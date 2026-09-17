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
    'learning_category_id',
    'title',
    'type',
    'content_url',
    'description',
    'xp_reward',
    'source_ba_id',
    'status',
    'created_by',
])]
class LearningMaterial extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'xp_reward' => 'integer',
        ];
    }

    /**
     * Get the category that the learning material belongs to.
     *
     * @return BelongsTo<LearningCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class, 'learning_category_id');
    }

    /**
     * Get the BA incident source for this material (if candidate from BA).
     *
     * @return BelongsTo<BaIncident, $this>
     */
    public function sourceBa(): BelongsTo
    {
        return $this->belongsTo(BaIncident::class, 'source_ba_id');
    }

    /**
     * Get the user who created the material.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the progress records for this material.
     *
     * @return HasMany<UserLearningProgress, $this>
     */
    public function progresses(): HasMany
    {
        return $this->hasMany(UserLearningProgress::class);
    }

    /**
     * Get the post-test quiz associated with this learning material.
     * Sesuai Data Contract §1: type=post_test, related_type=learning_material.
     *
     * @return HasOne<Quiz, $this>
     */
    public function postTest(): HasOne
    {
        return $this->hasOne(Quiz::class, 'related_id')
            ->where('type', 'post_test')
            ->where('related_type', 'learning_material');
    }

    /**
     * Get the authenticated user's progress for this material.
     *
     * @return HasOne<UserLearningProgress, $this>
     */
    public function currentUserProgress(): HasOne
    {
        return $this->hasOne(UserLearningProgress::class, 'learning_material_id')
            ->where('user_id', auth()->id());
    }

    /**
     * Get the user's progress for this material.
     */
    public function getProgressForUser(?User $user): ?UserLearningProgress
    {
        if (! $user) {
            return null;
        }

        if ($this->relationLoaded('progresses')) {
            return $this->progresses->firstWhere('user_id', $user->id);
        }

        return $this->progresses()->where('user_id', $user->id)->first();
    }

    /**
     * Scope query to only published materials.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
