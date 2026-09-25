<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'employee_id',
    'division_id',
    'jabatan',
    'avatar_url',
    'xp',
    'must_change_password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the division that the user belongs to.
     *
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the bookmark records for the user.
     *
     * @return HasMany<UserBookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(UserBookmark::class);
    }

    /**
     * Get the knowledge documents bookmarked by the user.
     *
     * @return BelongsToMany<KnowledgeDocument, $this>
     */
    public function bookmarkedDocuments(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeDocument::class, 'user_bookmarks', 'user_id', 'knowledge_document_id')
            ->withTimestamps();
    }

    /**
     * Get the learning progress records for the user.
     *
     * @return HasMany<UserLearningProgress, $this>
     */
    public function learningProgresses(): HasMany
    {
        return $this->hasMany(UserLearningProgress::class);
    }

    /**
     * Get the learning materials tracked by the user.
     *
     * @return BelongsToMany<LearningMaterial, $this>
     */
    public function learningMaterials(): BelongsToMany
    {
        return $this->belongsToMany(LearningMaterial::class, 'user_learning_progress', 'user_id', 'learning_material_id')
            ->withPivot(['progress_percent', 'completed_at'])
            ->withTimestamps();
    }

    /**
     * Get the point transactions for the user.
     *
     * @return HasMany<PointTransaction, $this>
     */
    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * Get the yearly KPI progress records for the user.
     *
     * @return HasMany<UserKpiYearly, $this>
     */
    public function kpiYearlies(): HasMany
    {
        return $this->hasMany(UserKpiYearly::class);
    }

    /**
     * Dapatkan atau buat record KPI tahunan (lazy initialization).
     */
    public function getKpiYearly(?int $year = null): UserKpiYearly
    {
        $year = $year ?? (int) now()->year;

        return $this->kpiYearlies()->firstOrCreate(
            ['period_year' => $year],
            [
                'materials_completed_count' => 0,
                'poin_cps_era_earned' => 0,
                'poin_from_ba' => 0,
                'poin_from_materi' => 0,
            ]
        );
    }

    /**
     * Get the quiz attempts by the user.
     *
     * @return HasMany<QuizAttempt, $this>
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the notifications for the user.
     *
     * @return HasMany<Notification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the user achievement records for the user.
     *
     * @return HasMany<UserAchievement, $this>
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Get the achievements unlocked by the user.
     *
     * @return BelongsToMany<Achievement, $this>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements', 'user_id', 'achievement_id')
            ->withPivot(['id', 'unlocked_at'])
            ->withTimestamps();
    }

    /**
     * Get the videos created by the user.
     *
     * @return HasMany<Video, $this>
     */
    public function createdVideos(): HasMany
    {
        return $this->hasMany(Video::class, 'created_by');
    }

    /**
     * Get the video view records of the user.
     *
     * @return HasMany<UserVideoView, $this>
     */
    public function videoViews(): HasMany
    {
        return $this->hasMany(UserVideoView::class);
    }

    /**
     * Determine if the user can access the given panel.
     * Hanya role admin, supervisor, dan quality yang diizinkan mengakses panel Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['admin', 'supervisor', 'quality']);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'xp' => 'integer',
        ];
    }

    /**
     * Dapatkan inisial nama 2 karakter untuk avatar default.
     */
    public function getInitialsAttribute(): string
    {
        if (empty($this->name)) {
            return 'CP';
        }

        $parts = explode(' ', trim($this->name));
        $first = substr($parts[0], 0, 1);
        $second = isset($parts[1]) ? substr($parts[1], 0, 1) : '';

        return strtoupper($first.$second);
    }
}
