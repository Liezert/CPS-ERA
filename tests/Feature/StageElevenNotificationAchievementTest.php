<?php

namespace Tests\Feature;

use App\Livewire\Achievement\Index as AchievementIndex;
use App\Livewire\Notification\Dropdown as NotificationDropdown;
use App\Models\Achievement;
use App\Models\Division;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserAchievement;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StageElevenNotificationAchievementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Division $division;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->division = Division::first();

        $this->user = User::factory()->create([
            'division_id' => $this->division->id,
            'total_points' => 350,
            'level' => 'Technician',
        ]);
        $this->user->assignRole('employee');
    }

    /**
     * DoD #1: 4 jenis notifikasi tampil benar di dropdown bell list dengan hairline divider.
     */
    public function test_four_notification_types_displayed_correctly_in_dropdown(): void
    {
        // 1. Buat notifikasi untuk ke-4 event: knowledge_baru, misi_baru, ba_review, achievement_baru
        $notifKnowledge = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'knowledge_baru',
            'title' => 'SOP Mesin CNC Diterbitkan',
            'message' => 'Dokumen SOP baru telah ditambahkan ke repositori.',
            'read_at' => null,
            'created_at' => now()->subMinutes(10),
        ]);

        $notifMisi = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'misi_baru',
            'title' => 'Tantangan K3 Baru',
            'message' => 'Selesaikan kuis keselamatan kerja untuk 50 poin XP.',
            'read_at' => null,
            'created_at' => now()->subMinutes(20),
        ]);

        $notifBa = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'ba_review',
            'title' => 'Laporan BA-2026-0099 Perlu Review',
            'message' => 'Berita acara insiden siap ditinjau supervisor.',
            'read_at' => null,
            'created_at' => now()->subMinutes(30),
        ]);

        $notifAchievement = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'achievement_baru',
            'title' => 'Badge Safety Pioneer Terbuka',
            'message' => 'Selamat, Anda telah meraih lencana Safety Pioneer!',
            'read_at' => null,
            'created_at' => now()->subMinutes(40),
        ]);

        // 2. Test Livewire Notification Dropdown component
        Livewire::actingAs($this->user)
            ->test(NotificationDropdown::class)
            // Cek 4 event tampil
            ->assertSee('SOP Mesin CNC Diterbitkan')
            ->assertSee('Tantangan K3 Baru')
            ->assertSee('Laporan BA-2026-0099 Perlu Review')
            ->assertSee('Badge Safety Pioneer Terbuka')
            // Cek hairline divider styling (Design System §5)
            ->assertSee('divide-y divide-neutral-200', false)
            // Cek unread count counter
            ->assertSee('4 Baru')
            // Cek tombol tandai semua dibaca
            ->assertSee('Tandai dibaca')
            // Jalankan markAsRead satu notifikasi
            ->call('markAsRead', (string) $notifKnowledge->id)
            ->assertSet('unreadCount', 3);

        $this->assertNotNull($notifKnowledge->fresh()->read_at);

        // Jalankan markAllAsRead
        Livewire::actingAs($this->user)
            ->test(NotificationDropdown::class)
            ->call('markAllAsRead')
            ->assertSet('unreadCount', 0);

        $this->assertNotNull($notifMisi->fresh()->read_at);
        $this->assertNotNull($notifBa->fresh()->read_at);
        $this->assertNotNull($notifAchievement->fresh()->read_at);
    }

    /**
     * DoD #2: Unlocked/Locked dibedakan lewat warna ikon saja (bukan dekorasi tambahan).
     */
    public function test_unlocked_and_locked_achievements_differentiated_only_by_icon_color(): void
    {
        $achievementUnlocked = Achievement::create([
            'name' => 'Knowledge Master',
            'description' => 'Menerbitkan lebih dari 10 SOP berkualitas.',
            'icon' => 'book',
        ]);

        $achievementLocked = Achievement::create([
            'name' => 'Zero Incident Hero',
            'description' => 'Bekerja 365 hari tanpa insiden keselamatan.',
            'icon' => 'shield-check',
        ]);

        // Buka $achievementUnlocked untuk $this->user
        UserAchievement::create([
            'user_id' => $this->user->id,
            'achievement_id' => $achievementUnlocked->id,
            'unlocked_at' => now()->subDays(5),
        ]);

        // Uji komponen Achievement Grid
        $component = Livewire::actingAs($this->user)
            ->test(AchievementIndex::class)
            ->assertSee('Knowledge Master')
            ->assertSee('Zero Incident Hero')
            ->assertSee('Total')
            ->assertSee('Unlocked')
            ->assertSee('Locked');

        $html = $component->html();

        // 1. Unlocked dibedakan dengan warna ikon hijau (#0B7840 -> text-brand)
        $this->assertStringContainsString('text-brand', $html);

        // 2. Locked dibedakan dengan warna ikon abu-abu transparan (text-neutral-400 opacity-40)
        $this->assertStringContainsString('text-neutral-400 opacity-40', $html);

        // 3. Checklist Anti-AI-Slop: Tidak ada dekorasi tambahan berlebihan
        $this->assertStringNotContainsString('confetti', $html);
        $this->assertStringNotContainsString('bg-gradient', $html);
        $this->assertStringNotContainsString('border-amber', $html);
        $this->assertStringNotContainsString('ribbon', $html);
        $this->assertStringNotContainsString('gold', $html);
    }

    /**
     * DoD #3: Kriteria unlock ditandai TODO di logika/kode & antarmuka sesuai PRD §5.3.
     */
    public function test_unlock_criteria_marked_as_todo_per_prd_5_3(): void
    {
        // 1. Periksa kode PHP di App\Livewire\Achievement\Index
        $classFile = file_get_contents(app_path('Livewire/Achievement/Index.php'));
        $this->assertStringContainsString('TODO: Menunggu keputusan PRD §5.3 (Poin 5: Kriteria unlock achievement)', $classFile);

        // 2. Periksa Blade view di resources/views/livewire/achievement/index.blade.php
        $bladeFile = file_get_contents(resource_path('views/livewire/achievement/index.blade.php'));
        $this->assertStringContainsString('TODO: Menunggu keputusan PRD §5.3 (Poin 5: Kriteria unlock achievement)', $bladeFile);

        // 3. Periksa antarmuka saat dirender memuat catatan kriteria PRD §5.3
        Livewire::actingAs($this->user)
            ->test(AchievementIndex::class)
            ->assertSee('[Menunggu Keputusan PRD §5.3: Kriteria Otomatisasi Unlock Achievement]');
    }

    /**
     * Test integrasi route web /achievements dapat diakses dengan layout terotentikasi.
     */
    public function test_achievements_page_accessible_via_web_route(): void
    {
        $response = $this->actingAs($this->user)->get(route('achievements.index'));
        $response->assertOk();
        $response->assertSee('Pencapaian & Lencana', false);
    }
}
