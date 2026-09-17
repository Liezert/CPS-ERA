<?php

namespace App\Services;

use App\Models\BaActivityLog;
use App\Models\BaIncident;
use App\Models\KnowledgeDocument;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Models\Video;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BaIncidentService
{
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
                $incident = BaIncident::findOrFail($baIncidentId);
                $incident->update([
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
                    'status' => 'draft',
                ]);

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
     * Submit Langkah 2 (Video): validasi file ATAU link wajib ada, ubah status jadi 'submitted'.
     *
     * @param  array<string, mixed>  $videoData
     */
    public function submitWithVideo(BaIncident $incident, User $actor, array $videoData): BaIncident
    {
        $hasFile = ! empty($videoData['video_file']);
        $hasLink = ! empty(trim((string) ($videoData['video_external_link'] ?? '')));

        if (! $hasFile && ! $hasLink) {
            throw new DomainException('Salah satu dari file video atau tautan link eksternal wajib diisi.');
        }

        return DB::transaction(function () use ($incident, $actor, $videoData, $hasFile, $hasLink): BaIncident {
            $fileUrl = null;
            if ($hasFile) {
                $file = $videoData['video_file'];
                if ($file instanceof UploadedFile) {
                    $path = $file->store('videos/mandatory', 'public');
                    $fileUrl = Storage::url($path);
                } elseif (is_string($file)) {
                    $fileUrl = $file;
                }
            }

            $externalLink = $hasLink ? trim((string) $videoData['video_external_link']) : null;
            $effectiveUrl = $fileUrl ?? $externalLink;

            // Buat atau perbarui entri Video terkait BA
            Video::updateOrCreate(
                ['ba_incident_id' => $incident->id],
                [
                    'title' => $videoData['title'] ?? ('Video Bukti & Penanganan: '.$incident->nomor_ba),
                    'description' => $videoData['description'] ?? "Video dokumentasi penanganan insiden {$incident->nomor_ba}",
                    'video_url' => $effectiveUrl,
                    'video_file_url' => $fileUrl,
                    'video_external_link' => $externalLink,
                    'division_id' => $incident->division_id,
                    'created_by' => $actor->id,
                    'creation_reason' => 'mandatory_incident',
                    'status' => 'pending_supervisor',
                ]
            );

            $incident->update([
                'status' => 'submitted',
                'catatan_penolakan' => null, // Reset catatan penolakan jika ini adalah resubmission
            ]);

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => 'BA & Video Diserahkan',
                'note' => 'Laporan BA beserta video penanganan resmi diserahkan oleh '.$actor->name,
            ]);

            return $incident->fresh();
        });
    }

    /**
     * Setujui BA (Approve) dengan mengisi status_verifikasi dan bukti/alasan.
     *
     * @param  array<string, mixed>  $verificationData
     */
    public function approve(BaIncident $incident, User $actor, array $verificationData, ?string $logAction = null): BaIncident
    {
        $isAuthorized = $actor->hasAnyRole(['admin', 'quality']) ||
            ($actor->hasRole('supervisor') && (int) $actor->division_id === (int) $incident->division_id);

        if (! $isAuthorized) {
            throw new DomainException('Aksi persetujuan hanya dapat dilakukan oleh Supervisor divisi terkait atau Admin.');
        }

        if (in_array($incident->status, ['approved', 'closed'], true)) {
            throw new DomainException('Laporan BA sudah disetujui sebelumnya.');
        }

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

        return DB::transaction(function () use ($incident, $actor, $statusVerifikasi, $buktiObjektif, $alasanTidakEfektif, $logAction): BaIncident {
            $incident->update([
                'status' => 'approved',
                'status_verifikasi' => $statusVerifikasi,
                'bukti_objektif' => $statusVerifikasi === 'efektif' ? $buktiObjektif : null,
                'alasan_tidak_efektif' => $statusVerifikasi === 'tidak_efektif' ? $alasanTidakEfektif : null,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'closed_at' => now(),
            ]);

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => $logAction ?? 'BA Disetujui',
                'note' => 'BA telah diverifikasi dengan hasil '.ucfirst(str_replace('_', ' ', $statusVerifikasi)).' oleh '.$actor->name,
            ]);

            // 1. Otomatis buat Lesson Learned di knowledge_documents
            $deskripsiRingkasan = 'Masalah: '.($incident->deskripsi_masalah ?: ($incident->description ?: 'Insiden tercatat.'))
                ."\n\nAkar Masalah: ".($incident->kesimpulan_akar_masalah ?: 'Analisis akar masalah terlampir pada dokumen.')
                ."\n\nTindakan Korektif: ".($incident->korektif_deskripsi ?: ($incident->koreksi_deskripsi ?: 'Tindakan korektif selesai diverifikasi.'));

            KnowledgeDocument::create([
                'title' => 'Lesson Learned: '.$incident->nomor_ba,
                'division_id' => $incident->division_id,
                'type' => 'lesson_learned',
                'file_url' => $incident->video?->video_url ?? '/files/lesson-learned-default.pdf',
                'external_link' => $incident->video?->video_external_link,
                'description' => $deskripsiRingkasan,
                'source_ba_id' => $incident->id,
                'created_by' => $actor->id,
                'status' => 'published',
            ]);

            // 2. Berikan Poin CPS ERA kepada pembuat BA via KpiContributionService (Jalur A, cap 3/tahun)
            $creator = $incident->creator ?: User::find($incident->created_by);
            if ($creator) {
                app(KpiContributionService::class)->recordBaVideoApproved($creator, $incident);
            }

            // 3. Pipeline BA -> Kandidat Materi Learning (status candidate menunggu post-test dari HRD)
            $defaultCategory = LearningCategory::query()->first()
                ?? LearningCategory::create([
                    'name' => 'Umum / Kaizen',
                    'created_by' => $actor->id,
                ]);
            $defaultCategoryId = $defaultCategory->id;
            $contentUrl = $incident->video_file_url
                ?: ($incident->video_external_link
                    ?: ($incident->video?->video_url ?? $incident->video?->video_external_link ?? '/videos/placeholder.mp4'));

            LearningMaterial::create([
                'learning_category_id' => $defaultCategoryId,
                'title' => 'Video Penanganan: '.$incident->nomor_ba,
                'type' => 'video',
                'source_ba_id' => $incident->id,
                'status' => 'candidate',
                'content_url' => $contentUrl,
                'created_by' => $actor->id,
            ]);

            // 4. Sinkronkan status video terkait
            if ($incident->video) {
                $incident->video->update([
                    'status' => 'published',
                    'supervisor_reviewed_by' => $actor->id,
                    'supervisor_reviewed_at' => now(),
                    'hr_reviewed_by' => $actor->id,
                    'hr_reviewed_at' => now(),
                ]);
            }

            return $incident->fresh();
        });
    }

    /**
     * Tolak BA (Reject) dengan mencatat alasan penolakan.
     */
    public function reject(BaIncident $incident, User $actor, string $rejectionReason): BaIncident
    {
        $isAuthorized = $actor->hasRole('admin') ||
            ($actor->hasRole('supervisor') && (int) $actor->division_id === (int) $incident->division_id);

        if (! $isAuthorized) {
            throw new DomainException('Aksi penolakan hanya dapat dilakukan oleh Supervisor divisi terkait atau Admin.');
        }

        $reason = trim($rejectionReason);
        if (empty($reason)) {
            throw new DomainException('Catatan alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($incident, $actor, $reason): BaIncident {
            $incident->update([
                'status' => 'rejected',
                'catatan_penolakan' => $reason,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => 'BA Ditolak / Perbaikan Diminta',
                'note' => $reason,
            ]);

            return $incident->fresh();
        });
    }

    /**
     * Backward-compatibility wrapper for legacy create calls.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor, UploadedFile|string|null $fileBa = null, UploadedFile|string|null $fileFtk = null): BaIncident
    {
        $nomorBa = $this->generateNomorBa();

        $incident = BaIncident::create([
            'nomor_ba' => $nomorBa,
            'title' => $data['title'] ?? ('BA '.$nomorBa),
            'description' => $data['description'] ?? ($data['deskripsi_masalah'] ?? null),
            'division_id' => $data['division_id'],
            'deskripsi_masalah' => $data['deskripsi_masalah'] ?? ($data['description'] ?? 'Deskripsi insiden'),
            'tanggal_pengisian' => now()->toDateString(),
            'tanggal_masalah' => now()->toDateString(),
            'lokasi' => $data['lokasi'] ?? 'Area Pabrik',
            'why_1' => $data['why_1'] ?? 'Akar masalah awal terdeteksi.',
            'kesimpulan_akar_masalah' => $data['kesimpulan_akar_masalah'] ?? 'Kesimpulan investigasi.',
            'koreksi_deskripsi' => $data['koreksi_deskripsi'] ?? 'Tindakan penanganan cepat.',
            'korektif_deskripsi' => $data['korektif_deskripsi'] ?? 'Tindakan perbaikan jangka panjang.',
            'status' => 'submitted',
            'created_by' => $actor->id,
        ]);

        BaActivityLog::create([
            'ba_incident_id' => $incident->id,
            'actor_id' => $actor->id,
            'action' => 'BA dibuat',
            'note' => 'Laporan BA baru dibuat oleh '.$actor->name,
        ]);

        return $incident;
    }

    /**
     * Backward-compatibility wrapper for legacy review() call.
     */
    public function review(BaIncident $incident, User $actor, ?string $note = null): BaIncident
    {
        if (in_array($incident->status, ['reviewed', 'approved', 'closed'], true)) {
            throw new DomainException('Laporan BA sudah ditinjau/disetujui sebelumnya.');
        }

        return $this->approve($incident, $actor, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => $note ?? 'Verifikasi tindakan korektif diverifikasi efektif.',
        ], 'Ditinjau oleh '.$actor->name);
    }

    /**
     * Backward-compatibility wrapper for legacy close() call.
     */
    public function close(BaIncident $incident, User $actor, ?string $note = null): BaIncident
    {
        if (in_array($incident->status, ['draft', 'created', 'submitted'], true)) {
            throw new DomainException('Laporan BA harus disetujui/ditinjau terlebih dahulu sebelum ditutup.');
        }

        return DB::transaction(function () use ($incident, $actor, $note): BaIncident {
            $incident->update([
                'status' => 'approved',
                'closed_at' => now(),
            ]);

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => 'Ditutup oleh '.$actor->name,
                'note' => $note ?? 'BA telah diselesaikan dan ditutup.',
            ]);

            return $incident->fresh();
        });
    }
}
