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

#[Fillable([
    'title',
    'division_id',
    'type',
    'file_url',
    'external_link',
    'description',
    'source_ba_id',
    'created_by',
    'status',
])]
class KnowledgeDocument extends Model
{
    /** @use HasFactory<KnowledgeDocumentFactory> */
    use HasFactory, HasUuids;

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
     * Scope query to only published documents.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope query by search keyword in title or description.
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (! $keyword || trim($keyword) === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%");
        });
    }

    /**
     * Scope query by multiple filters (q, category_id/division_id, type, status).
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['q'])) {
            $query->search($filters['q']);
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
