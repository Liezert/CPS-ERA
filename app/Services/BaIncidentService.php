<?php

namespace App\Services;

use App\Models\BaActivityLog;
use App\Models\BaIncident;
use App\Models\KnowledgeDocument;
use App\Models\User;
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
     * Create a new BA incident report with activity logging.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor, UploadedFile|string $fileBa, UploadedFile|string $fileFtk): BaIncident
    {
        $fileBaUrl = $this->resolveFileUrl($fileBa, 'ba-incidents/ba');
        $fileFtkUrl = $this->resolveFileUrl($fileFtk, 'ba-incidents/ftk');

        return DB::transaction(function () use ($data, $actor, $fileBa, $fileFtk, $fileBaUrl, $fileFtkUrl): BaIncident {
            $nomorBa = $this->generateNomorBa();

            $incident = BaIncident::create([
                'nomor_ba' => $nomorBa,
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'division_id' => $data['division_id'],
                'file_ba_url' => $fileBaUrl,
                'file_ftk_url' => $fileFtkUrl,
                'status' => 'created',
                'created_by' => $actor->id,
            ]);

            // Lampirkan DUA slot terpisah via Spatie MediaLibrary
            try {
                if ($fileBa instanceof UploadedFile) {
                    $mediaBa = $incident->addMedia($fileBa)->preservingOriginal()->toMediaCollection('ba_file');
                    $incident->update(['file_ba_url' => $mediaBa->getUrl()]);
                }
                if ($fileFtk instanceof UploadedFile) {
                    $mediaFtk = $incident->addMedia($fileFtk)->preservingOriginal()->toMediaCollection('ftk_file');
                    $incident->update(['file_ftk_url' => $mediaFtk->getUrl()]);
                }
            } catch (\Throwable $e) {
                // Silently fallback to URL storage
            }

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => 'BA dibuat',
                'note' => 'Laporan BA baru dibuat oleh '.$actor->name,
            ]);

            return $incident;
        });
    }

    /**
     * Transition status from 'created' to 'reviewed' with activity log and Lesson Learned creation.
     */
    public function review(BaIncident $incident, User $actor, ?string $note = null): BaIncident
    {
        if (! $incident->isCreated()) {
            throw new DomainException("Transisi status tidak valid: hanya BA dengan status 'created' yang dapat ditinjau (status saat ini: {$incident->status}).");
        }

        return DB::transaction(function () use ($incident, $actor, $note): BaIncident {
            $incident->update([
                'status' => 'reviewed',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            BaActivityLog::create([
                'ba_incident_id' => $incident->id,
                'actor_id' => $actor->id,
                'action' => 'Ditinjau oleh '.$actor->name,
                'note' => $note ?? 'BA telah ditinjau dan diverifikasi.',
            ]);

            // Efek samping PRD 3.1: Otomatis membuat entri Lesson Learned dari FTK
            KnowledgeDocument::create([
                'title' => 'Lesson Learned: '.$incident->nomor_ba,
                'division_id' => $incident->division_id,
                'type' => 'lesson_learned',
                'file_url' => $incident->file_ftk_url,
                'description' => 'Materi Lesson Learned otomatis dari formulir FTK BA insiden nomor '.$incident->nomor_ba,
                'source_ba_id' => $incident->id,
                'created_by' => $actor->id,
                'status' => 'published',
            ]);

            return $incident->fresh();
        });
    }

    /**
     * Transition status from 'reviewed' to 'closed' with activity log.
     */
    public function close(BaIncident $incident, User $actor, ?string $note = null): BaIncident
    {
        if (! $incident->isReviewed()) {
            throw new DomainException("Transisi status tidak valid: hanya BA dengan status 'reviewed' yang dapat ditutup (status saat ini: {$incident->status}).");
        }

        return DB::transaction(function () use ($incident, $actor, $note): BaIncident {
            $incident->update([
                'status' => 'closed',
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

    /**
     * Helper to resolve file URL from either an UploadedFile or string path.
     */
    protected function resolveFileUrl(UploadedFile|string $file, string $directory): string
    {
        if ($file instanceof UploadedFile) {
            $path = $file->store($directory, 'public');

            return Storage::disk('public')->url($path);
        }

        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://') || str_starts_with($file, '/storage/')) {
            return $file;
        }

        return Storage::disk('public')->url($file);
    }
}
