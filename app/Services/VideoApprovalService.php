<?php

namespace App\Services;

use App\Models\KnowledgeDocument;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Video;
use DomainException;
use Illuminate\Support\Facades\DB;

class VideoApprovalService
{
    /**
     * Setujui video pada tahap 1: Supervisor Divisi.
     * Mengubah status dari 'pending_supervisor' menjadi 'pending_hr'.
     *
     * @throws DomainException
     */
    public function approveSupervisor(Video $video, User $actor, ?string $note = null): Video
    {
        if (! $video->isPendingSupervisor()) {
            throw new DomainException("Transisi status tidak valid: hanya video berstatus 'pending_supervisor' yang dapat disetujui atasan (status saat ini: {$video->status}).");
        }

        $isAuthorized = $actor->hasRole('admin') ||
            ($actor->hasRole('supervisor') && (int) $actor->division_id === (int) $video->division_id);

        if (! $isAuthorized) {
            throw new DomainException('Hanya Supervisor dari divisi yang sama atau Admin yang berhak menyetujui video pada tahap ini.');
        }

        return DB::transaction(function () use ($video, $actor, $note): Video {
            $video->update([
                'status' => 'pending_hr',
                'supervisor_reviewed_by' => $actor->id,
                'supervisor_reviewed_at' => now(),
                'supervisor_notes' => $note,
            ]);

            return $video->fresh();
        });
    }

    /**
     * Setujui video pada tahap 2: HR / Quality.
     * Mengubah status dari 'pending_hr' menjadi 'published'.
     * TIDAK BOLEH melompat langsung dari 'pending_supervisor'.
     *
     * Efek samping otomatis:
     * 1. Terbitkan entri knowledge_documents (type='video', status='published').
     * 2. Insert point_transactions untuk PEMBUAT video.
     * 3. Otomatis buat kuis post_test kosong (related_type='video', related_id=video.id).
     *
     * @throws DomainException
     */
    public function approveHr(Video $video, User $actor, ?string $note = null): Video
    {
        if (! $video->isPendingHr()) {
            throw new DomainException("Transisi status tidak valid: video harus berstatus 'pending_hr' sebelum disetujui HR/Quality. Tidak boleh melompati tahap supervisor (status saat ini: {$video->status}).");
        }

        if (! $actor->hasAnyRole(['quality', 'admin'])) {
            throw new DomainException('Hanya tim Quality/HR atau Admin yang berhak menyetujui video pada tahap akhir.');
        }

        return DB::transaction(function () use ($video, $actor, $note): Video {
            $video->update([
                'status' => 'published',
                'hr_reviewed_by' => $actor->id,
                'hr_reviewed_at' => now(),
                'hr_notes' => $note,
            ]);

            // a. Insert ke knowledge_documents (type='video', status='published'), link balik ke video ini
            KnowledgeDocument::create([
                'title' => $video->title,
                'division_id' => $video->division_id,
                'type' => 'video',
                'file_url' => $video->video_url,
                'external_link' => str_starts_with($video->video_url, 'http') ? $video->video_url : null,
                'description' => $video->description ?? "Video kontribusi: {$video->title}",
                'source_ba_id' => $video->ba_incident_id,
                'source_video_id' => $video->id,
                'created_by' => $video->created_by,
                'status' => 'published',
            ]);

            // b. Insert point_transactions untuk PEMBUAT video
            $pointAmount = $video->isMandatory()
                ? (int) config('kpi.points.mandatory_video_published', 100)
                : (int) config('kpi.points.voluntary_video_published', 100);

            $sourceType = $video->isMandatory()
                ? 'video_mandatory_published'
                : 'video_voluntary_published';

            PointTransaction::create([
                'user_id' => $video->created_by,
                'ledger_type' => PointTransaction::LEDGER_XP,
                'points' => $pointAmount,
                'source_type' => $sourceType,
                'source_id' => $video->id,
                'description' => "Poin publikasi video kontribusi: {$video->title}",
                'created_at' => now(),
            ]);

            // c. Kuis post_test terkait video (related_type='video')
            Quiz::firstOrCreate([
                'related_type' => 'video',
                'related_id' => $video->id,
                'type' => 'post_test',
            ], [
                'title' => 'Post-Test: '.$video->title,
                'description' => 'Evaluasi pemahaman untuk video: '.$video->title,
                'points_reward' => (int) config('kpi.points.video_post_test_passed', 25),
            ]);

            return $video->fresh();
        });
    }

    /**
     * Tolak video pada tahap aktif (supervisor atau hr).
     *
     * @throws DomainException
     */
    public function reject(Video $video, User $actor, string $reason): Video
    {
        if ($video->isPublished() || $video->isRejected()) {
            throw new DomainException("Video dengan status '{$video->status}' tidak dapat ditolak.");
        }

        // Cek wewenang penolakan sesuai tahap saat ini
        if ($video->isPendingSupervisor()) {
            $isAuthorized = $actor->hasRole('admin') ||
                ($actor->hasRole('supervisor') && (int) $actor->division_id === (int) $video->division_id);

            if (! $isAuthorized) {
                throw new DomainException('Hanya Supervisor dari divisi yang sama atau Admin yang dapat menolak pada tahap ini.');
            }
        } elseif ($video->isPendingHr()) {
            if (! $actor->hasAnyRole(['quality', 'admin'])) {
                throw new DomainException('Hanya Quality/HR atau Admin yang dapat menolak pada tahap HR.');
            }
        }

        return DB::transaction(function () use ($video, $actor, $reason): Video {
            $updateData = [
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ];

            if ($video->isPendingSupervisor()) {
                $updateData['supervisor_reviewed_by'] = $actor->id;
                $updateData['supervisor_reviewed_at'] = now();
                $updateData['supervisor_notes'] = $reason;
            } elseif ($video->isPendingHr()) {
                $updateData['hr_reviewed_by'] = $actor->id;
                $updateData['hr_reviewed_at'] = now();
                $updateData['hr_notes'] = $reason;
            }

            $video->update($updateData);

            return $video->fresh();
        });
    }
}
