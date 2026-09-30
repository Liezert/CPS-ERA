<?php

namespace Tests\Feature;

use App\Filament\Pages\GoogleDrive;
use App\Filament\Resources\KpiSettings\KpiSettingResource;
use App\Livewire\Admin\Taxonomy;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeTopic;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\FakesGoogleDrive;
use Tests\TestCase;

/**
 * Masukan owner 2026-09-27: dashboard HR hanya berisi review & kelola data, pengaturan jarang dipakai
 * dikelompokkan sebagai "Advance", dan kategori Learning + topik Knowledge dikelola dari satu halaman
 * bertampilan utama CPS ERA.
 */
class AdminFocusAndTaxonomyTest extends TestCase
{
    use FakesGoogleDrive;
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = $this->userWithRole('admin', 'HRGA');
        $this->employee = $this->userWithRole('employee', 'Produksi');
    }

    private function userWithRole(string $role, string $division): User
    {
        $user = User::factory()->create(['division_id' => Division::where('name', $division)->value('id')]);
        $user->assignRole($role);

        return $user;
    }

    public function test_hr_dashboard_only_shows_review_and_data_management(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Menunggu Review Anda')
            ->assertSee('Kelola Data (Admin)')
            ->assertSee('Video Kontribusi')
            ->assertDontSee('Lanjutkan Pembelajaran')
            ->assertDontSee('Misi yang Perlu Diselesaikan')
            ->assertDontSee('Knowledge Terbaru')
            ->assertDontSee('Laporan CAPA Terbaru')
            ->assertDontSee('KPI Contribution');

        $quality = $this->userWithRole('quality', 'Quality Control');
        $this->actingAs($quality)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Menunggu Review Anda')
            ->assertDontSee('Lanjutkan Pembelajaran');

        // Karyawan tetap melihat dashboard lengkapnya.
        $this->actingAs($this->employee)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lanjutkan Pembelajaran')
            ->assertSee('KPI Contribution')
            ->assertDontSee('Kelola Data (Admin)');
    }

    public function test_rarely_used_settings_are_grouped_as_advance(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->getContent();
        $advance = substr($html, strpos($html, 'pengaturan yang jarang diubah'));

        $this->assertStringContainsString(route('filament.admin.resources.kpi-settings.index'), $advance);
        $this->assertStringContainsString(route('filament.admin.pages.google-drive'), $advance);
        $this->assertStringNotContainsString(route('filament.admin.resources.learning-materials.create'), $advance);

        $this->assertSame('Advance', KpiSettingResource::getNavigationGroup());
        $this->assertSame('Advance', GoogleDrive::getNavigationGroup());
    }

    public function test_taxonomy_page_uses_main_layout_and_is_hr_only(): void
    {
        $this->actingAs($this->admin)->get(route('taxonomy.index'))
            ->assertOk()
            ->assertSee('Kategori &amp; Topik', false)
            ->assertSee('Kategori Learning')
            ->assertSee('Topik Knowledge')
            ->assertSee(route('learning.index'), false) // sidebar CPS ERA, bukan layout Filament
            ->assertDontSee('fi-sidebar', false);

        $this->actingAs($this->employee)->get(route('taxonomy.index'))->assertForbidden();

        // Sidebar HR menautkan ke halaman gabungan, bukan halaman Filament.
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertSee(route('taxonomy.index'), false)
            ->assertDontSee(route('filament.admin.resources.learning-categories.index'), false);
    }

    public function test_hr_manages_learning_categories(): void
    {
        $component = Livewire::actingAs($this->admin)->test(Taxonomy::class)
            ->assertSet('tab', 'kategori')
            ->set('name', 'K3')
            ->call('save')
            ->assertHasErrors(['name' => 'min'])
            ->set('name', 'Keselamatan Kerja')
            ->call('save')
            ->assertHasNoErrors()
            ->set('name', 'Keselamatan Kerja')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);

        $category = LearningCategory::where('name', 'Keselamatan Kerja')->sole();

        $component->call('edit', $category->id)
            ->set('editName', 'Keselamatan & K3')
            ->call('update')
            ->assertHasNoErrors();
        $this->assertSame('Keselamatan & K3', $category->fresh()->name);

        $component->call('delete', $category->id);
        $this->assertModelMissing($category);
    }

    public function test_category_with_materials_cannot_be_deleted(): void
    {
        $category = LearningCategory::create(['name' => 'Produksi Dasar', 'created_by' => $this->admin->id]);
        $material = LearningMaterial::create([
            'learning_category_id' => $category->id,
            'title' => 'Materi Penting',
            'type' => 'artikel',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)->test(Taxonomy::class)
            ->call('delete', $category->id)
            ->assertHasErrors('delete')
            ->assertSee('masih dipakai 1 materi');

        $this->assertModelExists($category);
        $this->assertModelExists($material);
    }

    public function test_hr_manages_knowledge_topics_and_documents_keep_existing(): void
    {
        $component = Livewire::actingAs($this->admin)->test(Taxonomy::class)
            ->set('tab', 'topik')
            ->set('name', 'SOP Produksi')
            ->set('description', 'Prosedur standar lini produksi')
            ->call('save')
            ->assertHasNoErrors();

        $topic = KnowledgeTopic::where('name', 'SOP Produksi')->sole();
        $this->assertSame('Prosedur standar lini produksi', $topic->description);

        $document = KnowledgeDocument::create([
            'title' => 'SOP Mesin Press',
            'division_id' => $this->admin->division_id,
            'topic_id' => $topic->id,
            'type' => 'sop',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        $component->call('delete', $topic->id);

        $this->assertModelMissing($topic);
        $this->assertNull($document->fresh()->topic_id);
    }

    public function test_employee_cannot_change_categories_even_by_calling_actions(): void
    {
        $category = LearningCategory::create(['name' => 'Tetap Ada', 'created_by' => $this->admin->id]);

        Livewire::actingAs($this->employee)->test(Taxonomy::class)->assertForbidden();
        $this->assertModelExists($category);
    }

    public function test_hr_review_queue_groups_and_secondary_capa_button(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

        // Antrean dipisah per jenis dengan format item yang sama.
        $this->assertStringContainsString('data-queue="capa"', $html);
        $this->assertStringContainsString('data-queue="video"', $html);

        // Kelola Data dikelompokkan per kategori.
        foreach (['Learning &amp; Pengembangan', 'Knowledge &amp; Video', 'SDM &amp; Laporan'] as $group) {
            $this->assertStringContainsString($group, $html);
        }

        // Bagi HR, "Buat Laporan CAPA" bukan lagi tombol utama (hijau solid); bagi karyawan tetap utama.
        $this->assertDoesNotMatchRegularExpression('/bg-brand[^"]*"[^>]*>\s*Buat Laporan CAPA/', $html);
        $employeeHtml = $this->actingAs($this->employee)->get(route('dashboard'))->getContent();
        $this->assertMatchesRegularExpression('/bg-brand[^"]*"[^>]*>\s*Buat Laporan CAPA/', $employeeHtml);
    }

    public function test_google_drive_status_is_visible_on_admin_dashboard(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertSee('Google Drive: Belum terhubung');

        $this->connectDriveAccount();

        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertSee('Google Drive: Terhubung')
            ->assertSee('drive-cps@gmail.com');

        // Advance menjelaskan dampak sistemiknya.
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertSee('memengaruhi progres KPI seluruh karyawan');

        $this->actingAs($this->employee)->get(route('dashboard'))->assertDontSee('Google Drive:');
    }
}
