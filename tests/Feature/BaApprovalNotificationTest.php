<?php

namespace Tests\Feature;

use App\Livewire\Notification\Dropdown;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\Notification;
use App\Models\User;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Stage 5 alur approval CAPA: notifikasi bell per transisi status, beserta aturan four-eyes.
 */
class BaApprovalNotificationTest extends TestCase
{
    use RefreshDatabase;

    private BaIncidentService $service;

    private User $reporter;

    private User $supervisor;

    private User $otherDivisionSupervisor;

    private User $quality;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->service = app(BaIncidentService::class);
        $produksi = Division::where('name', 'Produksi')->firstOrFail();
        $engineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->reporter = $this->userWithRoles($produksi, 'employee');
        $this->supervisor = $this->userWithRoles($produksi, 'supervisor');
        $this->otherDivisionSupervisor = $this->userWithRoles($engineering, 'supervisor');
        $this->quality = $this->userWithRoles($engineering, 'quality');
        $this->admin = $this->userWithRoles($engineering, 'admin');
    }

    private function userWithRoles(Division $division, string ...$roles): User
    {
        $user = User::factory()->create(['division_id' => $division->id]);
        $user->assignRole($roles);

        return $user;
    }

    private function submitted(): BaIncident
    {
        $draft = $this->service->saveDraft([
            'division_id' => $this->reporter->division_id,
            'deskripsi_masalah' => 'Roller conveyor macet.',
        ], $this->reporter);

        return $this->service->submitWithVideo($draft, $this->reporter, [
            'video_external_link' => 'https://vimeo.com/123456789',
        ]);
    }

    /**
     * @return list<int>
     */
    private function recipients(BaIncident $incident, string $type): array
    {
        return Notification::where('related_id', (string) $incident->id)
            ->where('type', $type)
            ->orderBy('user_id')
            ->pluck('user_id')
            ->all();
    }

    public function test_submission_notifies_only_supervisors_of_the_reporting_division(): void
    {
        $incident = $this->submitted();

        $this->assertSame([$this->supervisor->id], $this->recipients($incident, 'ba_review'));

        $notification = Notification::where('user_id', $this->supervisor->id)->firstOrFail();
        $this->assertSame('BA Menunggu Review: '.$incident->nomor_ba, $notification->title);
        $this->assertSame('ba_incident', $notification->related_type);
    }

    public function test_resubmission_after_revision_notifies_the_supervisor_again(): void
    {
        $incident = $this->submitted();
        $incident = $this->service->reject($incident, $this->supervisor, 'Lengkapi analisis why kedua.');

        // Permintaan revisi Supervisor tidak memicu notifikasi penolakan HR.
        $this->assertSame([], $this->recipients($incident, 'ba_ditolak_hr'));

        $this->service->submitWithVideo($incident, $this->reporter, ['video_external_link' => '']);

        $titles = Notification::where('user_id', $this->supervisor->id)->orderBy('created_at')->orderBy('id')->pluck('title')->all();
        $this->assertSame(['BA Menunggu Review: '.$incident->nomor_ba, 'BA Revisi Menunggu Review: '.$incident->nomor_ba], $titles);
    }

    public function test_supervisor_approval_notifies_every_hr_reviewer_role(): void
    {
        $incident = $this->submitted();
        Notification::query()->delete();

        $this->service->approveAsSupervisor($incident, $this->supervisor, 'Sudah dicek di lapangan.');

        $recipients = $this->recipients($incident, 'ba_review');
        sort($recipients);
        $expected = [$this->quality->id, $this->admin->id];
        sort($expected);

        $this->assertSame($expected, $recipients);
        $this->assertStringContainsString('Menunggu Review Final HR', Notification::where('user_id', $this->quality->id)->value('title'));
    }

    public function test_hr_rejection_notifies_the_supervisor_who_approved(): void
    {
        $incident = $this->service->approveAsSupervisor($this->submitted(), $this->supervisor);
        Notification::query()->delete();

        $this->service->reject($incident, $this->quality, 'Tindakan korektif tidak relevan.');

        $this->assertSame([$this->supervisor->id], $this->recipients($incident, 'ba_ditolak_hr'));
        $this->assertSame(1, Notification::count());
        $this->assertStringContainsString('Tindakan korektif tidak relevan.', Notification::value('message'));
    }

    public function test_four_eyes_blocks_the_same_person_from_approving_both_stages(): void
    {
        $dualRole = $this->userWithRoles(Division::where('name', 'Produksi')->firstOrFail(), 'supervisor', 'quality');
        $incident = $this->submitted();

        $incident = $this->service->approveAsSupervisor($incident, $dualRole);

        // Pemberi approval tahap Supervisor tidak boleh memutus tahap HR, dan tidak diberi tahu.
        $this->assertFalse($dualRole->can('reviewAsHr', $incident));
        $this->assertTrue($this->quality->can('reviewAsHr', $incident));
        $hrQueueRecipients = Notification::where('related_id', (string) $incident->id)
            ->where('title', 'like', '%Menunggu Review Final HR%')
            ->pluck('user_id')
            ->all();
        $this->assertNotContains($dualRole->id, $hrQueueRecipients);
        $this->assertContains($this->quality->id, $hrQueueRecipients);

        $this->expectException(DomainException::class);
        $this->service->approve($incident, $dualRole, ['status_verifikasi' => 'efektif', 'bukti_objektif' => 'Oke.']);
    }

    public function test_failed_transition_sends_no_notification(): void
    {
        $incident = $this->submitted();
        Notification::query()->delete();

        // Log aktivitas ditulis setelah perubahan status; kegagalannya membatalkan seluruh transisi.
        Schema::drop('ba_activity_logs');

        try {
            $this->service->approveAsSupervisor($incident, $this->supervisor);
            $this->fail('Transisi seharusnya gagal.');
        } catch (QueryException) {
            // diharapkan
        }

        $this->assertSame('pending_supervisor', $incident->fresh()->status);
        $this->assertSame(0, Notification::count());
    }

    public function test_hr_rejection_notification_links_to_the_report(): void
    {
        $incident = $this->service->approveAsSupervisor($this->submitted(), $this->supervisor);
        $this->service->reject($incident, $this->quality, 'Ditolak.');

        $notification = Notification::where('type', 'ba_ditolak_hr')->firstOrFail();

        Livewire::actingAs($this->supervisor)
            ->test(Dropdown::class)
            ->call('markAsRead', $notification->id)
            ->assertRedirect(route('ba.show', $incident->id));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
