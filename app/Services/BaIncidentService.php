<?php

namespace App\Services;

use App\Enums\BaIncidentStatus;
use App\Models\BaActivityLog;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Models\Video;
use DateTimeInterface;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BaIncidentService
{
    /**
     * Isi laporan yang dilacak saat revisi, beserta label yang tampil di riwayat aktivitas.
     * `title` dan `description` tidak ikut karena diturunkan otomatis dari deskripsi masalah.
     */
    private const REVISION_FIELD_LABELS = [
        'division_id' => 'Divisi',
        'tanggal_pengisian' => 'Tanggal Pengisian',
        'sumber_ketidaksesuaian' => 'Sumber Ketidaksesuaian',
        'sumber_ketidaksesuaian_lainnya' => 'Sumber Ketidaksesuaian Lainnya',
        'tanggal_masalah' => 'Tanggal Masalah',
        'lokasi' => 'Lokasi',
        'deskripsi_masalah' => 'Deskripsi Masalah',
        'why_1' => 'Why 1',
        'why_2' => 'Why 2',
        'why_3' => 'Why 3',
        'why_4' => 'Why 4',
        'why_5' => 'Why 5',
        'kesimpulan_akar_masalah' => 'Kesimpulan Akar Masalah',
        'koreksi_deskripsi' => 'Koreksi',
        'koreksi_pic' => 'PIC Koreksi',
        'koreksi_waktu' => 'Waktu Koreksi',
        'korektif_deskripsi' => 'Tindakan Korektif',
        'korektif_pic' => 'PIC Tindakan Korektif',
        'korektif_waktu' => 'Waktu Tindakan Korektif',
        'is_potensi_risiko' => 'Potensi Risiko',
        'is_potensi_peluang' => 'Potensi Peluang',
    ];

    /**
     * Generate unique, sequential BA number with database locking to prevent race conditions.
     * Format: BA-YYYY-NNNN (reset annually).
     */
    public function generateNomorBa(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $prefix = "BA-{$year}-";

        return DB::transaction(function () use ($year, $prefix): string {
            $castType = DB::connection()->getDriverName() === 'mysql' ? 'UNSIGNED' : 'INTEGER';

            $latestIncident = BaIncident::query()
                ->where('nomor_ba', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByRaw("CAST(SUBSTRING(nomor_ba, 9) AS {$castType}) DESC")
                ->first();

            $sequence = 1;
            if ($latestIncident !== null) {
                $parts = explode('-', $latestIncident->nomor_ba);
                $sequence = ((int) end($parts)) + 1;
            }

            return sprintf('BA-%04d-%04d', $year, $sequence);
        });
    }

    /**
     * Simpan Langkah 1 Form Digital CAPA/FTK sebagai Draft.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveDraft(array $data, User $actor, ?string $baIncidentId = null): BaIncident
    {
        return DB::transaction(function () use ($data, $actor, $baIncidentId): BaIncident {
            if ($baIncidentId) {
                $incident = BaIncident::lockForUpdate()->findOrFail($baIncidentId);

                if (! $incident->isEditable()) {
                    throw new DomainException("Laporan {$incident->nomor_ba} sedang dalam proses review atau sudah final, sehingga isinya tidak bisa diubah.");
                }

                // Kepemilikan dicek setelah status: policy update juga mensyaratkan editable,
                // jadi pemilik laporan yang sedang direview tetap mendapat pesan di atas.
                Gate::forUser($actor)->authorize('update', $incident);

                $isRevision = $incident->isRevisionRequested();

                $incident->fill([
                    'division_id' => $data['division_id'] ?? $incident->division_id,
                    'title' => $data['title'] ?? $incident->title,
                    'description' => $data['deskripsi_masalah'] ?? $data['description'] ?? $incident->description,
                    'tanggal_pengisian' => $data['tanggal_pengisian'] ?? $incident->tanggal_pengisian ?? now()->toDateString(),
                    'sumber_ketidaksesuaian' => $data['sumber_ketidaksesuaian'] ?? $incident->sumber_ketidaksesuaian ?? 'laporan_ketidaksesuaian',
                    'sumber_ketidaksesuaian_lainnya' => $data['sumber_ketidaksesuaian_lainnya'] ?? $incident->sumber_ketidaksesuaian_lainnya,
                    'tanggal_masalah' => $data['tanggal_masalah'] ?? $incident->tanggal_masalah,
                    'lokasi' => $data['lokasi'] ?? $incident->lokasi,
                    'deskripsi_masalah' => $data['deskripsi_masalah'] ?? $incident->deskripsi_masalah,
                    'why_1' => $data['why_1'] ?? $incident->why_1,
                    'why_2' => $data['why_2'] ?? $incident->why_2,
                    'why_3' => $data['why_3'] ?? $incident->why_3,
                    'why_4' => $data['why_4'] ?? $incident->why_4,
                    'why_5' => $data['why_5'] ?? $incident->why_5,
                    'kesimpulan_akar_masalah' => $data['kesimpulan_akar_masalah'] ?? $incident->kesimpulan_akar_masalah,
                    'koreksi_deskripsi' => $data['koreksi_deskripsi'] ?? $incident->koreksi_deskripsi,
                    'koreksi_pic' => $data['koreksi_pic'] ?? $incident->koreksi_pic,
                    'koreksi_waktu' => $data['koreksi_waktu'] ?? $incident->koreksi_waktu,
                    'korektif_deskripsi' => $data['korektif_deskripsi'] ?? $incident->korektif_deskripsi,
                    'korektif_pic' => $data['korektif_pic'] ?? $incident->korektif_pic,
                    'korektif_waktu' => $data['korektif_waktu'] ?? $incident->korektif_waktu,
                    'is_potensi_risiko' => $data['is_potensi_risiko'] ?? $incident->is_potensi_risiko ?? false,
                    'is_potensi_peluang' => $data['is_potensi_peluang'] ?? $incident->is_potensi_peluang ?? false,
                    // Laporan yang sedang direvisi tetap revision_requested sampai benar-benar
                    // dikirim ulang; hanya draf biasa yang disimpan sebagai draft.
                    'status' => $isRevision ? BaIncidentStatus::RevisionRequested->value : BaIncidentStatus::Draft->value,
                ]);

                $changes = $isRevision ? $this->describeRevisionChanges($incident) : [];

                $incident->save();

                if ($changes !== []) {
                    BaActivityLog::create([
                        'ba_incident_id' => $incident->id,
                        'actor_id' => $actor->id,
                        'action' => 'Revisi BA disimpan',
                        'note' => 'Diubah oleh '.$actor->name.': '.implode('; ', $changes),
                    ]);
                }

                return $incident->fresh();
            }

            $nomorBa = $this->generateNomorBa();

            $incident = BaIncident::create([
                'nomor_ba' => $nomorBa,
                'division_id' => $data['division_id'],
                'title' => $data['title'] ?? ('CAPA '.$nomorBa.': '.mb_substr((string) ($data['deskripsi_masalah'] ?? 'Insiden Baru'), 0, 50)),
                'description' => $data['deskripsi_masalah'] ?? ($data['description'] ?? null),
                'tanggal_pengisian' => $data['tanggal_pengisian'] ?? now()->toDateString(),
                'sumber_ketidaksesuaian' => $data['sumber_ketidaksesuaian'] ?? 'laporan_ketidaksesuaian',
                'sumber_ketidaksesuaian_lainnya' => $data['sumber_ketidaksesuaian_lainnya'] ?? null,
                'tanggal_masalah' => $data['tanggal_masalah'] ?? now()->toDateString(),
                'lokasi' => $data['lokasi'] ?? null,
                'deskripsi_masalah' => $data['deskripsi_masalah'] ?? null,
                'why_1' => $data['why_1'] ?? null,
                'why_2' => $data['why_2'] ?? null,
                'why_3' => $data['why_3'] ?? null,
                'why_4' => $data['why_4'] ?? null,
                'why_5' => $data['why_5'] ?? null,
                'kesimpulan_akar_masalah' => $data['kesimpulan_akar_masalah'] ?? null,
                'koreksi_deskripsi' => $data['koreksi_deskripsi'] ?? null,
                'koreksi_pic' => $data['koreksi_pic'] ?? null,
                'koreksi_waktu' => $data['koreksi_waktu'] ?? null,
                'korektif_deskripsi' => $data['korektif_deskripsi'] ?? null,
                'korektif_pic' => $data['korektif_pic'] ?? null,
                'korektif_waktu' => $data['korektif_waktu'] ?? null,
                'is_potensi_risiko' => $data['is_potensi_risiko'] ?? false,
                'is_potensi_peluang' => $data['is_potensi_peluang'] ?? false,
                'status' => 'draft',
                'created_by' => $actor->id,
            ]);

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => 'Draft BA dibuat',
                'note' => 'Langkah 1 Form CAPA disimpan sebagai draft oleh '.$actor->name,
            ]);

            return $incident;
        });
    }

    /**
     * Ringkasan field isi laporan yang berubah (belum disimpan), untuk riwayat revisi.
     *
     * @return list<string>
     */
    private function describeRevisionChanges(BaIncident $incident): array
    {
        $changes = [];

        foreach (self::REVISION_FIELD_LABELS as $field => $label) {
            if (! $incident->isDirty($field)) {
                continue;
            }

            $old = $this->formatRevisionValue($field, $incident->getOriginal($field));
            $new = $this->formatRevisionValue($field, $incident->getAttribute($field));

            // Mis. null → '' tidak dianggap perubahan isi.
            if ($old !== $new) {
                $changes[] = "{$label} (\"{$old}\" → \"{$new}\")";
            }
        }

        return $changes;
    }

    private function formatRevisionValue(string $field, mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => '-',
            is_bool($value) => $value ? 'Ya' : 'Tidak',
            $value instanceof DateTimeInterface => $value->format('d/m/Y'),
            $field === 'division_id' => Division::whereKey($value)->value('name') ?? (string) $value,
            $field === 'sumber_ketidaksesuaian' => BaIncident::SUMBER_OPTIONS[$value] ?? (string) $value,
            default => Str::limit((string) $value, 60),
        };
    }

    /**
     * Submit Langkah 2 (Video): validasi file ATAU link wajib ada, ubah status jadi 'pending_supervisor' (submit awal maupun resubmit).
     *
     * @param  array<string, mixed>  $videoData
     */
    public function submitWithVideo(BaIncident $incident, User $actor, array $videoData): BaIncident
    {
        if (! $incident->isEditable()) {
            throw new DomainException("Laporan {$incident->nomor_ba} sedang dalam proses review atau sudah final, sehingga tidak bisa diserahkan lagi.");
        }

        Gate::forUser($actor)->authorize('update', $incident);

        $existingVideo = $incident->video;
        $hasFile = ! empty($videoData['video_file']);
        $hasLink = ! empty(trim((string) ($videoData['video_external_link'] ?? '')));

        // Resubmit tanpa mengganti video: pakai video/tautan yang sudah terkirim sebelumnya.
        if (! $hasFile && ! $hasLink && $existingVideo) {
            $videoData['video_file'] = GoogleDriveService::isDriveFileId($existingVideo->video_file_url)
                ? $existingVideo->video_file_url
                : null;
            $videoData['video_external_link'] = $existingVideo->video_external_link;
            $hasFile = ! empty($videoData['video_file']);
            $hasLink = ! empty(trim((string) $videoData['video_external_link']));
        }

        if (! $hasFile && ! $hasLink) {
            throw new DomainException('Salah satu dari file video atau tautan link eksternal wajib diisi.');
        }

        // Unggahan ke Drive dilakukan SEBELUM transaksi: video 100MB memakan
        // puluhan detik, dan menahan transaksi selama itu mengunci baris terlalu lama.
        $driveFileId = null;

        if ($hasFile) {
            $file = $videoData['video_file'];

            if ($file instanceof UploadedFile) {
                $driveFileId = $this->uploadVideoToDrive($file, $incident);
            } elseif (is_string($file)) {
                // Nilai string berarti berkas sudah pernah diunggah (mis. revisi tanpa ganti video).
                $driveFileId = $file;
            }
        }

        $previousDriveFileId = $existingVideo?->video_file_url;

        try {
            $submitted = $this->persistVideoSubmission($incident, $actor, $videoData, $driveFileId, $hasLink);
        } catch (Throwable $exception) {
            // Penyimpanan gagal: berkas yang terlanjur naik tidak boleh jadi sampah di Drive.
            // Berkas lama tidak disentuh, jadi video sebelumnya tetap utuh.
            if ($driveFileId !== null && $videoData['video_file'] instanceof UploadedFile) {
                $this->discardDriveFile($driveFileId);
            }

            throw $exception;
        }

        // Video diganti: berkas lama baru dihapus setelah record baru pasti tersimpan.
        if (GoogleDriveService::isDriveFileId($previousDriveFileId) && $previousDriveFileId !== $driveFileId) {
            $this->discardDriveFile($previousDriveFileId);
        }

        return $submitted;
    }

    /**
     * Unggah berkas video ke folder CAPA di Drive dan buka aksesnya via tautan.
     *
     * @return string File ID Drive
     */
    protected function uploadVideoToDrive(UploadedFile $file, BaIncident $incident): string
    {
        $folderId = config('services.google_drive.folder_id_ba');

        if (blank($folderId)) {
            throw new DomainException(
                'Folder Google Drive untuk video CAPA belum dikonfigurasi (GOOGLE_DRIVE_FOLDER_ID_BA).'
            );
        }

        $drive = app(GoogleDriveService::class);
        $fileName = $incident->nomor_ba.'-'.now()->format('Ymd-His').'.'.$file->getClientOriginalExtension();

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
            Log::error('Unggah video CAPA ke Google Drive gagal', [
                'ba_incident_id' => $incident->id,
                'error' => $exception->getMessage(),
            ]);

            throw new DomainException(
                'Gagal mengunggah video ke Google Drive. Periksa koneksi lalu coba lagi. '
                .'Laporan Anda belum dikirim, data formulir tetap tersimpan sebagai draf.',
                previous: $exception
            );
        }
    }

    /**
     * Hapus berkas Drive yang terlanjur terunggah saat penyimpanan gagal.
     */
    protected function discardDriveFile(string $fileId): void
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

    /**
     * @param  array<string, mixed>  $videoData
     */
    protected function persistVideoSubmission(
        BaIncident $incident,
        User $actor,
        array $videoData,
        ?string $driveFileId,
        bool $hasLink
    ): BaIncident {
        return DB::transaction(function () use ($incident, $actor, $videoData, $driveFileId, $hasLink): BaIncident {
            $this->lockInStatus($incident, BaIncidentStatus::Draft, BaIncidentStatus::RevisionRequested);
            $isResubmit = $incident->isRevisionRequested();

            $externalLink = $hasLink ? trim((string) $videoData['video_external_link']) : null;
            $effectiveUrl = $driveFileId
                ? app(GoogleDriveService::class)->getPreviewUrl($driveFileId)
                : $externalLink;

            // Buat atau perbarui entri Video terkait BA
            Video::updateOrCreate(
                ['ba_incident_id' => $incident->id],
                [
                    'title' => $videoData['title'] ?? ('Video Bukti & Penanganan: '.$incident->nomor_ba),
                    'description' => $videoData['description'] ?? "Video dokumentasi penanganan insiden {$incident->nomor_ba}",
                    'video_url' => $effectiveUrl,
                    // Kolom ini kini menyimpan file ID Drive (bukan path lokal).
                    'video_file_url' => $driveFileId,
                    'video_external_link' => $externalLink,
                    'division_id' => $incident->division_id,
                    'created_by' => $actor->id,
                    'creation_reason' => 'mandatory_incident',
                    'status' => 'pending_supervisor',
                ]
            );

            $incident->update([
                'status' => BaIncidentStatus::PendingSupervisor->value,
                'catatan_penolakan' => null, // Reset catatan penolakan jika ini adalah resubmission
            ]);

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => $isResubmit ? 'BA Dikirim Ulang' : 'BA & Video Diserahkan',
                'note' => $isResubmit
                    ? 'Laporan hasil revisi dikirim ulang ke Supervisor oleh '.$actor->name
                    : 'Laporan BA beserta video penanganan resmi diserahkan oleh '.$actor->name,
            ]);

            return $incident->fresh();
        });
    }

    /**
     * Kunci baris laporan dan pastikan statusnya masih salah satu yang diharapkan. Pengecekan di
     * luar transaksi hanya membaca model di memori; dua aksi bersamaan bisa sama-sama lolos di
     * sana, jadi status dibaca ulang di sini setelah baris terkunci.
     */
    private function lockInStatus(BaIncident $incident, BaIncidentStatus ...$allowed): void
    {
        $locked = BaIncident::query()->whereKey($incident->getKey())->lockForUpdate()->firstOrFail();

        if (! in_array($locked->status, array_map(fn (BaIncidentStatus $status): string => $status->value, $allowed), true)) {
            throw new DomainException("Status laporan {$locked->nomor_ba} sudah berubah. Muat ulang halaman lalu coba lagi.");
        }

        // Sinkronkan instance milik pemanggil alih-alih menggantinya: pemanggil (mis. Filament
        // action) mengandalkan model yang mereka kirim ikut ter-update.
        $incident->setRawAttributes($locked->getAttributes(), true);
    }

    /**
     * @throws DomainException jika aktor tidak berwenang untuk tahap laporan saat ini
     */
    private function authorizeStage(User $actor, string $ability, BaIncident $incident, string $message): void
    {
        if (Gate::forUser($actor)->denies($ability, $incident)) {
            throw new DomainException($message);
        }
    }

    /**
     * Tahap 1: Supervisor divisi pelapor menyetujui laporan dan meneruskannya ke HR. Catatan
     * lapangan (opsional) disimpan di riwayat aktivitas dan ditampilkan ke HR saat review final.
     */
    public function approveAsSupervisor(BaIncident $incident, User $actor, ?string $note = null): BaIncident
    {
        $this->authorizeStage($actor, 'reviewAsSupervisor', $incident, 'Persetujuan tahap Supervisor hanya untuk Supervisor divisi pelapor, selama laporan menunggu review Supervisor.');

        return DB::transaction(function () use ($incident, $actor, $note): BaIncident {
            $this->lockInStatus($incident, BaIncidentStatus::PendingSupervisor);

            $incident->update([
                'status' => BaIncidentStatus::PendingHr->value,
                'supervisor_reviewed_by' => $actor->id,
                'supervisor_reviewed_at' => now(),
            ]);

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => 'BA Disetujui Supervisor',
                'note' => filled($note) ? trim($note) : null,
            ]);

            return $incident->fresh();
        });
    }

    /**
     * Catatan lapangan terakhir dari Supervisor, untuk ditampilkan ke HR saat review final.
     */
    public function latestSupervisorNote(BaIncident $incident): ?BaActivityLog
    {
        return BaActivityLog::query()
            ->with('actor')
            ->where('ba_incident_id', $incident->id)
            ->where('action', 'BA Disetujui Supervisor')
            ->latest()
            ->first();
    }

    /**
     * Tahap 2 (final): HR menyetujui laporan dengan evaluasi formal (status_verifikasi dan
     * bukti/alasan), lalu poin diberikan dan Lesson Learned diterbitkan.
     *
     * @param  array<string, mixed>  $verificationData
     */
    public function approve(BaIncident $incident, User $actor, array $verificationData): BaIncident
    {
        $this->authorizeStage($actor, 'reviewAsHr', $incident, 'Persetujuan final hanya untuk tim HR, selama laporan menunggu review HR.');

        $statusVerifikasi = $verificationData['status_verifikasi'] ?? null;
        if (! in_array($statusVerifikasi, ['efektif', 'tidak_efektif'], true)) {
            throw new DomainException('Status verifikasi tindakan korektif wajib dipilih (efektif atau tidak_efektif).');
        }

        $buktiObjektif = trim((string) ($verificationData['bukti_objektif'] ?? ''));
        $alasanTidakEfektif = trim((string) ($verificationData['alasan_tidak_efektif'] ?? ''));

        if ($statusVerifikasi === 'efektif' && empty($buktiObjektif)) {
            throw new DomainException('Bukti objektif wajib dicantumkan jika status verifikasi dinyatakan efektif.');
        }

        if ($statusVerifikasi === 'tidak_efektif' && empty($alasanTidakEfektif)) {
            throw new DomainException('Alasan ketidakefektifan wajib dicantumkan jika status verifikasi dinyatakan tidak efektif.');
        }

        // Siapkan baris KPI tahunan pembuat BA SEBELUM transaksi dibuka (autocommit),
        // sehingga di dalam transaksi hanya ada SELECT ... FOR UPDATE pada baris yang
        // sudah ada -- tanpa S-lock duplicate-key yang memicu deadlock.
        $creator = $incident->creator ?: User::find($incident->created_by);
        if ($creator) {
            app(KpiContributionService::class)->ensureYearlyRecord($creator);
        }

        return DB::transaction(function () use ($incident, $actor, $creator, $statusVerifikasi, $buktiObjektif, $alasanTidakEfektif): BaIncident {
            // Hanya satu approval yang bisa melanjutkan; approval kedua gagal di sini karena
            // statusnya sudah bukan pending_hr, sehingga poin tidak pernah diberikan dua kali.
            $this->lockInStatus($incident, BaIncidentStatus::PendingHr);

            $incident->update([
                'status' => BaIncidentStatus::Approved->value,
                'status_verifikasi' => $statusVerifikasi,
                'bukti_objektif' => $statusVerifikasi === 'efektif' ? $buktiObjektif : null,
                'alasan_tidak_efektif' => $statusVerifikasi === 'tidak_efektif' ? $alasanTidakEfektif : null,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'points_awarded_at' => $creator ? now() : null,
                'published_at' => now(),
                'closed_at' => now(),
            ]);

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => 'BA Disetujui HR (Final)',
                'note' => 'BA telah diverifikasi dengan hasil '.ucfirst(str_replace('_', ' ', $statusVerifikasi)).' oleh '.$actor->name,
            ]);

            // 1. Berikan Poin CPS ERA kepada pembuat BA via KpiContributionService (Jalur A, cap 3/tahun)
            if ($creator) {
                app(KpiContributionService::class)->recordBaVideoApproved($creator, $incident);
            }

            // 2. Hasil laporan masuk ke Learning (bukan Knowledge Repository, yang khusus berkas resmi
            // perusahaan) sebagai materi kandidat. Materi terbit otomatis saat HRGA membuat post-test-nya
            // (lihat QuizObserver::publishBaLearningMaterial).
            $ringkasanLaporan = 'Masalah: '.($incident->deskripsi_masalah ?: ($incident->description ?: 'Insiden tercatat.'))
                ."\n\nAkar Masalah: ".($incident->kesimpulan_akar_masalah ?: 'Analisis akar masalah terlampir pada dokumen.')
                ."\n\nTindakan Korektif: ".($incident->korektif_deskripsi ?: ($incident->koreksi_deskripsi ?: 'Tindakan korektif selesai diverifikasi.'));

            $defaultCategory = LearningCategory::query()->first()
                ?? LearningCategory::create([
                    'name' => 'Umum / Kaizen',
                    'created_by' => $actor->id,
                ]);
            $defaultCategoryId = $defaultCategory->id;
            // Materi Learning menyimpan URL yang bisa dibuka langsung; berkas Drive
            // dirujuk lewat URL preview, bukan file ID mentah.
            $driveFileId = $incident->video?->video_file_url;
            $contentUrl = GoogleDriveService::isDriveFileId($driveFileId)
                ? app(GoogleDriveService::class)->getPreviewUrl($driveFileId)
                : ($incident->video_file_url
                    ?: ($incident->video_external_link
                        ?: ($incident->video?->video_url ?? $incident->video?->video_external_link ?? '/videos/placeholder.mp4')));

            LearningMaterial::create([
                'learning_category_id' => $defaultCategoryId,
                'title' => 'Video Penanganan: '.$incident->nomor_ba,
                'type' => 'video',
                'description' => $ringkasanLaporan,
                'source_ba_id' => $incident->id,
                'status' => 'candidate',
                'content_url' => $contentUrl,
                'created_by' => $actor->id,
            ]);

            // 3. Sinkronkan status video terkait
            if ($incident->video) {
                $incident->video->update([
                    'status' => 'published',
                    'supervisor_reviewed_by' => $incident->supervisor_reviewed_by,
                    'supervisor_reviewed_at' => $incident->supervisor_reviewed_at,
                    'hr_reviewed_by' => $actor->id,
                    'hr_reviewed_at' => now(),
                ]);
            }

            return $incident->fresh();
        }, attempts: 3);
    }

    /**
     * Tolak laporan sesuai tahapnya:
     * - Supervisor: laporan dikembalikan ke pelapor untuk direvisi (`revision_requested`).
     * - HR: penolakan final (`rejected`), tanpa jalur revisi. Video lampiran BA diarsipkan sebagai
     *   draf internal: tidak tampil di feed publik, tetap bisa dibuka dan dihapus tim HR.
     */
    public function reject(BaIncident $incident, User $actor, string $rejectionReason): BaIncident
    {
        $reason = trim($rejectionReason);
        if ($reason === '') {
            throw new DomainException('Catatan alasan penolakan wajib diisi.');
        }

        $gate = Gate::forUser($actor);

        if ($gate->allows('reviewAsSupervisor', $incident)) {
            return DB::transaction(function () use ($incident, $actor, $reason): BaIncident {
                $this->lockInStatus($incident, BaIncidentStatus::PendingSupervisor);

                $incident->update([
                    'status' => BaIncidentStatus::RevisionRequested->value,
                    'catatan_penolakan' => $reason,
                ]);

                BaActivityLog::create([
                    'ba_incident_id' => $incident->id,
                    'actor_id' => $actor->id,
                    'action' => 'BA Ditolak Supervisor — Revisi Diminta',
                    'note' => $reason,
                ]);

                return $incident->fresh();
            });
        }

        if ($gate->allows('reviewAsHr', $incident)) {
            return DB::transaction(function () use ($incident, $actor, $reason): BaIncident {
                $this->lockInStatus($incident, BaIncidentStatus::PendingHr);

                $incident->update([
                    'status' => BaIncidentStatus::Rejected->value,
                    'catatan_penolakan' => $reason,
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => now(),
                ]);

                $incident->video()->where('creation_reason', 'mandatory_incident')->update(['status' => 'draft']);

                BaActivityLog::create([
                    'ba_incident_id' => $incident->id,
                    'actor_id' => $actor->id,
                    'action' => 'BA Ditolak HR (Final)',
                    'note' => $reason,
                ]);

                return $incident->fresh();
            });
        }

        throw new DomainException('Anda tidak berwenang menolak laporan ini pada tahap review saat ini.');
    }

    /**
     * Hapus rekaman video arsip dari BA yang ditolak permanen (hak tim HR). Berkas Drive dihapus
     * lebih dulu: kalau gagal, record tetap ada dan penghapusan bisa diulang.
     */
    public function deleteArchivedVideo(BaIncident $incident, User $actor): void
    {
        $this->authorizeStage($actor, 'deleteArchivedVideo', $incident, 'Hanya tim HR yang boleh menghapus rekaman video dari laporan yang ditolak permanen.');

        $video = $incident->video;
        $driveFileId = $video->video_file_url;

        if (GoogleDriveService::isDriveFileId($driveFileId)) {
            try {
                app(GoogleDriveService::class)->deleteFile($driveFileId);
            } catch (RuntimeException $exception) {
                throw new DomainException('Gagal menghapus berkas video di Google Drive. Coba lagi beberapa saat lagi.', previous: $exception);
            }
        }

        DB::transaction(function () use ($incident, $actor, $video): void {
            $video->delete();

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => 'Video BA Dihapus',
                'note' => 'Rekaman video arsip dihapus oleh '.$actor->name,
            ]);
        });

        $incident->unsetRelation('video');
    }
}
