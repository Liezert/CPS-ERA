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
    'type',
    'related_type',
    'related_id',
    'points_reward',
    'description',
])]
class Quiz extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the questions for this quiz.
     *
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order_index');
    }

    /**
     * Get the user attempts for this quiz.
     *
     * @return HasMany<QuizAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the related learning material if applicable.
     *
     * @return BelongsTo<LearningMaterial, $this>
     */
    public function relatedLearningMaterial(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class, 'related_id');
    }

    /**
     * Get the latest attempt by the currently authenticated user.
     *
     * @return HasOne<QuizAttempt, $this>
     */
    public function currentUserAttempt(): HasOne
    {
        return $this->hasOne(QuizAttempt::class)
            ->where('user_id', auth()->id())
            ->latestOfMany('attempted_at');
    }

    /**
     * Scope query untuk hanya mengambil misi (Case Study dan Quiz Cepat).
     */
    public function scopeMissions(Builder $query): Builder
    {
        return $query->whereIn('type', ['mission_case_study', 'mission_quiz']);
    }

    /**
     * Periksa apakah misi bertipe Studi Kasus.
     */
    public function isCaseStudy(): bool
    {
        return $this->type === 'mission_case_study';
    }

    /**
     * Periksa apakah misi bertipe Quiz Cepat.
     */
    public function isQuiz(): bool
    {
        return $this->type === 'mission_quiz';
    }

    /**
     * Periksa apakah bertipe Post-Test.
     */
    public function isPostTest(): bool
    {
        return $this->type === 'post_test';
    }
}
