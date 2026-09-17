<?php

use App\Http\Controllers\ProfileController;
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
use App\Models\User;
use App\Policies\DivisionScopedPolicy;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/components-preview', function () {
    return view('components-preview');
})->name('components.preview');

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
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/test-access', function (Request $request) {
        /** @var User $user */
        $user = $request->user()->load('division', 'roles');

        $policy = new DivisionScopedPolicy;

        $sameDivisionRecord = (object) [
            'id' => 101,
            'title' => 'Laporan BA Insiden Divisi '.($user->division?->name ?? 'User'),
            'division_id' => $user->division_id,
        ];

        $otherDivisionRecord = (object) [
            'id' => 102,
            'title' => 'Laporan BA Insiden Divisi Luar',
            'division_id' => $user->division_id ? $user->division_id + 999 : 999,
        ];

        $adminPanel = Filament::getPanel('admin');

        $rbacReport = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'jabatan' => $user->jabatan,
                'division' => $user->division ? $user->division->name : 'Belum dihubungkan ke divisi',
                'roles' => $user->getRoleNames(),
            ],
            'gates' => [
                'admin' => Gate::allows('admin'),
                'supervisor' => Gate::allows('supervisor'),
                'quality' => Gate::allows('quality'),
                'employee' => Gate::allows('employee'),
                'access-admin-panel' => Gate::allows('access-admin-panel'),
            ],
            'filament_access' => $user->canAccessPanel($adminPanel),
            'policy_simulation' => [
                'same_division' => [
                    'record_title' => $sameDivisionRecord->title,
                    'can_view' => $policy->view($user, $sameDivisionRecord),
                    'can_update' => $policy->update($user, $sameDivisionRecord),
                    'can_review' => $policy->review($user, $sameDivisionRecord),
                ],
                'other_division' => [
                    'record_title' => $otherDivisionRecord->title,
                    'can_view' => $policy->view($user, $otherDivisionRecord),
                    'can_update' => $policy->update($user, $otherDivisionRecord),
                    'can_review' => $policy->review($user, $otherDivisionRecord),
                ],
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($rbacReport);
        }

        return view('test-access', compact('user', 'rbacReport'));
    })->name('test.access');
});

// Area Khusus Quality & Admin (Design System §8 & PRD §2.2)
Route::middleware(['auth', 'role:quality|admin'])->prefix('management')->name('management.')->group(function () {
    Route::get('/learning-categories', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Akses terverifikasi untuk role Quality dan Admin.',
        ]);
    })->name('learning-categories');
});

require __DIR__.'/auth.php';
