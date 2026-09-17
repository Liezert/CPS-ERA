<?php

use App\Http\Controllers\Api\BaIncidentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KnowledgeDocumentController;
use App\Http\Controllers\Api\KnowledgeTopicController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\LearningCategoryController;
use App\Http\Controllers\Api\LearningMaterialController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\UserBookmarkController;
use App\Http\Controllers\Api\UserXpAdjustmentController;
use App\Http\Controllers\Api\VideoController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Auth endpoints for API / Postman testing
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (! Auth::attempt($credentials)) {
        return response()->json([
            'success' => false,
            'message' => 'Email atau password salah.',
        ], 401);
    }

    $request->session()->regenerate();
    /** @var User $user */
    $user = Auth::user()->load('division', 'roles');

    return response()->json([
        'success' => true,
        'message' => 'Login berhasil.',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id,
            'division' => $user->division?->name,
            'roles' => $user->getRoleNames(),
        ],
    ]);
})->name('api.login');

Route::middleware(['auth'])->group(function () {
    Route::get('/me', function (Request $request) {
        /** @var User $user */
        $user = $request->user()->load('division', 'roles');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'division' => $user->division?->name,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    })->name('api.me');

    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    })->name('api.logout');

    // BA Incident endpoints
    Route::get('/ba-incidents', [BaIncidentController::class, 'index'])->name('api.ba-incidents.index');
    Route::post('/ba-incidents', [BaIncidentController::class, 'store'])->name('api.ba-incidents.store');
    Route::get('/ba-incidents/{id}', [BaIncidentController::class, 'show'])->name('api.ba-incidents.show');
    Route::patch('/ba-incidents/{id}/review', [BaIncidentController::class, 'review'])->name('api.ba-incidents.review');
    Route::patch('/ba-incidents/{id}/close', [BaIncidentController::class, 'close'])->name('api.ba-incidents.close');
    Route::get('/ba-incidents/{id}/activity-log', [BaIncidentController::class, 'activityLog'])->name('api.ba-incidents.activity-log');

    // Video Contribution endpoints (Double Verification & Post-Test Gate)
    Route::get('/videos', [VideoController::class, 'index'])->name('api.videos.index');
    Route::post('/videos', [VideoController::class, 'store'])->name('api.videos.store');
    Route::get('/videos/{id}', [VideoController::class, 'show'])->name('api.videos.show');
    Route::patch('/videos/{id}/approve-supervisor', [VideoController::class, 'approveSupervisor'])->name('api.videos.approve-supervisor');
    Route::patch('/videos/{id}/approve-hr', [VideoController::class, 'approveHr'])->name('api.videos.approve-hr');
    Route::patch('/videos/{id}/reject', [VideoController::class, 'reject'])->name('api.videos.reject');
    Route::post('/videos/{id}/view', [VideoController::class, 'recordView'])->name('api.videos.view');

    // Knowledge Repository endpoints (PRD v2.0 §3.2)
    Route::get('/knowledge-topics', [KnowledgeTopicController::class, 'index'])->name('api.knowledge-topics.index');
    Route::post('/knowledge-topics', [KnowledgeTopicController::class, 'store'])->name('api.knowledge-topics.store');
    Route::get('/knowledge-topics/{id}', [KnowledgeTopicController::class, 'show'])->name('api.knowledge-topics.show');
    Route::patch('/knowledge-topics/{id}', [KnowledgeTopicController::class, 'update'])->name('api.knowledge-topics.update');
    Route::delete('/knowledge-topics/{id}', [KnowledgeTopicController::class, 'destroy'])->name('api.knowledge-topics.destroy');

    Route::get('/knowledge-documents', [KnowledgeDocumentController::class, 'index'])->name('api.knowledge-documents.index');
    Route::post('/knowledge-documents', [KnowledgeDocumentController::class, 'store'])->name('api.knowledge-documents.store');
    Route::get('/knowledge-documents/{id}', [KnowledgeDocumentController::class, 'show'])->name('api.knowledge-documents.show');
    Route::patch('/knowledge-documents/{id}', [KnowledgeDocumentController::class, 'update'])->name('api.knowledge-documents.update');
    Route::delete('/knowledge-documents/{id}', [KnowledgeDocumentController::class, 'destroy'])->name('api.knowledge-documents.destroy');

    // Bookmark endpoints
    Route::post('/knowledge-documents/{id}/bookmark', [UserBookmarkController::class, 'store'])->name('api.knowledge-documents.bookmark.store');
    Route::delete('/knowledge-documents/{id}/bookmark', [UserBookmarkController::class, 'destroy'])->name('api.knowledge-documents.bookmark.destroy');
    Route::get('/me/bookmarks', [UserBookmarkController::class, 'index'])->name('api.me.bookmarks');

    // Learning endpoints
    Route::get('/learning-categories', [LearningCategoryController::class, 'index'])->name('api.learning-categories.index');
    Route::post('/learning-categories', [LearningCategoryController::class, 'store'])->name('api.learning-categories.store');
    Route::get('/learning-categories/{id}/materials', [LearningCategoryController::class, 'materials'])->name('api.learning-categories.materials');
    Route::post('/learning-materials', [LearningMaterialController::class, 'store'])->name('api.learning-materials.store');
    Route::get('/learning-materials/{id}', [LearningMaterialController::class, 'show'])->name('api.learning-materials.show');
    Route::patch('/learning-materials/{id}/progress', [LearningMaterialController::class, 'updateProgress'])->name('api.learning-materials.progress');
    Route::get('/me/learning-progress', [LearningMaterialController::class, 'myProgress'])->name('api.me.learning-progress');

    // Quiz & Mission endpoints
    Route::get('/quizzes', [QuizController::class, 'index'])->name('api.quizzes.index');
    Route::get('/quizzes/{id}', [QuizController::class, 'show'])->name('api.quizzes.show');
    Route::post('/quizzes/{id}/attempt', [QuizController::class, 'attempt'])->name('api.quizzes.attempt');

    // Leaderboard endpoint
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('api.leaderboard');

    // Notification & Achievement endpoints
    Route::get('/me/notifications', [NotificationController::class, 'index'])->name('api.me.notifications');
    Route::patch('/me/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.me.notifications.read');
    Route::get('/me/achievements', [NotificationController::class, 'myAchievements'])->name('api.me.achievements');

    // Dashboard & Profile endpoints
    Route::get('/dashboard/summary', [DashboardController::class, 'summary'])->name('api.dashboard.summary');
    Route::get('/me/profile', [ProfileController::class, 'show'])->name('api.me.profile.show');
    Route::patch('/me/profile', [ProfileController::class, 'update'])->name('api.me.profile.update');

    // Admin XP Adjustment endpoint (PRD v2.0 §2.2)
    Route::post('/admin/users/{id}/adjust-xp', [UserXpAdjustmentController::class, 'adjustXp'])->name('api.admin.users.adjust-xp');
});
