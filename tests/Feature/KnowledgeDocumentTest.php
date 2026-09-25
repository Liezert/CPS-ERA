<?php

namespace Tests\Feature;

use App\Livewire\Knowledge\Index as KnowledgeIndex;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeTopic;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\FakesGoogleDrive;
use Tests\TestCase;

class KnowledgeDocumentTest extends TestCase
{
    use FakesGoogleDrive, RefreshDatabase;

    protected Division $divisionProduksi;

    protected Division $divisionEngineering;

    protected User $admin;

    protected User $supervisorProduksi;

    protected User $supervisorEngineering;

    protected User $quality;

    protected User $employeeProduksi;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->divisionProduksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->divisionEngineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->admin = User::factory()->create(['division_id' => $this->divisionProduksi->id]);
        $this->admin->assignRole('admin');

        $this->supervisorProduksi = User::factory()->create(['division_id' => $this->divisionProduksi->id]);
        $this->supervisorProduksi->assignRole('supervisor');

        $this->supervisorEngineering = User::factory()->create(['division_id' => $this->divisionEngineering->id]);
        $this->supervisorEngineering->assignRole('supervisor');

        $this->quality = User::factory()->create(['division_id' => $this->divisionEngineering->id]);
        $this->quality->assignRole('quality');

        $this->employeeProduksi = User::factory()->create(['division_id' => $this->divisionProduksi->id]);
        $this->employeeProduksi->assignRole('employee');
    }

    /**
     * PRD v2.0 §3.2: Interaksi Livewire: buka modal detail dokumen dan akses berkas.
     */
    public function test_livewire_can_open_document_detail_modal_and_access_download_url(): void
    {
        $topic = KnowledgeTopic::create([
            'name' => 'SOP Keamanan Fasilitas',
            'created_by' => $this->admin->id,
        ]);

        $doc = KnowledgeDocument::create([
            'title' => 'SOP Pengawasan Pos Keamanan 24 Jam',
            'topic_id' => $topic->id,
            'division_id' => $this->divisionProduksi->id,
            'type' => 'sop',
            'description' => 'Instruksi kerja patroli malam dan kontrol gerbang masuk barang.',
            'file_url' => 'knowledge-documents/files/sop-patroli.pdf',
            'created_by' => $this->admin->id,
            'status' => 'published',
        ]);

        Livewire::actingAs($this->employeeProduksi)
            ->test(KnowledgeIndex::class)
            ->assertSee('SOP Pengawasan Pos Keamanan 24 Jam')
            ->assertSee('SOP Keamanan Fasilitas')
            ->assertSet('viewingDocument', null)
            ->call('showDocument', $doc->id)
            ->assertSet('viewingDocument.id', $doc->id)
            ->assertSee('Referensi Resmi Pengetahuan:')
            ->assertSee('tidak memberikan penambahan poin KPI atau XP')
            ->call('closeDocument')
            ->assertSet('viewingDocument', null);
    }
}
