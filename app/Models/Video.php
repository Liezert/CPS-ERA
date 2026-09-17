<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'title',
    'description',
    'video_url',
    'video_file_url',
    'video_external_link',
    'division_id',
    'created_by',
    'creation_reason',
    'ba_incident_id',
    'status',
    'supervisor_reviewed_by',
    'supervisor_reviewed_at',
    'supervisor_notes',
    'hr_reviewed_by',
    'hr_reviewed_at',
    'hr_notes',
    'rejection_reason',
])]
class Video extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'supervisor_reviewed_at' => 'datetime',
            'hr_reviewed_at' => 'datetime',
        ];
    }

    /**
     * User yang mengunggah / membuat video.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Divisi yang terkait dengan video.
     *
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Laporan BA insiden yang menjadi dasar video wajib (Jalur A).
     *
     * @return BelongsTo<BaIncident, $this>
     */
    public function baIncident(): BelongsTo
    {
        return $this->belongsTo(BaIncident::class, 'ba_incident_id');
    }

    /**
     * Supervisor yang mereview pada tahap 1.
     *
     * @return BelongsTo<User, $this>
     */
    public function supervisorReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_reviewed_by');
    }

    /**
     * HR / Quality yang mereview pada tahap 2.
     *
     * @return BelongsTo<User, $this>
     */
    public function hrReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_reviewed_by');
    }

    /**
     * Kuis Post-Test terkait video ini.
     *
     * @return HasOne<Quiz, $this>
     */
    public function postTestQuiz(): HasOne
    {
        return $this->hasOne(Quiz::class, 'related_id')
            ->where('type', 'post_test')
            ->where('related_type', 'video');
    }

    /**
     * Riwayat view/tontonan pengguna terhadap video ini.
     *
     * @return HasMany<UserVideoView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(UserVideoView::class);
    }

    /**
     * Dokumen pengetahuan yang terbit otomatis saat video dipublikasikan.
     *
     * @return HasOne<KnowledgeDocument, $this>
     */
    public function knowledgeDocument(): HasOne
    {
        return $this->hasOne(KnowledgeDocument::class, 'source_video_id');
    }

    /**
     * Periksa apakah video merupakan Jalur Wajib (akibat insiden).
     */
    public function isMandatory(): bool
    {
        return $this->creation_reason === 'mandatory_incident';
    }

    /**
     * Periksa apakah video merupakan Jalur Sukarela (improvement).
     */
    public function isVoluntary(): bool
    {
        return $this->creation_reason === 'voluntary_improvement';
    }

    /**
     * Periksa apakah sedang menunggu verifikasi Supervisor.
     */
    public function isPendingSupervisor(): bool
    {
        return $this->status === 'pending_supervisor';
    }

    /**
     * Periksa apakah sedang menunggu verifikasi HR/Quality.
     */
    public function isPendingHr(): bool
    {
        return $this->status === 'pending_hr';
    }

    /**
     * Periksa apakah video telah diterbitkan (tayang).
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Periksa apakah video ditolak.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Scope untuk mengambil video yang sudah diterbitkan.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
