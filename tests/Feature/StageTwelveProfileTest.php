<?php

namespace Tests\Feature;

use App\Livewire\Profile\Index as ProfileIndex;
use App\Models\Division;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserLearningProgress;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\LearningSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StageTwelveProfileTest extends TestCase
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
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad.fauzi@cps.test',
            'employee_id' => 'CPS-00124',
            'jabatan' => 'Automation Specialist',
            'division_id' => $this->division->id,
            'xp' => 1250,
        ]);
        $this->user->assignRole('employee');

        $this->seed(LearningSeeder::class);

        // Tambah transaksi poin bulanan untuk grafik
        PointTransaction::create([
            'user_id' => $this->user->id,
            'ledger_type' => 'xp',
            'points' => 300,
            'description' => 'Poin Bulan Ini',
            'source_type' => 'mission_completed',
            'created_at' => now(),
        ]);

        PointTransaction::create([
            'user_id' => $this->user->id,
            'ledger_type' => 'xp',
            'points' => 450,
            'description' => 'Poin Bulan Lalu',
            'source_type' => 'mission_completed',
            'created_at' => now()->subMonth(),
        ]);

        $material = LearningMaterial::first();

        UserLearningProgress::create([
            'user_id' => $this->user->id,
            'learning_material_id' => $material->id,
            'progress_percent' => 80,
            'completed_at' => now(),
        ]);
    }

    /**
     * DoD #1: Metric card konsisten visual dengan Dashboard (Stage 5), tidak ada varian baru.
     */
    public function test_metric_cards_visually_consistent_with_dashboard(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            // 1. Memeriksa 4 label metric card yang identik dengan Dashboard
            ->assertSee('Learning Progress')
            ->assertSee('KPI Contribution')
            ->assertSee('Total Poin Saya')
            // 2. Memeriksa nilai & status metric
            ->assertSee('80%')
            // KPI Contribution kini berbasis materi Learning (bukan video).
            ->assertSee('0 dari 5 materi')
            ->assertDontSee('video')
            ->assertSee('Pts')
            // 3. Memeriksa sub-teks footer metric card konsisten
            ->assertSee('Materi Pelatihan Selesai')
            ->assertSee('Agregasi ledger point_transactions')
            // Level sudah dihapus dari CPS ERA
            ->assertDontSee('Target Level Berikutnya')
            ->assertDontSee('Progress Level');

        $html = $component->html();

        // Tidak ada border warna-warni atau varian baru
        $this->assertStringNotContainsString('border-amber', $html);
        $this->assertStringNotContainsString('border-purple', $html);
        $this->assertStringNotContainsString('border-rose', $html);
    }

    /**
     * DoD #2: Employee ID pakai mono font, format benar (CPS-00124).
     */
    public function test_employee_id_uses_mono_font_and_correct_format(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->assertSee('CPS-00124');

        $html = $component->html();

        // Memastikan format CPS-00124 dibungkus atau memiliki kelas font-mono (IBM Plex Mono)
        $this->assertMatchesRegularExpression('/class="[^"]*font-mono[^"]*"[^>]*>\s*CPS-00124/i', $html);
    }

    /**
     * DoD #3: Grafik pakai satu warna hijau, tanpa gradient/dekorasi tambahan.
     */
    public function test_monthly_performance_chart_uses_single_green_without_gradients_or_decorations(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->assertSee('Grafik Performa Bulanan')
            ->assertSee('Poin Diperoleh (Hijau Tunggal)')
            ->assertSee('300')
            ->assertSee('450');

        $html = $component->html();

        // 1. Batang chart menggunakan satu warna hijau tunggal (#0B7840 -> bg-brand)
        $this->assertStringContainsString('bg-brand', $html);

        // 2. Tidak ada gradasi (bg-gradient)
        $this->assertStringNotContainsString('bg-gradient', $html);
        $this->assertStringNotContainsString('gradient', $html);

        // 3. Checklist Anti-AI-Slop: Tidak ada dekorasi berlebihan (confetti, ribbon, gold, 3D shadow)
        $this->assertStringNotContainsString('confetti', $html);
        $this->assertStringNotContainsString('ribbon', $html);
        $this->assertStringNotContainsString('gold', $html);
        $this->assertStringNotContainsString('drop-shadow-2xl', $html);
    }

    /**
     * Test identitas lengkap (Nama, Jabatan, Divisi, Avatar) tampil benar, tanpa level.
     */
    public function test_profile_identity_details_render_correctly(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->assertSee('Ahmad Fauzi')
            ->assertSee('Automation Specialist')
            ->assertSee($this->division->name)
            ->assertSee('AF') // Inisial avatar
            ->assertDontSee('Progress Level')
            ->assertDontSee('Level 1');
    }

    /**
     * Test form update profil berhasil memperbarui data di database.
     */
    public function test_profile_information_can_be_updated_via_livewire(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->set('name', 'Ahmad Fauzi Updated')
            ->set('email', 'ahmad.new@cps.test')
            ->call('updateProfileInformation')
            ->assertSee('Informasi profil berhasil diperbarui.');

        $this->user->refresh();
        $this->assertSame('Ahmad Fauzi Updated', $this->user->name);
        $this->assertSame('ahmad.new@cps.test', $this->user->email);
    }

    /**
     * Test halaman profil dapat diakses melalui GET /profile.
     */
    public function test_profile_page_is_accessible_via_web_route(): void
    {
        $response = $this->actingAs($this->user)->get('/profile');
        $response->assertOk();
        $response->assertSee('Profil Pegawai');
        $response->assertSee('CPS-00124');
    }
}
