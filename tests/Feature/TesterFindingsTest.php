<?php

namespace Tests\Feature;

use App\Filament\Resources\LearningMaterials\Pages\CreateLearningMaterial;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Livewire\Ba\Create as BaCreate;
use App\Livewire\Learning\PostTest as LearningPostTest;
use App\Livewire\Profile\Index as ProfileIndex;
use App\Livewire\Video\Create as VideoCreate;
use App\Models\Achievement;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Notification;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Services\BaIncidentService;
use App\Services\VideoApprovalService;
use Carbon\Carbon;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Perbaikan temuan uji klien 2026-09-30: XP post-test, notifikasi pelapor/pengunggah,
 * validasi tautan video, email profil, dan tampilan jam WIB.
 */
class TesterFindingsTest extends TestCase
{
    use RefreshDatabase;

    private Division $produksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->produksi = Division::where('name', 'Produksi')->firstOrFail();
    }

    private function user(string $role, ?Division $division = null): User
    {
        $user = User::factory()->create(['division_id' => ($division ?? $this->produksi)->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_passing_post_test_awards_its_xp_once(): void
    {
        $employee = $this->user('employee');
        $material = LearningMaterial::create([
            'learning_category_id' => LearningCategory::create(['name' => 'K3', 'created_by' => $employee->id])->id,
            'title' => 'Nozzle', 'type' => 'video', 'status' => 'published', 'created_by' => $employee->id,
        ]);
        $quiz = Quiz::create(['title' => 'Post-Test Nozzle', 'type' => 'post_test', 'related_type' => 'learning_material', 'related_id' => $material->id, 'points_reward' => 20]);
        $question = $quiz->questions()->create(['question_text' => 'APD?', 'order_index' => 1, 'allow_multiple_answers' => false]);
        $correct = $question->options()->create(['option_text' => 'Sarung tangan', 'is_correct' => true]);
        $question->options()->create(['option_text' => 'Tidak perlu', 'is_correct' => false]);
        UserLearningProgress::create(['user_id' => $employee->id, 'learning_material_id' => $material->id, 'progress_percent' => 100, 'completed_at' => now()]);

        foreach ([1, 2] as $attempt) {
            Livewire::actingAs($employee)
                ->test(LearningPostTest::class, ['material' => $material])
                ->call('selectOption', $question->id, $correct->id)
                ->call('submitPostTest')
                ->assertSet('passed', true);
        }

        $this->assertSame(20, (int) PointTransaction::where('user_id', $employee->id)->where('ledger_type', PointTransaction::LEDGER_XP)->sum('points'));
        $this->assertSame(20, $employee->fresh()->xp);
    }

    public function test_reporter_is_notified_when_capa_is_approved_by_supervisor_and_hr(): void
    {
        $reporter = $this->user('employee');
        $supervisor = $this->user('supervisor');
        $hr = $this->user('quality', Division::where('name', 'HRGA')->firstOrFail());
        $service = app(BaIncidentService::class);

        $incident = $service->saveDraft(['division_id' => $this->produksi->id, 'deskripsi_masalah' => 'Label miring.'], $reporter);
        $incident = $service->submit($incident, $reporter);
        $incident = $service->approveAsSupervisor($incident, $supervisor);
        $service->approve($incident, $hr, ['status_verifikasi' => 'efektif', 'bukti_objektif' => 'Sudah dicek.']);

        $titles = Notification::where('user_id', $reporter->id)->where('type', 'ba_disetujui')->orderBy('created_at')->orderBy('id')->pluck('title')->all();
        $this->assertSame(['BA Disetujui Supervisor: '.$incident->nomor_ba, 'BA Disetujui Final: '.$incident->nomor_ba], $titles);
    }

    public function test_uploader_is_notified_on_video_decision_and_links_must_be_drive_or_onedrive(): void
    {
        $uploader = $this->user('employee');
        $hr = $this->user('quality', Division::where('name', 'HRGA')->firstOrFail());
        $service = app(VideoApprovalService::class);

        Livewire::actingAs($uploader)
            ->test(VideoCreate::class)
            ->set('title', 'Video YouTube')
            ->set('videoMethod', 'link')
            ->set('videoExternalLink', 'https://www.youtube.com/watch?v=abc')
            ->call('submit')
            ->assertHasErrors(['videoExternalLink' => 'regex']);

        $approved = $service->submit($uploader, ['title' => 'Video A', 'video_external_link' => 'https://drive.google.com/file/d/1VideoA/view']);
        $rejected = $service->submit($uploader, ['title' => 'Video B', 'video_external_link' => 'https://1drv.ms/v/s!VideoB']);
        $service->approve($approved, $hr);
        $service->reject($rejected, $hr, 'Audio tidak jelas.');

        $this->assertSame(['video_disetujui', 'video_ditolak'], Notification::where('user_id', $uploader->id)->orderBy('created_at')->orderBy('id')->pluck('type')->all());
        $this->assertStringContainsString('Audio tidak jelas.', Notification::where('type', 'video_ditolak')->value('message'));
    }

    public function test_employee_cannot_change_own_login_email(): void
    {
        $employee = $this->user('employee');
        $originalEmail = $employee->email;

        Livewire::actingAs($employee)
            ->test(ProfileIndex::class)
            ->set('name', 'Nama Baru')
            ->set('email', 'penyusup@contoh.test')
            ->call('updateProfileInformation');

        $this->assertSame('Nama Baru', $employee->fresh()->name);
        $this->assertSame($originalEmail, $employee->fresh()->email);
    }

    public function test_achievement_feature_is_hidden_while_switched_off(): void
    {
        $employee = $this->user('employee');
        Achievement::create(['name' => 'Pelapor Teladan', 'description' => 'Uji', 'icon' => 'badge-check']);

        config(['app.achievements_enabled' => false]);
        $this->actingAs($employee)->get(route('achievements.index'))->assertNotFound();
        $this->actingAs($employee)->get(route('dashboard'))->assertOk()->assertDontSee(route('achievements.index'));

        config(['app.achievements_enabled' => true]);
        $this->actingAs($employee)->get(route('dashboard'))->assertSee(route('achievements.index'));
    }

    public function test_submitted_report_tells_the_list_page_to_clear_the_local_draft(): void
    {
        $reporter = $this->user('employee');

        Livewire::actingAs($reporter)
            ->test(BaCreate::class)
            ->set('lokasi', 'Gudang')
            ->set('deskripsiMasalah', 'Karung tanpa label.')
            ->set('why1', 'Label habis.')
            ->set('kesimpulanAkarMasalah', 'Stok label tidak dipantau.')
            ->set('koreksiDeskripsi', 'Beri label manual.')
            ->set('korektifDeskripsi', 'Tambah stok minimum label.')
            ->call('submitReport')
            ->assertHasNoErrors();

        $this->assertSame((string) BaIncident::firstOrFail()->id, session('capa_draft_clear'));
        $this->actingAs($reporter)->get(route('ba.index'))->assertSee('cps-era:capa-draft:'.$reporter->id.':', false);
    }

    public function test_material_url_can_be_typed_without_breaking_the_file_upload_field(): void
    {
        $admin = $this->user('admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $category = LearningCategory::create(['name' => 'K3', 'created_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(CreateLearningMaterial::class)
            ->fillForm(['learning_category_id' => $category->id, 'title' => 'SOP Tautan', 'type' => 'link', 'status' => 'published'])
            ->set('data.content_url', 'https://drive.google.com/file/d/1SopTautan/view')
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('https://drive.google.com/file/d/1SopTautan/view', LearningMaterial::where('title', 'SOP Tautan')->value('content_url'));
    }

    public function test_admin_deletes_an_account_only_after_retyping_its_name(): void
    {
        $admin = $this->user('admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $target = User::factory()->create(['name' => 'Budi Keluar', 'division_id' => $this->produksi->id]);
        $target->assignRole('employee');
        $author = $this->user('employee');
        app(BaIncidentService::class)->saveDraft(['division_id' => $this->produksi->id, 'deskripsi_masalah' => 'Label miring.'], $author);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('deleteUser', $target, ['confirm_name' => 'budi'])
            ->assertHasTableActionErrors(['confirm_name']);
        $this->assertNotNull($target->fresh());

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('deleteUser', $target, ['confirm_name' => 'Budi Keluar'])
            ->assertHasNoTableActionErrors();
        $this->assertNull($target->fresh());

        // Akun yang punya laporan tidak bisa dihapus (jejak audit); admin tidak bisa menghapus dirinya.
        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('deleteUser', $author, ['confirm_name' => $author->name])
            ->assertNotified('Akun tidak bisa dihapus')
            ->assertTableActionHidden('deleteUser', $admin);
        $this->assertNotNull($author->fresh());
    }

    public function test_changing_own_password_does_not_log_the_admin_out_of_the_panel(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/admin/knowledge-topics')->assertOk();
        $this->assertTrue(session()->has('password_hash_web'));

        $admin->update(['password' => 'KataSandiBaru#2026']);

        $this->get('/admin/knowledge-topics')->assertOk();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_knowledge_document_detail_links_its_file_and_messages_are_indonesian(): void
    {
        $admin = $this->user('admin');
        $document = \App\Models\KnowledgeDocument::create([
            'title' => 'SOP Berkas', 'type' => 'dokumen', 'status' => 'published', 'created_by' => $admin->id,
            'file_url' => 'https://contoh.test/sop.pdf',
        ]);

        $this->actingAs($admin)->get("/admin/knowledge-documents/{$document->id}")
            ->assertOk()->assertSee('Buka / unduh berkas')->assertSee('https://contoh.test/sop.pdf', false);

        $this->assertSame('id', app()->getLocale());
        $this->assertSame('Nama sudah dipakai.', __('validation.unique', ['attribute' => 'Nama']));
    }

    public function test_create_capa_button_is_hidden_for_admin_and_hrga(): void
    {
        foreach (['admin', 'quality'] as $role) {
            $reviewer = $this->user($role, Division::where('name', 'HRGA')->firstOrFail());
            $this->actingAs($reviewer)->get(route('dashboard'))->assertOk()->assertDontSee(route('ba.create'));
            $this->actingAs($reviewer)->get(route('ba.index'))->assertOk()->assertDontSee(route('ba.create'));
            $this->actingAs($reviewer)->get(route('ba.create'))->assertForbidden();
        }

        $employee = $this->user('employee');
        $this->actingAs($employee)->get(route('dashboard'))->assertSee(route('ba.create'));
        $this->actingAs($employee)->get(route('ba.index'))->assertSee(route('ba.create'));
    }

    public function test_timestamps_are_displayed_in_wib_but_stored_in_utc(): void
    {
        $moment = Carbon::parse('2026-09-30 00:23:00', 'UTC');

        $this->assertSame('07:23', $moment->wib()->format('H:i'));
        $this->assertSame('00:23', $moment->format('H:i'), 'wib() tidak boleh mengubah nilai asli.');
        $this->assertSame('UTC', config('app.timezone'));
    }
}
