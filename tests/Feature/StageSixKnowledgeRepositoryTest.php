<?php

namespace Tests\Feature;

use App\Livewire\Knowledge\Index as KnowledgeIndex;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Models\UserBookmark;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StageSixKnowledgeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    /**
     * Test autentikasi: Halaman Knowledge Repository wajib login.
     */
    public function test_knowledge_repository_requires_authentication(): void
    {
        $response = $this->get(route('knowledge.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * DoD #1: Filter 12 divisi (Marketing & Sales digabung) sesuai daftar tetap di Design System §8.
     */
    public function test_knowledge_repository_contains_all_12_fixed_divisions_from_design_system(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $expectedDivisions = [
            'Engineering',
            'Finance Accounting Tax',
            'HRGA',
            'Jahit',
            'Marketing & Sales',
            'PPIC',
            'Plant Balben & Krian',
            'Produksi',
            'Purchasing',
            'Quality Control',
            'RM Warehouse',
            'Warehouse & Delivery',
        ];

        $response = $this->actingAs($user)->get(route('knowledge.index'));
        $response->assertStatus(200);

        // Verifikasi semua 12 divisi hadir di dropdown filter
        foreach ($expectedDivisions as $divName) {
            $response->assertSee($divName);
        }

        // Verifikasi total divisi di database adalah tepat 12
        $this->assertCount(12, Division::all());
    }

    /**
     * DoD #2: Search reaktif tanpa reload (Livewire wire:model.live.debounce.300ms).
     */
    public function test_livewire_reactive_search_filters_documents_instantly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $div = Division::first();

        $docA = KnowledgeDocument::factory()->create([
            'title' => 'SOP Kalibrasi Sensor Injection 01',
            'description' => 'Prosedur standar kalibrasi harian mesin injection',
            'division_id' => $div->id,
            'status' => 'published',
            'type' => 'sop',
        ]);

        $docB = KnowledgeDocument::factory()->create([
            'title' => 'Panduan Audit Keuangan Kuartal',
            'description' => 'Tata cara pelaporan berkas kas kecil dan audit',
            'division_id' => $div->id,
            'status' => 'published',
            'type' => 'dokumen',
        ]);

        Livewire::actingAs($user)
            ->test(KnowledgeIndex::class)
            ->assertSee('SOP Kalibrasi Sensor Injection 01')
            ->assertSee('Panduan Audit Keuangan Kuartal')
            // Jalankan pencarian reaktif
            ->set('search', 'Sensor Injection')
            ->assertSee('SOP Kalibrasi Sensor Injection 01')
            ->assertDontSee('Panduan Audit Keuangan Kuartal')
            // Reset pencarian
            ->set('search', '')
            ->assertSee('SOP Kalibrasi Sensor Injection 01')
            ->assertSee('Panduan Audit Keuangan Kuartal');
    }

    /**
     * DoD #1 & #2: Filter reaktif berdasarkan Divisi dan Tipe Materi.
     */
    public function test_filter_by_division_and_material_type_reactively(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $divEngineering = Division::where('name', 'Engineering')->first();
        $divHRGA = Division::where('name', 'HRGA')->first();

        $docEng = KnowledgeDocument::factory()->create([
            'title' => 'Panduan CAD Blueprint Mold',
            'division_id' => $divEngineering->id,
            'type' => 'presentasi',
            'status' => 'published',
        ]);

        $docHR = KnowledgeDocument::factory()->create([
            'title' => 'Kebijakan Cuti & BPJS Ketenagakerjaan',
            'division_id' => $divHRGA->id,
            'type' => 'sop',
            'status' => 'published',
        ]);

        Livewire::actingAs($user)
            ->test(KnowledgeIndex::class)
            // Filter divisi Engineering
            ->set('selectedDivisionId', $divEngineering->id)
            ->assertSee('Panduan CAD Blueprint Mold')
            ->assertDontSee('Kebijakan Cuti & BPJS Ketenagakerjaan')
            // Filter tipe 'sop'
            ->set('selectedDivisionId', null)
            ->set('selectedType', 'sop')
            ->assertDontSee('Panduan CAD Blueprint Mold')
            ->assertSee('Kebijakan Cuti & BPJS Ketenagakerjaan');
    }

    /**
     * DoD #3: Bookmark tersimpan per user, dicek ke ERD relasi user_bookmarks.
     */
    public function test_bookmark_saves_per_user_in_user_bookmarks_relation_and_toggles(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('employee');
        $user2->assignRole('employee');

        $doc = KnowledgeDocument::factory()->create([
            'title' => 'SOP Penanganan Short Shot Mold A',
            'status' => 'published',
            'type' => 'sop',
        ]);

        // User 1 membuka halaman dan toggle bookmark
        Livewire::actingAs($user1)
            ->test(KnowledgeIndex::class)
            ->call('toggleBookmark', $doc->id);

        // Verifikasi di database: user_bookmarks menyimpan relasi user1 dan doc
        $this->assertDatabaseHas('user_bookmarks', [
            'user_id' => $user1->id,
            'knowledge_document_id' => $doc->id,
        ]);

        // User 2 belum memiliki bookmark untuk doc tersebut
        $this->assertDatabaseMissing('user_bookmarks', [
            'user_id' => $user2->id,
            'knowledge_document_id' => $doc->id,
        ]);

        // User 1 toggle bookmark sekali lagi -> bookmark harus terhapus
        Livewire::actingAs($user1)
            ->test(KnowledgeIndex::class)
            ->call('toggleBookmark', $doc->id);

        $this->assertDatabaseMissing('user_bookmarks', [
            'user_id' => $user1->id,
            'knowledge_document_id' => $doc->id,
        ]);
    }

    /**
     * DoD #3: Filter "Hanya Bookmark" hanya menampilkan materi yang di-bookmark user aktif.
     */
    public function test_only_bookmarks_filter_shows_saved_documents(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $docSaved = KnowledgeDocument::factory()->create([
            'title' => 'Dokumen Favorit Tersimpan',
            'status' => 'published',
        ]);

        $docUnsaved = KnowledgeDocument::factory()->create([
            'title' => 'Dokumen Lain Tidak Tersimpan',
            'status' => 'published',
        ]);

        // Simpan bookmark untuk docSaved
        UserBookmark::create([
            'user_id' => $user->id,
            'knowledge_document_id' => $docSaved->id,
        ]);

        Livewire::actingAs($user)
            ->test(KnowledgeIndex::class)
            ->set('onlyBookmarks', true)
            ->assertSee('Dokumen Favorit Tersimpan')
            ->assertDontSee('Dokumen Lain Tidak Tersimpan');
    }

    /**
     * Verifikasi Desain: 5 Tipe Materi ditandai LEWAT IKON OUTLINE, bukan warna berbeda per tipe.
     * Tidak ada badge rainbow/warna-warni per tipe (Dokumen/Video/Presentasi/SOP/Link).
     */
    public function test_material_types_use_outline_icons_without_rainbow_badge_colors(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $types = ['dokumen', 'video', 'presentasi', 'sop', 'link'];

        foreach ($types as $type) {
            KnowledgeDocument::factory()->create([
                'title' => "Materi Tipe {$type}",
                'type' => $type,
                'status' => 'published',
            ]);
        }

        $response = $this->actingAs($user)->get(route('knowledge.index'));
        $response->assertStatus(200);

        // Pastikan tidak ada class badge warna-warni spesifik untuk tipe (anti-rainbow slop)
        $response->assertDontSee('bg-blue-500', false);
        $response->assertDontSee('bg-red-500', false);
        $response->assertDontSee('bg-purple-500', false);
        $response->assertDontSee('bg-yellow-500', false);
        $response->assertDontSee('bg-orange-500', false);

        // Semua tipe ditampilkan dalam teks netral
        $response->assertSee('Dokumen');
        $response->assertSee('Video');
        $response->assertSee('Presentasi');
        $response->assertSee('SOP');
    }

    /**
     * Verifikasi View Mode: Dapat berpindah antara Grid dan List tanpa reload.
     */
    public function test_view_mode_grid_and_list_can_be_switched(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        KnowledgeDocument::factory()->create([
            'title' => 'Panduan Tata Kerja Standard',
            'status' => 'published',
        ]);

        Livewire::actingAs($user)
            ->test(KnowledgeIndex::class)
            ->assertSet('viewMode', 'grid')
            ->set('viewMode', 'list')
            ->assertSet('viewMode', 'list')
            ->assertSee('Panduan Tata Kerja Standard');
    }
}
