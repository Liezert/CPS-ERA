<?php

namespace App\Providers;

use App\Models\BaIncident;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeTopic;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\Video;
use App\Observers\BaIncidentObserver;
use App\Observers\KnowledgeDocumentObserver;
use App\Observers\PointTransactionObserver;
use App\Observers\QuizObserver;
use App\Observers\UserAchievementObserver;
use App\Policies\BaIncidentPolicy;
use App\Policies\KnowledgeDocumentPolicy;
use App\Policies\KnowledgeTopicPolicy;
use App\Policies\LearningCategoryPolicy;
use App\Policies\LearningMaterialPolicy;
use App\Policies\QuizPolicy;
use App\Policies\VideoPolicy;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PointTransaction::observe(PointTransactionObserver::class);
        UserAchievement::observe(UserAchievementObserver::class);
        KnowledgeDocument::observe(KnowledgeDocumentObserver::class);
        BaIncident::observe(BaIncidentObserver::class);
        Quiz::observe(QuizObserver::class);

        // Timestamp disimpan & dihitung dalam UTC (app.timezone sengaja tetap UTC agar data lama tidak
        // bergeser); hanya tampilannya yang memakai WIB. Pemakaian: $model->created_at->wib()->format(...).
        foreach ([Carbon::class, CarbonImmutable::class] as $carbon) {
            $carbon::macro('wib', fn () => $this->copy()->setTimezone(config('kpi.timezone')));
        }
        FilamentTimezone::set(config('kpi.timezone'));

        Gate::policy(BaIncident::class, BaIncidentPolicy::class);
        Gate::policy(KnowledgeDocument::class, KnowledgeDocumentPolicy::class);
        Gate::policy(KnowledgeTopic::class, KnowledgeTopicPolicy::class);
        Gate::policy(LearningCategory::class, LearningCategoryPolicy::class);
        Gate::policy(LearningMaterial::class, LearningMaterialPolicy::class);
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(Video::class, VideoPolicy::class);

        // Implicitly grant "admin" role all permissions and gate checks
        Gate::before(function (User $user, string $ability): ?bool {
            // Approve/reject per tahap selalu diputuskan policy: status laporan dan tahapnya
            // tetap berlaku untuk admin (admin tidak boleh approve draf atau melompati tahap).
            // Sama untuk review video (VideoPolicy::review): tanpa ini admin tetap melihat panel review
            // setelah video tayang, dan bisa mereview unggahannya sendiri.
            if (in_array($ability, [...BaIncidentPolicy::STAGE_REVIEW_ABILITIES, 'review'], true)) {
                return null;
            }

            return $user->hasRole('admin') ? true : null;
        });

        // Role-based gates
        Gate::define('admin', fn (User $user): bool => $user->hasRole('admin'));
        Gate::define('supervisor', fn (User $user): bool => $user->hasRole('supervisor'));
        Gate::define('quality', fn (User $user): bool => $user->hasRole('quality'));
        Gate::define('employee', fn (User $user): bool => $user->hasRole('employee'));

        // Admin panel access gate
        Gate::define('access-admin-panel', fn (User $user): bool => $user->hasAnyRole(['admin', 'supervisor', 'quality']));

        // Gates PRD v2.0 §2.2 (Access Matrix)
        $isQualityOrHrga = function (User $user): bool {
            return $user->hasAnyRole(['admin', 'quality', 'hrga'])
                || ($user->relationLoaded('division') ? $user->division?->name === 'HRGA' : $user->division()->where('name', 'HRGA')->exists());
        };

        Gate::define('manage-knowledge-topics', fn (User $user): bool => $isQualityOrHrga($user));
        Gate::define('manage-knowledge-documents', fn (User $user): bool => $isQualityOrHrga($user));
        Gate::define('manage-learning-materials', fn (User $user): bool => $isQualityOrHrga($user));
        Gate::define('view-kpi-summary', fn (User $user): bool => $isQualityOrHrga($user));
        Gate::define('adjust-xp-manual', fn (User $user): bool => $user->hasRole('admin'));
    }
}
