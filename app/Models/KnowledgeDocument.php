<?php

namespace App\Models;

use Database\Factories\KnowledgeDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'title',
    'division_id',
    'topic_id',
    'type',
    'file_url',
    'external_link',
    'description',
    'source_ba_id',
    'source_video_id',
    'created_by',
    'status',
])]
class KnowledgeDocument extends Model
{
    /** @use HasFactory<KnowledgeDocumentFactory> */
    use HasFactory, HasUuids;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'download_url',
    ];

    /**
     * Get the topic that the document belongs to.
     *
     * @return BelongsTo<KnowledgeTopic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(KnowledgeTopic::class, 'topic_id');
    }

    /**
     * Get the division that the document belongs to.
     *
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the user who created the document.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the BA incident that was the source for this document.
     *
     * @return BelongsTo<BaIncident, $this>
     */
    public function sourceBa(): BelongsTo
    {
        return $this->belongsTo(BaIncident::class, 'source_ba_id');
    }

    /**
     * Get the video that was the source for this document.
     *
     * @return BelongsTo<Video, $this>
     */
    public function sourceVideo(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'source_video_id');
    }

    /**
     * Get the bookmarks for this document.
     *
     * @return HasMany<UserBookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(UserBookmark::class);
    }

    /**
     * Get the users who bookmarked this document.
     *
     * @return BelongsToMany<User, $this>
     */
    public function bookmarkedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_bookmarks', 'knowledge_document_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Check if the document is bookmarked by a specific user.
     */
    public function isBookmarkedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('bookmarks')) {
            return $this->bookmarks->contains('user_id', $user->id);
        }

        return $this->bookmarks()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if this document is a lesson learned.
     */
    public function isLessonLearned(): bool
    {
        return $this->type === 'lesson_learned';
    }

    /**
     * Get the full downloadable URL for the attached file.
     */
    public function getDownloadUrlAttribute(): ?string
    {
        if (empty($this->file_url)) {
            return null;
        }

        if (str_starts_with($this->file_url, 'http://') || str_starts_with($this->file_url, 'https://')) {
            return $this->file_url;
        }

        if (str_starts_with($this->file_url, '/storage/')) {
            return asset(ltrim($this->file_url, '/'));
        }

        return Storage::disk('public')->url($this->file_url);
    }

    /**
     * Scope query to only published documents.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope query by search keyword in title, description, or topic name.
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (! $keyword || trim($keyword) === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%")
                ->orWhereHas('topic', function (Builder $tq) use ($keyword) {
                    $tq->where('name', 'like', "%{$keyword}%");
                });
        });
    }

    /**
     * Scope query by multiple filters (q, topic_id, category_id/division_id, type, status).
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['q'])) {
            $query->search($filters['q']);
        }

        if (! empty($filters['topic_id'])) {
            $query->where('topic_id', $filters['topic_id']);
        }

        $categoryId = $filters['category_id'] ?? $filters['division_id'] ?? null;
        if (! empty($categoryId)) {
            $query->where('division_id', $categoryId);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
