<?php

namespace Tests\Feature;

use App\Livewire\Leaderboard\Index as LeaderboardIndex;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StageTenLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected Division $divisionProduksi;

    protected Division $divisionQC;

    protected User $userChampion;

    protected User $userRunnerUp;

    protected User $userThird;

    protected User $userFourth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->divisionProduksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->divisionQC = Division::where('name', 'Quality Control')->firstOrFail();

        // Buat 4 user dengan variasi poin berbeda
        $this->userChampion = User::factory()->create([
            'name' => 'Ahmad Juara Satu',
            'employee_id' => 'CPS-00001',
            'division_id' => $this->divisionProduksi->id,
            'xp' => 3500,
        ]);

        $this->userRunnerUp = User::factory()->create([
            'name' => 'Budi Juara Dua',
            'employee_id' => 'CPS-00002',
            'division_id' => $this->divisionQC->id,
            'xp' => 2200,
        ]);

        $this->userThird = User::factory()->create([
            'name' => 'Candra Juara Tiga',
            'employee_id' => 'CPS-00003',
            'division_id' => $this->divisionProduksi->id,
            'xp' => 1500,
        ]);

        $this->userFourth = User::factory()->create([
            'name' => 'Dedi Peringkat Empat',
            'employee_id' => 'CPS-00004',
            'division_id' => $this->divisionQC->id,
            'xp' => 750,
        ]);
    }

    /**
     * DoD #1: Sorting berdasarkan Points benar (descending).
     */
    public function test_leaderboard_sorting_by_points_is_strictly_descending(): void
    {
        $response = $this->actingAs($this->userRunnerUp)->get(route('leaderboard.index'));
        $response->assertStatus(200);

        Livewire::actingAs($this->userRunnerUp)
            ->test(LeaderboardIndex::class)
            // Cek bahwa posisi nama berurutan dari poin tertinggi ke terendah
            ->assertSeeInOrder([
                'Ahmad Juara Satu',   // 3500 poin
                'Budi Juara Dua',     // 2200 poin
                'Candra Juara Tiga',  // 1500 poin
                'Dedi Peringkat Empat', // 750 poin
            ])
            ->assertSeeHtml('3,500')
            ->assertSeeHtml('2,200')
            ->assertSeeHtml('1,500')
            ->assertSeeHtml('750');
    }

    /**
     * DoD #2: Baris user sendiri ter-highlight pakai brand-tint (bukan warna solid berlebihan).
     */
    public function test_current_user_row_is_highlighted_with_brand_tint_not_solid(): void
    {
        // Login sebagai userRunnerUp (Budi)
        $component = Livewire::actingAs($this->userRunnerUp)
            ->test(LeaderboardIndex::class);

        // 1. Verifikasi baris user login mendapat kelas bg-brand-tint
        $component->assertSeeHtml('user-row-'.$this->userRunnerUp->id.'"')
            ->assertSeeHtml('bg-brand-tint border-l-4 border-l-brand');

        // 2. Verifikasi ada badge penanda "(Anda)"
        $component->assertSee('(Anda)');

        // 3. Verifikasi baris user TIDAK memakai warna hijau solid berlebihan sebagai latar tr
        $component->assertDontSeeHtml('user-row-'.$this->userRunnerUp->id.'" class="transition bg-brand text-white');

        // 4. Verifikasi banner status peringkat pengguna memuat peringkat #2
        $component->assertSee('Peringkat Anda')
            ->assertSee('#2');
    }

    /**
     * DoD #3: Table-to-card berfungsi di mobile (Design System §4).
     */
    public function test_table_to_card_responsive_structure_for_mobile_view(): void
    {
        $component = Livewire::actingAs($this->userThird)
            ->test(LeaderboardIndex::class);

        // 1. Verifikasi kontainer tabel desktop & tablet tersembunyi di mobile (hidden md:block)
        $component->assertSeeHtml('hidden md:block bg-white border border-neutral-200 rounded-lg overflow-hidden');
        $component->assertSeeHtml('id="leaderboard-table"');

        // 2. Verifikasi 4 kolom tabel hadir pada desktop: Rank, Nama, Divisi, Points (Level sudah dihapus)
        $component->assertSeeHtml('>Rank</th>')
            ->assertSeeHtml('>Nama Pegawai</th>')
            ->assertSeeHtml('>Divisi</th>')
            ->assertSeeHtml('>Points</th>')
            ->assertDontSeeHtml('>Level</th>')
            ->assertDontSee('Lv.');

        // 3. Verifikasi kontainer kartu mobile aktif di mobile dan tersembunyi di desktop (block md:hidden)
        $component->assertSeeHtml('block md:hidden space-y-3');
        $component->assertSeeHtml('id="leaderboard-mobile-cards"');

        // 4. Verifikasi kartu mobile untuk user login juga mendapat sorotan bg-brand-tint
        $component->assertSeeHtml('mobile-card-'.$this->userThird->id.'"')
            ->assertSeeHtml('bg-brand-tint border-brand/50');
    }

    /**
     * Uji Filter Divisi & Pencarian Nama Pegawai.
     */
    public function test_leaderboard_division_filter_and_search_preserves_sorting(): void
    {
        // 1. Filter divisi Produksi -> Hanya Ahmad dan Candra yang muncul, tetap terurut
        Livewire::actingAs($this->userChampion)
            ->test(LeaderboardIndex::class)
            ->set('selectedDivision', (string) $this->divisionProduksi->id)
            ->assertSee('Ahmad Juara Satu')
            ->assertSee('Candra Juara Tiga')
            ->assertDontSee('Budi Juara Dua')
            ->assertDontSee('Dedi Peringkat Empat')
            ->assertSeeInOrder([
                'Ahmad Juara Satu',
                'Candra Juara Tiga',
            ]);

        // 2. Pencarian Nama Pegawai
        Livewire::actingAs($this->userChampion)
            ->test(LeaderboardIndex::class)
            ->set('search', 'Dedi')
            ->assertSee('Dedi Peringkat Empat')
            ->assertDontSee('Candra Juara Tiga');

        // 3. Pencarian hanya berdasarkan nama: ID pegawai tidak lagi dipakai di Leaderboard
        Livewire::actingAs($this->userChampion)
            ->test(LeaderboardIndex::class)
            ->set('search', 'CPS-00002')
            ->assertDontSee('Budi Juara Dua');
    }

    /**
     * Leaderboard hanya menampilkan nama (tanpa ID pegawai) dan filter berisi 12 divisi.
     */
    public function test_leaderboard_hides_employee_ids_and_lists_twelve_divisions(): void
    {
        Livewire::actingAs($this->userChampion)
            ->test(LeaderboardIndex::class)
            ->assertSee('Ahmad Juara Satu')
            ->assertDontSee('CPS-00001')
            ->assertDontSee('CPS-00002')
            ->assertDontSee('CPS-00003')
            ->assertDontSee('CPS-00004')
            ->assertSee('Cari nama pegawai...')
            ->assertSee('Semua Divisi (12 Divisi)')
            ->assertSee('Marketing & Sales')
            ->assertDontSeeHtml('>Sales</option>');
    }
}
