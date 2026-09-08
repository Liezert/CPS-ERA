<?php

namespace App\Models;

use Database\Factories\BaIncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'nomor_ba',
    'title',
    'description',
    'division_id',
    'file_ba_url',
    'file_ftk_url',
    'status',
    'created_by',
    'reviewed_by',
    'reviewed_at',
    'closed_at',
])]
class BaIncident extends Model implements HasMedia
{
    /** @use HasFactory<BaIncidentFactory> */
    use HasFactory, HasUuids, InteractsWithMedia;

    /**
     * Register media collections for separate BA and FTK files.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('ba_file')->singleFile();
        $this->addMediaCollection('ftk_file')->singleFile();
    }

    /**
     * Get the division that the incident belongs to.
     *
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the user who created the incident.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who reviewed the incident.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get all activity logs for the incident.
     *
     * @return HasMany<BaActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(BaActivityLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the lesson learned knowledge document generated from this incident.
     *
     * @return HasOne<KnowledgeDocument, $this>
     */
    public function lessonLearned(): HasOne
    {
        return $this->hasOne(KnowledgeDocument::class, 'source_ba_id');
    }

    /**
     * Determine if the incident is in 'created' status.
     */
    public function isCreated(): bool
    {
        return $this->status === 'created';
    }

    /**
     * Determine if the incident is in 'reviewed' status.
     */
    public function isReviewed(): bool
    {
        return $this->status === 'reviewed';
    }

    /**
     * Determine if the incident is in 'closed' status.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
