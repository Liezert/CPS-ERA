<?php

namespace Tests\Feature;

use App\Livewire\Video\Create as VideoCreate;
use App\Livewire\Video\Show as VideoShow;
use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserKpiYearly;
use App\Models\Video;
use App\Services\KpiContributionService;
use App\Services\VideoApprovalService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Video kontribusi (keputusan owner 2026-09-27): karyawan/supervisor mengunggah video, HANYA HR yang
 * menyetujui/menolak, video yang disetujui tampil di Learning untuk karyawan lain, dan pengunggah
 * mendapat 1 Poin CPS ERA (cap 3/tahun gabungan).
 */
class VideoContributionFlowTest extends TestCase
{
    use RefreshDatabase;

    private VideoApprovalService $service;

    private User $employee;

    private User $otherEmployee;

    private User $supervisor;

    private User $hr;

    private LearningCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $produksi = Division::where('name', 'Produksi')->firstOrFail();
        $engineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->employee = $this->userWithRole('employee', $produksi);
        $this->otherEmployee = $this->userWithRole('employee', $engineering);
        $this->supervisor = $this->userWithRole('supervisor', $produksi);
        $this->hr = $this->userWithRole('quality', $engineering);
        $this->category = LearningCategory::create(['name' => 'Tips Produksi', 'created_by' => $this->hr->id]);
        $this->service = app(VideoApprovalService::class);
    }

    private function userWithRole(string $role, Division $division): User
    {
        $user = User::factory()->create(['division_id' => $division->id]);
        $user->assignRole($role);

        return $user;
    }

    private function submit(?User $uploader = null, string $title = 'Cara Membersihkan Nozzle Injeksi'): Video
    {
        return $this->service->submit($uploader ?? $this->employee, [
            'title' => $title,
            'description' => 'Langkah aman membersihkan nozzle.',
            'learning_category_id' => $this->category->id,
            'video_external_link' => 'https://drive.google.com/file/d/1VideoKontribusiUji0001/view',
        ]);
    }

    private function videoPoints(User $user): int
    {
        return (int) PointTransaction::where('user_id', $user->id)
            ->where('ledger_type', PointTransaction::LEDGER_POIN_CPS_ERA)
            ->where('source_type', KpiContributionService::SOURCE_VIDEO_CONTRIBUTION)
            ->sum('points');
    }

    public function test_upload_goes_straight_to_hr_review_without_points(): void
    {
        $video = $this->submit();

        $this->assertSame('pending_hr', $video->status);
        $this->assertSame(VideoApprovalService::CREATION_REASON, $video->creation_reason);
        $this->assertSame($this->employee->division_id, $video->division_id);
        $this->assertSame(0, $this->videoPoints($this->employee));
        $this->assertDatabaseMissing('learning_materials', ['source_video_id' => $video->id]);
    }

    public function test_only_hr_can_decide_and_never_on_their_own_video(): void
    {
        $video = $this->submit();

        foreach ([$this->employee, $this->otherEmployee, $this->supervisor] as $notHr) {
            $this->assertThrows(fn () => $this->service->approve($video, $notHr), DomainException::class);
            $this->assertThrows(fn () => $this->service->reject($video, $notHr, 'Tidak relevan.'), DomainException::class);
        }
        $this->assertSame('pending_hr', $video->fresh()->status);

        // Anggota HR yang mengunggah video sendiri tidak boleh memutusnya.
        $ownVideo = $this->submit($this->hr, 'Video Milik HR');
        $this->assertThrows(fn () => $this->service->approve($ownVideo, $this->hr), DomainException::class);
        $this->assertSame('pending_hr', $ownVideo->fresh()->status);
    }

    public function test_hr_approval_publishes_to_learning_and_awards_one_cps_era_point_once(): void
    {
        $video = $this->submit();
        $xpBefore = (int) $this->employee->fresh()->xp;

        $video = $this->service->approve($video, $this->hr, 'Konten jelas.');

        $this->assertSame('published', $video->status);
        $this->assertSame($this->hr->id, $video->hr_reviewed_by);

        $material = LearningMaterial::where('source_video_id', $video->id)->sole();
        $this->assertSame('published', $material->status);
        $this->assertSame('video', $material->type);
        $this->assertSame($this->category->id, $material->learning_category_id);
        $this->assertSame($this->employee->id, $material->created_by);
        $this->assertSame('https://drive.google.com/file/d/1VideoKontribusiUji0001/preview', $material->drive_preview_url);

        $this->assertSame(1, $this->videoPoints($this->employee));
        $yearly = UserKpiYearly::where('user_id', $this->employee->id)->where('period_year', (int) now()->year)->sole();
        $this->assertSame(1, $yearly->poin_cps_era_earned);
        $this->assertSame(1, $yearly->poin_from_ba);
        $this->assertSame($xpBefore, (int) $this->employee->fresh()->xp);

        // Keputusan kedua ditolak: tidak ada materi atau poin ganda.
        $this->assertThrows(fn () => $this->service->approve($video, $this->hr), DomainException::class);
        $this->assertSame(1, LearningMaterial::where('source_video_id', $video->id)->count());
        $this->assertSame(1, $this->videoPoints($this->employee));
    }

    public function test_yearly_cap_of_three_points_still_publishes_the_video(): void
    {
        UserKpiYearly::create([
            'user_id' => $this->employee->id,
            'period_year' => (int) now()->year,
            'materials_completed_count' => 0,
            'poin_cps_era_earned' => 3,
            'poin_from_ba' => 1,
            'poin_from_materi' => 2,
        ]);

        $video = $this->service->approve($this->submit(), $this->hr);

        $this->assertSame('published', $video->status);
        $this->assertSame(0, $this->videoPoints($this->employee));
        $this->assertSame(3, UserKpiYearly::where('user_id', $this->employee->id)->value('poin_cps_era_earned'));
    }

    public function test_hr_rejection_requires_reason_and_awards_nothing(): void
    {
        $video = $this->submit();

        $this->assertThrows(fn () => $this->service->reject($video, $this->hr, '  '), DomainException::class);

        $video = $this->service->reject($video, $this->hr, 'Audio tidak jelas, mohon rekam ulang.');

        $this->assertSame('rejected', $video->status);
        $this->assertSame('Audio tidak jelas, mohon rekam ulang.', $video->rejection_reason);
        $this->assertSame(0, $this->videoPoints($this->employee));
        $this->assertDatabaseMissing('learning_materials', ['source_video_id' => $video->id]);
    }

    public function test_pending_video_is_private_until_published_then_visible_in_learning(): void
    {
        $video = $this->submit();

        $this->actingAs($this->otherEmployee)->get(route('videos.show', $video))->assertForbidden();
        $this->actingAs($this->employee)->get(route('videos.show', $video))->assertOk()->assertSee('Menunggu Review HR');
        $this->actingAs($this->hr)->get(route('videos.show', $video))->assertOk()->assertSee('Keputusan Review HR');

        $this->service->approve($video, $this->hr);

        $this->actingAs($this->otherEmployee)->get(route('learning.index'))
            ->assertOk()
            ->assertSee('Cara Membersihkan Nozzle Injeksi');
        $this->actingAs($this->otherEmployee)->get(route('videos.show', $video))->assertOk();
    }

    public function test_upload_form_submits_to_hr_and_redirects_to_the_video(): void
    {
        Livewire::actingAs($this->supervisor)
            ->test(VideoCreate::class)
            ->assertSeeHtml('x-on:livewire-upload-progress="progress = $event.detail.progress"')
            ->set('title', 'Pengecekan Harian Mesin Press')
            ->set('learningCategoryId', $this->category->id)
            ->set('videoMethod', 'link')
            ->set('videoExternalLink', 'https://drive.google.com/file/d/1VideoSupervisorUji00001/view')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('videos.show', Video::where('created_by', $this->supervisor->id)->sole()));

        $this->assertSame('pending_hr', Video::where('created_by', $this->supervisor->id)->value('status'));
    }

    public function test_upload_form_validates_title_and_video(): void
    {
        Livewire::actingAs($this->employee)
            ->test(VideoCreate::class)
            ->call('submit')
            ->assertHasErrors(['title' => 'required', 'videoFile' => 'required']);

        Livewire::actingAs($this->employee)
            ->test(VideoCreate::class)
            ->set('title', 'Judul Video Uji')
            ->set('videoMethod', 'link')
            ->set('videoExternalLink', 'bukan-url')
            ->call('submit')
            ->assertHasErrors(['videoExternalLink' => 'url']);

        $this->assertDatabaseCount('videos', 0);
    }

    public function test_hr_decides_from_the_video_page(): void
    {
        $video = $this->submit();

        Livewire::actingAs($this->otherEmployee)
            ->test(VideoShow::class, ['video' => $this->service->approve($this->submit(title: 'Video Lain'), $this->hr)])
            ->assertDontSee('Keputusan Review HR')
            ->call('approve')
            ->assertForbidden();

        Livewire::actingAs($this->hr)
            ->test(VideoShow::class, ['video' => $video])
            ->set('alasanPenolakan', '')
            ->call('reject')
            ->assertHasErrors(['alasanPenolakan' => 'required'])
            ->set('catatanHr', 'Mantap.')
            ->call('approve')
            ->assertHasNoErrors()
            ->assertRedirect(route('videos.show', $video));

        $this->assertSame('published', $video->fresh()->status);
    }

    public function test_learning_page_has_upload_button_and_hr_dashboard_counts_pending_videos(): void
    {
        $this->actingAs($this->employee)->get(route('learning.index'))
            ->assertOk()
            ->assertSee('Unggah Video')
            ->assertSee(route('videos.create'), false);

        $video = $this->submit();

        // HR melihat video di antreannya sendiri, dengan format item yang sama seperti laporan CAPA.
        $this->actingAs($this->hr)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-queue="video"', false)
            ->assertSee('Cara Membersihkan Nozzle Injeksi')
            ->assertSee(route('videos.show', $video).'#panel-review', false);
        $this->actingAs($this->employee)->get(route('dashboard'))
            ->assertDontSee('data-queue="video"', false);
    }

    public function test_admin_review_panel_disappears_after_publish_and_offers_post_test(): void
    {
        $admin = $this->userWithRole('admin', Division::where('name', 'HRGA')->firstOrFail());
        $video = $this->submit();
        $this->assertTrue($admin->can('review', $video));

        $video = $this->service->approve($video, $this->hr);

        // Admin tidak lagi melewati aturan status lewat Gate::before: panel review hilang setelah tayang.
        $this->assertFalse($admin->can('review', $video));
        $this->assertFalse($admin->can('review', $this->submit($admin, 'Video Admin Sendiri')));

        Livewire::actingAs($admin)
            ->test(VideoShow::class, ['video' => $video])
            ->assertDontSee('Keputusan Review HR')
            ->assertSee('Buat Soal Post-Test')
            ->assertSee(route('learning.post-test.edit', $video->learningMaterial), false);
    }
}
