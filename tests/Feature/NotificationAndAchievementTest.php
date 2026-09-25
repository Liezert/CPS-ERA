<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserAchievement;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAndAchievementTest extends TestCase
{
    use RefreshDatabase;

    protected Division $divisionA;

    protected Division $divisionB;

    protected User $admin;

    protected User $supervisorA;

    protected User $supervisorB;

    protected User $employeeA;

    protected User $employeeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(AchievementSeeder::class);

        $divisions = Division::take(2)->get();
        $this->divisionA = $divisions[0];
        $this->divisionB = $divisions[1];

        $this->admin = User::factory()->create(['division_id' => $this->divisionA->id]);
        $this->admin->assignRole('admin');

        $this->supervisorA = User::factory()->create(['division_id' => $this->divisionA->id]);
        $this->supervisorA->assignRole('supervisor');

        $this->supervisorB = User::factory()->create(['division_id' => $this->divisionB->id]);
        $this->supervisorB->assignRole('supervisor');

        $this->employeeA = User::factory()->create(['division_id' => $this->divisionA->id]);
        $this->employeeA->assignRole('employee');

        $this->employeeB = User::factory()->create(['division_id' => $this->divisionB->id]);
        $this->employeeB->assignRole('employee');
    }

    /**
     * Checklist 1: Notifikasi otomatis 'knowledge_baru' muncul saat dokumen berstatus published dibuat.
     */
    public function test_knowledge_baru_notification_triggered_when_published_document_is_created(): void
    {
        $initialCountA = Notification::where('user_id', $this->employeeA->id)->count();

        // Buat knowledge document dengan status published oleh employeeB
        $doc = KnowledgeDocument::create([
            'title' => 'Panduan Operasional Mesin CNC',
            'division_id' => $this->divisionA->id,
            'type' => 'sop',
            'description' => 'SOP penggunaan mesin CNC terbaru.',
            'created_by' => $this->employeeB->id,
            'status' => 'published',
        ]);

        // employeeA harus menerima notifikasi knowledge_baru
        $notifA = Notification::where('user_id', $this->employeeA->id)
            ->where('type', 'knowledge_baru')
            ->first();

        $this->assertNotNull($notifA);
        $this->assertStringContainsString('Panduan Operasional Mesin CNC', $notifA->title);
        $this->assertSame('knowledge_document', $notifA->related_type);
        $this->assertSame((string) $doc->id, $notifA->related_id);
        $this->assertNull($notifA->read_at);

        // Pembuat materi (employeeB) tidak perlu dikirimi notifikasi materi buatannya sendiri
        $notifB = Notification::where('user_id', $this->employeeB->id)
            ->where('type', 'knowledge_baru')
            ->where('related_id', (string) $doc->id)
            ->first();

        $this->assertNull($notifB);
    }

    /**
     * Checklist 1: Notifikasi otomatis 'ba_review' muncul saat BA masuk antrean Supervisor
     * (pending_supervisor) untuk supervisor di divisi terkait.
     */
    public function test_ba_review_notification_triggered_for_supervisors_in_the_incident_division(): void
    {
        // Buat BA di divisi A lalu serahkan ke antrean Supervisor
        $ba = BaIncident::create([
            'nomor_ba' => 'BA-2026-0001',
            'division_id' => $this->divisionA->id,
            'file_ba_url' => 'https://example.com/ba.pdf',
            'file_ftk_url' => 'https://example.com/ftk.pdf',
            'status' => 'draft',
            'created_by' => $this->employeeA->id,
        ]);
        $ba->update(['status' => 'pending_supervisor']);

        // Supervisor divisi A HARUS menerima notifikasi ba_review
        $notifSupervisorA = Notification::where('user_id', $this->supervisorA->id)
            ->where('type', 'ba_review')
            ->where('related_id', (string) $ba->id)
            ->first();

        $this->assertNotNull($notifSupervisorA);
        $this->assertStringContainsString('BA-2026-0001', $notifSupervisorA->title);
        $this->assertSame('ba_incident', $notifSupervisorA->related_type);

        // Supervisor divisi B TIDAK boleh menerima notifikasi BA divisi A
        $notifSupervisorB = Notification::where('user_id', $this->supervisorB->id)
            ->where('type', 'ba_review')
            ->where('related_id', (string) $ba->id)
            ->first();

        $this->assertNull($notifSupervisorB);
    }

    /**
     * Checklist 1: Notifikasi otomatis 'misi_baru' muncul saat quiz mission dibuat.
     */
    public function test_misi_baru_notification_triggered_when_mission_quiz_created(): void
    {
        $quiz = Quiz::create([
            'title' => 'Misi Pengetahuan Kaizen 1',
            'type' => 'mission_quiz',
            'points_reward' => 50,
            'description' => 'Selesaikan misi pemahaman Kaizen.',
        ]);

        // Semua user (misal employeeA dan employeeB) harus menerima notifikasi misi_baru
        $notifA = Notification::where('user_id', $this->employeeA->id)
            ->where('type', 'misi_baru')
            ->where('related_id', (string) $quiz->id)
            ->first();

        $this->assertNotNull($notifA);
        $this->assertStringContainsString('Misi Pengetahuan Kaizen 1', $notifA->title);
        $this->assertSame('quiz', $notifA->related_type);

        $notifB = Notification::where('user_id', $this->employeeB->id)
            ->where('type', 'misi_baru')
            ->where('related_id', (string) $quiz->id)
            ->first();

        $this->assertNotNull($notifB);
    }

    /**
     * Checklist 2: Admin/Quality unlock achievement manual untuk user, user menerima notifikasi 'achievement_baru'.
     */
    public function test_manual_achievement_unlock_creates_user_achievement_and_notifies_user(): void
    {
        $achievement = Achievement::where('name', 'Knowledge Contributor')->firstOrFail();

        $this->assertFalse(
            UserAchievement::where('user_id', $this->employeeA->id)
                ->where('achievement_id', $achievement->id)
                ->exists()
        );

        // Simulasi unlock achievement untuk employeeA
        $userAchievement = UserAchievement::create([
            'user_id' => $this->employeeA->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => now(),
        ]);

        $this->assertTrue(
            UserAchievement::where('user_id', $this->employeeA->id)
                ->where('achievement_id', $achievement->id)
                ->exists()
        );

        // Notifikasi 'achievement_baru' harus otomatis dibuat via observer
        $notif = Notification::where('user_id', $this->employeeA->id)
            ->where('type', 'achievement_baru')
            ->where('related_id', (string) $userAchievement->id)
            ->first();

        $this->assertNotNull($notif);
        $this->assertStringContainsString('Knowledge Contributor', $notif->message);
        $this->assertSame('achievement', $notif->related_type);
        $this->assertNull($notif->read_at);
    }
}
