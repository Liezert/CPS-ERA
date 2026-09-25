<?php

namespace Tests\Feature;

use App\Filament\Resources\BaIncidents\Pages\ViewBaIncident;
use App\Livewire\Ba\Show as BaShow;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\Notification;
use App\Models\User;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Stage 6 alur approval CAPA: riwayat aktivitas (audit trail) hanya untuk reviewer, sementara
 * pelapor tetap melihat alasan penolakan lewat banner "Catatan Reviewer".
 */
class BaActivityLogAccessTest extends TestCase
{
    use RefreshDatabase;

    private const REVISION_NOTE = 'Lengkapi hasil pengukuran suhu sebelum dan sesudah perbaikan.';

    private BaIncident $incident;

    private User $reporter;

    private User $coworker;

    private User $supervisor;

    private User $quality;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $produksi = Division::where('name', 'Produksi')->firstOrFail();
        $engineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->reporter = $this->userWithRole('employee', $produksi);
        $this->coworker = $this->userWithRole('employee', $produksi);
        $this->supervisor = $this->userWithRole('supervisor', $produksi);
        $this->quality = $this->userWithRole('quality', $engineering);
        $this->admin = $this->userWithRole('admin', $engineering);

        // Laporan yang diminta revisi: punya riwayat aktivitas sekaligus catatan penolakan.
        $service = app(BaIncidentService::class);
        $draft = $service->saveDraft([
            'division_id' => $produksi->id,
            'deskripsi_masalah' => 'Roller conveyor macet.',
        ], $this->reporter);
        $submitted = $service->submitWithVideo($draft, $this->reporter, ['video_external_link' => 'https://vimeo.com/123456789']);
        $this->incident = $service->reject($submitted, $this->supervisor, self::REVISION_NOTE);
    }

    private function userWithRole(string $role, Division $division): User
    {
        $user = User::factory()->create(['division_id' => $division->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_reporter_and_coworker_see_the_reviewer_note_but_not_the_activity_timeline(): void
    {
        foreach ([$this->reporter, $this->coworker] as $employee) {
            $this->assertFalse($employee->can('viewActivityLog', $this->incident));

            Livewire::actingAs($employee)
                ->test(BaShow::class, ['incident' => $this->incident])
                ->assertSee('Catatan Reviewer:')
                ->assertSee(self::REVISION_NOTE)
                ->assertDontSee('Update History (Riwayat Aktivitas)')
                ->assertDontSee('BA Ditolak Supervisor');
        }
    }

    public function test_reviewers_see_the_activity_timeline_on_the_detail_page(): void
    {
        foreach ([$this->supervisor, $this->quality, $this->admin] as $reviewer) {
            $this->assertTrue($reviewer->can('viewActivityLog', $this->incident));

            Livewire::actingAs($reviewer)
                ->test(BaShow::class, ['incident' => $this->incident])
                ->assertSee('Update History (Riwayat Aktivitas)')
                ->assertSee('BA Ditolak Supervisor — Revisi Diminta')
                ->assertSee('BA &amp; Video Diserahkan', false);
        }
    }

    public function test_supervisor_of_another_division_cannot_view_the_activity_log(): void
    {
        $otherSupervisor = $this->userWithRole('supervisor', Division::where('name', 'Engineering')->firstOrFail());

        $this->assertFalse($otherSupervisor->can('viewActivityLog', $this->incident));
    }

    public function test_filament_review_page_shows_the_activity_timeline(): void
    {
        Livewire::actingAs($this->quality)
            ->test(ViewBaIncident::class, ['record' => $this->incident->getRouteKey()])
            ->assertSee('Update History (Riwayat Aktivitas)')
            ->assertSee('BA Ditolak Supervisor — Revisi Diminta')
            ->assertSee(self::REVISION_NOTE);
    }

    public function test_reporter_is_notified_when_supervisor_requests_revision(): void
    {
        $notification = Notification::where('user_id', $this->reporter->id)->where('type', 'ba_revisi')->sole();

        $this->assertSame('BA Perlu Revisi: '.$this->incident->nomor_ba, $notification->title);
        $this->assertStringContainsString(self::REVISION_NOTE, $notification->message);
        $this->assertSame((string) $this->incident->id, $notification->related_id);

        // Hanya pelapor yang diberi tahu soal permintaan revisi.
        $this->assertSame(1, Notification::where('type', 'ba_revisi')->count());
    }
}
