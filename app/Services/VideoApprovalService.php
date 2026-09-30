<?php

namespace App\Services;

use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Notification;
use App\Models\User;
use App\Models\Video;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Video kontribusi (keputusan owner 2026-09-27), terpisah dari laporan CAPA:
 * unggah (karyawan/supervisor) -> review HR saja -> terbit di Learning + 1 Poin CPS ERA.
 */
class VideoApprovalService
{
    public const CREATION_REASON = 'voluntary_improvement';

    /**
     * Kirim video kontribusi ke antrean review HR. Berkas diunggah ke Drive, atau cukup tautan eksternal.
     *
     * @param  array{title: string, description?: ?string, learning_category_id?: ?int, video_file?: ?UploadedFile, video_external_link?: ?string}  $data
     *
     * @throws DomainException
     */
    public function submit(User $actor, array $data): Video
    {
        Gate::forUser($actor)->authorize('create', Video::class);

        if (! $actor->division_id) {
            throw new DomainException('Akun Anda belum terhubung ke divisi. Hubungi admin sebelum mengunggah video.');
        }

        $file = $data['video_file'] ?? null;
        $link = trim((string) ($data['video_external_link'] ?? ''));

        if (! $file instanceof UploadedFile && $link === '') {
            throw new DomainException('Unggah berkas video atau cantumkan tautan video eksternal.');
        }

        // Unggahan ke Drive dilakukan SEBELUM transaksi: video 100MB memakan puluhan detik.
        $driveFileId = $file instanceof UploadedFile ? $this->uploadVideoToDrive($file, $data['title']) : null;

        try {
            return Video::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'video_url' => $driveFileId ? app(GoogleDriveService::class)->getPreviewUrl($driveFileId) : $link,
                // Kolom ini menyimpan file ID Drive (bukan path lokal).
                'video_file_url' => $driveFileId,
                'video_external_link' => $driveFileId ? null : $link,
                'division_id' => $actor->division_id,
                'learning_category_id' => $data['learning_category_id'] ?? null,
                'created_by' => $actor->id,
                'creation_reason' => self::CREATION_REASON,
                'status' => 'pending_hr',
            ]);
        } catch (Throwable $exception) {
            // Penyimpanan gagal: berkas yang terlanjur naik tidak boleh jadi sampah di Drive.
            if ($driveFileId !== null) {
                $this->discardDriveFile($driveFileId);
            }

            throw $exception;
        }
    }

    /**
     * HR menyetujui video: terbit sebagai materi Learning dan pengunggah mendapat 1 Poin CPS ERA
     * (selama cap tahunan 3 poin belum tercapai).
     *
     * @throws DomainException
     */
    public function approve(Video $video, User $actor, ?string $note = null): Video
    {
        $this->authorizeReview($video, $actor);

        $creator = $video->creator ?: User::find($video->created_by);
        if ($creator) {
            // Siapkan baris KPI tahunan di luar transaksi (lihat KpiContributionService::ensureYearlyRecord).
            app(KpiContributionService::class)->ensureYearlyRecord($creator);
        }

        return DB::transaction(function () use ($video, $actor, $note, $creator): Video {
            $this->lockPendingHr($video);

            $video->update([
                'status' => 'published',
                'hr_reviewed_by' => $actor->id,
                'hr_reviewed_at' => now(),
                'hr_notes' => $note,
            ]);

            LearningMaterial::create([
                'learning_category_id' => $video->learning_category_id ?? $this->defaultCategoryId($actor),
                'title' => $video->title,
                'type' => 'video',
                'content_url' => $video->video_url,
                'description' => $video->description,
                'source_video_id' => $video->id,
                'status' => 'published',
                'created_by' => $video->created_by,
            ]);

            if ($creator) {
                app(KpiContributionService::class)->recordVideoContributionApproved($creator, $video);
            }

            $this->notifyUploader($video, 'video_disetujui', 'Video Disetujui: '.$video->title, "Video \"{$video->title}\" disetujui HR dan sudah tayang di Learning.");

            return $video->fresh();
        }, attempts: 3);
    }

    private function notifyUploader(Video $video, string $type, string $title, string $message): void
    {
        Notification::create([
            'user_id' => $video->created_by,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_type' => 'video',
            'related_id' => (string) $video->id,
            'read_at' => null,
        ]);
    }

    /**
     * HR menolak video. Alasan wajib diisi dan ditampilkan ke pengunggah.
     *
     * @throws DomainException
     */
    public function reject(Video $video, User $actor, string $reason): Video
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }

        $this->authorizeReview($video, $actor);

        return DB::transaction(function () use ($video, $actor, $reason): Video {
            $this->lockPendingHr($video);

            $video->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'hr_reviewed_by' => $actor->id,
                'hr_reviewed_at' => now(),
                'hr_notes' => $reason,
            ]);

            $this->notifyUploader($video, 'video_ditolak', 'Video Ditolak: '.$video->title, "Video \"{$video->title}\" ditolak HR. Alasan: {$reason}");

            return $video->fresh();
        });
    }

    private function authorizeReview(Video $video, User $actor): void
    {
        if (Gate::forUser($actor)->denies('review', $video)) {
            throw new DomainException('Video hanya bisa disetujui/ditolak tim HR selama menunggu review, dan tidak oleh pengunggahnya sendiri.');
        }
    }

    /**
     * Kunci baris video dan pastikan statusnya masih pending_hr: dua keputusan bersamaan
     * tidak boleh sama-sama lolos (mis. HR klik ganda, atau dua anggota HR bersamaan).
     */
    private function lockPendingHr(Video $video): void
    {
        $status = Video::query()->whereKey($video->getKey())->lockForUpdate()->value('status');

        if ($status !== 'pending_hr') {
            throw new DomainException('Video ini sudah diputuskan sebelumnya. Muat ulang halaman untuk melihat status terbaru.');
        }
    }

    private function defaultCategoryId(User $actor): int
    {
        return LearningCategory::query()->value('id')
            ?? LearningCategory::create(['name' => 'Umum / Kaizen', 'created_by' => $actor->id])->id;
    }

    /**
     * Unggah berkas video ke folder video di Drive dan buka aksesnya via tautan.
     *
     * @return string File ID Drive
     */
    private function uploadVideoToDrive(UploadedFile $file, string $title): string
    {
        // Folder yang sama dengan video CAPA lama, supaya konfigurasi production tidak berubah.
        $folderId = config('services.google_drive.folder_id_ba');

        if (blank($folderId)) {
            throw new DomainException('Folder Google Drive untuk video belum dikonfigurasi (GOOGLE_DRIVE_FOLDER_ID_BA).');
        }

        $drive = app(GoogleDriveService::class);
        $fileName = 'VIDEO-'.now()->format('Ymd-His').'-'.str($title)->slug()->limit(40, '').'.'.$file->getClientOriginalExtension();

        try {
            $fileId = $drive->uploadFileResumable(
                $file->getRealPath(),
                $folderId,
                $fileName,
                chunkBytes: null,
                // MIME dari pengunggah, bukan tebakan atas isi berkas.
                mimeType: $file->getMimeType(),
            );

            $drive->setPublicPermission($fileId);

            return $fileId;
        } catch (RuntimeException $exception) {
            Log::error('Unggah video kontribusi ke Google Drive gagal', ['error' => $exception->getMessage()]);

            throw new DomainException('Gagal mengunggah video ke Google Drive. Periksa koneksi lalu coba lagi.', previous: $exception);
        }
    }

    /**
     * Hapus berkas Drive yang terlanjur terunggah saat penyimpanan gagal.
     */
    private function discardDriveFile(string $fileId): void
    {
        try {
            app(GoogleDriveService::class)->deleteFile($fileId);
        } catch (Throwable $exception) {
            // Kegagalan pembersihan tidak boleh menutupi galat aslinya.
            Log::warning('Gagal membersihkan berkas Drive setelah penyimpanan gagal', [
                'file_id' => $fileId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
