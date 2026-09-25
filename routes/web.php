<?php

use App\Http\Controllers\Admin\GoogleDriveOAuthController;
use App\Livewire\Achievement\Index as AchievementIndex;
use App\Livewire\Ba\Create as BaCreate;
use App\Livewire\Ba\Index as BaIndex;
use App\Livewire\Ba\Show as BaShow;
use App\Livewire\Dashboard;
use App\Livewire\Knowledge\Index as KnowledgeIndex;
use App\Livewire\Leaderboard\Index as LeaderboardIndex;
use App\Livewire\Learning\Index as LearningIndex;
use App\Livewire\Learning\PostTest as LearningPostTest;
use App\Livewire\Learning\Show as LearningShow;
use App\Livewire\Mission\Index as MissionIndex;
use App\Livewire\Mission\Show as MissionShow;
use App\Livewire\Profile\Index as ProfileIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/knowledge', KnowledgeIndex::class)->name('knowledge.index');
    Route::get('/ba-incidents', BaIndex::class)->name('ba.index');
    Route::get('/ba-incidents/create', BaCreate::class)->name('ba.create');
    Route::get('/ba-incidents/{incident}', BaShow::class)->name('ba.show');
    Route::get('/learning', LearningIndex::class)->name('learning.index');
    Route::get('/learning/{material}', LearningShow::class)->name('learning.show');
    Route::get('/learning/{material}/post-test', LearningPostTest::class)->name('learning.post-test');
    Route::get('/missions', MissionIndex::class)->name('missions.index');
    Route::get('/missions/{quiz}', MissionShow::class)->name('missions.show');
    Route::get('/leaderboard', LeaderboardIndex::class)->name('leaderboard.index');
    Route::get('/achievements', AchievementIndex::class)->name('achievements.index');
    Route::get('/profile', ProfileIndex::class)->name('profile.edit');
});

// Alur consent OAuth Google Drive — hanya admin (di luar resource Filament).
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/google-drive')
    ->name('admin.google-drive.')
    ->group(function () {
        Route::get('/connect', [GoogleDriveOAuthController::class, 'connect'])->name('connect');
        Route::get('/callback', [GoogleDriveOAuthController::class, 'callback'])->name('callback');
    });

require __DIR__.'/auth.php';
