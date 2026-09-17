<?php

namespace Tests\Feature;

use App\Livewire\Knowledge\Index as KnowledgeIndex;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeTopic;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserBookmark;
use App\Models\UserKpiYearly;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KnowledgeDocumentTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_can_list_and_search_and_filter_knowledge_documents(): void
    {
        // Create sample documents
        $doc1 = KnowledgeDocument::create([
            'title' => 'SOP Pengoperasian Mesin Bubut CNC',
            'division_id' => $this->divisionProduksi->id,
            'type' => 'sop',
            'description' => 'Panduan lengkap langkah demi langkah keselamatan kerja mesin bubut.',
            'created_by' => $this->supervisorProduksi->id,
            'status' => 'published',
        ]);

        $doc2 = KnowledgeDocument::create([
            'title' => 'Video Tutorial Wiring Panel PLC',
            'division_id' => $this->divisionEngineering->id,
            'type' => 'video',
            'description' => 'Tutorial wiring elektrik panel PLC Siemens S7-1200.',
            'created_by' => $this->quality->id,
            'status' => 'published',
        ]);

        $doc3 = KnowledgeDocument::create([
            'title' => 'Panduan Maintenance Mesin Produksi',
            'division_id' => $this->divisionProduksi->id,
            'type' => 'dokumen',
            'description' => 'Jadwal dan checklist perawatan berkala.',
            'created_by' => $this->supervisorProduksi->id,
            'status' => 'draft',
        ]);

        // 1. Basic list (published only for employee)
        $res = $this->actingAs($this->employeeProduksi)->getJson('/api/knowledge-documents');
        $res->assertOk();
        $this->assertCount(2, $res->json('data.data'));

        // 2. Search query `q` in title
        $searchRes = $this->actingAs($this->employeeProduksi)->getJson('/api/knowledge-documents?q=Mesin+Bubut');
        $searchRes->assertOk();
        $this->assertCount(1, $searchRes->json('data.data'));
        $this->assertSame($doc1->id, $searchRes->json('data.data.0.id'));

        // 3. Search query `q` in description
        $searchDescRes = $this->actingAs($this->employeeProduksi)->getJson('/api/knowledge-documents?q=Siemens');
        $searchDescRes->assertOk();
        $this->assertCount(1, $searchDescRes->json('data.data'));
        $this->assertSame($doc2->id, $searchDescRes->json('data.data.0.id'));

        // 4. Filter by category_id / division_id
        $filterCategoryRes = $this->actingAs($this->employeeProduksi)->getJson("/api/knowledge-documents?category_id={$this->divisionEngineering->id}");
        $filterCategoryRes->assertOk();
        $this->assertCount(1, $filterCategoryRes->json('data.data'));
        $this->assertSame($doc2->id, $filterCategoryRes->json('data.data.0.id'));

        // 5. Filter by type
        $filterTypeRes = $this->actingAs($this->employeeProduksi)->getJson('/api/knowledge-documents?type=sop');
        $filterTypeRes->assertOk();
        $this->assertCount(1, $filterTypeRes->json('data.data'));
        $this->assertSame($doc1->id, $filterTypeRes->json('data.data.0.id'));
    }

    public function test_manual_creation_forbidden_for_employee(): void
    {
        $response = $this->actingAs($this->employeeProduksi)->postJson('/api/knowledge-documents', [
            'title' => 'Unauthorized Document Attempt',
            'division_id' => $this->divisionProduksi->id,
            'type' => 'sop',
            'description' => 'Employee should be blocked by manage-knowledge-documents gate.',
        ]);

        $response->assertForbidden();
    }

    public function test_manual_creation_rejects_lesson_learned(): void
    {
        // Attempting to manually create with type=lesson_learned must fail with 422
        $response = $this->actingAs($this->quality)->postJson('/api/knowledge-documents', [
            'title' => 'Manual Lesson Learned Percobaan',
            'division_id' => $this->divisionProduksi->id,
            'type' => 'lesson_learned',
            'description' => 'Ini harus ditolak oleh sistem.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
        $this->assertStringContainsString(
            'Tipe lesson_learned hanya dapat dibuat otomatis',
            $response->json('errors.type.0')
        );

        $this->assertDatabaseMissing('knowledge_documents', [
            'title' => 'Manual Lesson Learned Percobaan',
        ]);
    }

    public function test_manual_creation_succeeds_with_valid_type(): void
    {
        $response = $this->actingAs($this->quality)->postJson('/api/knowledge-documents', [
            'title' => 'Panduan Standar 5S di Area Produksi',
            'division_id' => $this->divisionProduksi->id,
            'type' => 'sop',
            'description' => 'Langkah Seiri, Seiton, Seiso, Seiketsu, Shitsuke.',
            'file' => UploadedFile::fake()->create('panduan-5s.pdf', 200, 'application/pdf'),
        ]);

        $response->assertCreated();
        $response->assertJson([
            'success' => true,
            'data' => [
                'title' => 'Panduan Standar 5S di Area Produksi',
                'type' => 'sop',
                'status' => 'published',
            ],
        ]);

        $this->assertDatabaseHas('knowledge_documents', [
            'title' => 'Panduan Standar 5S di Area Produksi',
            'type' => 'sop',
            'created_by' => $this->quality->id,
        ]);
    }

    public function test_user_can_bookmark_and_unbookmark_document(): void
    {
        $doc = KnowledgeDocument::create([
            'title' => 'SOP Keselamatan Kerja Listrik',
            'division_id' => $this->divisionEngineering->id,
            'type' => 'sop',
            'created_by' => $this->supervisorEngineering->id,
            'status' => 'published',
        ]);

        // 1. Bookmark document
        $bookmarkRes = $this->actingAs($this->employeeProduksi)->postJson("/api/knowledge-documents/{$doc->id}/bookmark");
        $bookmarkRes->assertCreated();
        $this->assertTrue($bookmarkRes->json('success'));

        $this->assertDatabaseHas('user_bookmarks', [
            'user_id' => $this->employeeProduksi->id,
            'knowledge_document_id' => $doc->id,
        ]);

        // 2. GET /api/knowledge-documents/{id} reflects is_bookmarked: true
        $showRes = $this->actingAs($this->employeeProduksi)->getJson("/api/knowledge-documents/{$doc->id}");
        $showRes->assertOk();
        $this->assertTrue($showRes->json('data.is_bookmarked'));

        // 3. GET /api/me/bookmarks lists the bookmarked document
        $myBookmarksRes = $this->actingAs($this->employeeProduksi)->getJson('/api/me/bookmarks');
        $myBookmarksRes->assertOk();
        $this->assertCount(1, $myBookmarksRes->json('data.data'));
        $this->assertSame($doc->id, $myBookmarksRes->json('data.data.0.id'));

        // 4. Delete bookmark
        $deleteRes = $this->actingAs($this->employeeProduksi)->deleteJson("/api/knowledge-documents/{$doc->id}/bookmark");
        $deleteRes->assertOk();
        $this->assertTrue($deleteRes->json('success'));

        $this->assertDatabaseMissing('user_bookmarks', [
            'user_id' => $this->employeeProduksi->id,
            'knowledge_document_id' => $doc->id,
        ]);

        // 5. GET /api/me/bookmarks is now empty
        $emptyRes = $this->actingAs($this->employeeProduksi)->getJson('/api/me/bookmarks');
        $emptyRes->assertOk();
        $this->assertCount(0, $emptyRes->json('data.data'));
    }

    public function test_cannot_duplicate_bookmark_for_same_user(): void
    {
        $doc = KnowledgeDocument::create([
            'title' => 'Dokumen Spesifikasi Material',
            'division_id' => $this->divisionProduksi->id,
            'type' => 'dokumen',
            'created_by' => $this->supervisorProduksi->id,
            'status' => 'published',
        ]);

        // First bookmark succeeds
        $first = $this->actingAs($this->employeeProduksi)->postJson("/api/knowledge-documents/{$doc->id}/bookmark");
        $first->assertCreated();

        // Second bookmark attempt fails
        $second = $this->actingAs($this->employeeProduksi)->postJson("/api/knowledge-documents/{$doc->id}/bookmark");
        $second->assertStatus(422);
        $this->assertFalse($second->json('success'));
        $this->assertStringContainsString('sudah tersimpan', $second->json('message'));

        // Ensure database table only has exactly 1 record
        $this->assertSame(
            1,
            UserBookmark::where('user_id', $this->employeeProduksi->id)
                ->where('knowledge_document_id', $doc->id)
                ->count()
        );
    }

    public function test_rbac_delete_knowledge_document(): void
    {
        $docProduksi = KnowledgeDocument::create([
            'title' => 'Prosedur Kalibrasi Mesin Produksi',
            'division_id' => $this->divisionProduksi->id,
            'type' => 'sop',
            'created_by' => $this->supervisorProduksi->id,
            'status' => 'published',
        ]);

        $docEngineering = KnowledgeDocument::create([
            'title' => 'Wiring Diagram Generator',
            'division_id' => $this->divisionEngineering->id,
            'type' => 'dokumen',
            'created_by' => $this->supervisorEngineering->id,
            'status' => 'published',
        ]);

        // 1. Employee cannot delete any document
        $empRes = $this->actingAs($this->employeeProduksi)->deleteJson("/api/knowledge-documents/{$docProduksi->id}");
        $empRes->assertForbidden();

        // 2. Supervisor cannot delete other division's document
        $spvCrossRes = $this->actingAs($this->supervisorProduksi)->deleteJson("/api/knowledge-documents/{$docEngineering->id}");
        $spvCrossRes->assertForbidden();

        // 3. Supervisor CAN delete own division's document
        $spvOwnRes = $this->actingAs($this->supervisorProduksi)->deleteJson("/api/knowledge-documents/{$docProduksi->id}");
        $spvOwnRes->assertOk();
        $this->assertDatabaseMissing('knowledge_documents', ['id' => $docProduksi->id]);

        // 4. Quality can delete any division's document
        $qualityRes = $this->actingAs($this->quality)->deleteJson("/api/knowledge-documents/{$docEngineering->id}");
        $qualityRes->assertOk();
        $this->assertDatabaseMissing('knowledge_documents', ['id' => $docEngineering->id]);
    }

    /**
     * PRD v2.0 §3.2: Filter dan taksonomi berdasarkan Topik Pengetahuan.
     */
    public function test_knowledge_documents_can_be_filtered_by_topic_id(): void
    {
        $topicA = KnowledgeTopic::create([
            'name' => 'SOP Operasional Pabrik',
            'description' => 'Kumpulan SOP baku fasilitas produksi',
            'created_by' => $this->admin->id,
        ]);

        $topicB = KnowledgeTopic::create([
            'name' => 'Kebijakan HR & Peraturan Perusahaan',
            'description' => 'Tata tertib dan kebijakan SDM',
            'created_by' => $this->admin->id,
        ]);

        $docA = KnowledgeDocument::create([
            'title' => 'SOP Kalibrasi Sensor Mesin',
            'topic_id' => $topicA->id,
            'division_id' => $this->divisionEngineering->id,
            'type' => 'sop',
            'created_by' => $this->supervisorEngineering->id,
            'status' => 'published',
        ]);

        $docB = KnowledgeDocument::create([
            'title' => 'Buku Panduan Karyawan 2026',
            'topic_id' => $topicB->id,
            'division_id' => null,
            'type' => 'dokumen',
            'created_by' => $this->admin->id,
            'status' => 'published',
        ]);

        // Filter by topic A
        $resTopicA = $this->actingAs($this->employeeProduksi)->getJson("/api/knowledge-documents?topic_id={$topicA->id}");
        $resTopicA->assertOk();
        $this->assertCount(1, $resTopicA->json('data.data'));
        $this->assertSame($docA->id, $resTopicA->json('data.data.0.id'));
        $this->assertSame('SOP Operasional Pabrik', $resTopicA->json('data.data.0.topic.name'));

        // Filter by topic B
        $resTopicB = $this->actingAs($this->employeeProduksi)->getJson("/api/knowledge-documents?topic_id={$topicB->id}");
        $resTopicB->assertOk();
        $this->assertCount(1, $resTopicB->json('data.data'));
        $this->assertSame($docB->id, $resTopicB->json('data.data.0.id'));
        $this->assertSame('Kebijakan HR & Peraturan Perusahaan', $resTopicB->json('data.data.0.topic.name'));
    }

    /**
     * PRD v2.0 §3.2: Pencarian mencakup pencarian nama topik.
     */
    public function test_search_matches_topic_name(): void
    {
        $topic = KnowledgeTopic::create([
            'name' => 'Standar Mutu ISO 9001',
            'description' => 'Manajemen jaminan kualitas produk',
            'created_by' => $this->admin->id,
        ]);

        $doc = KnowledgeDocument::create([
            'title' => 'Instruksi Pengujian Kekuatan Tarik',
            'topic_id' => $topic->id,
            'division_id' => $this->divisionProduksi->id,
            'type' => 'dokumen',
            'description' => 'Metode uji mekanik sampel logam',
            'created_by' => $this->quality->id,
            'status' => 'published',
        ]);

        // Cari dengan keyword nama topik "ISO 9001"
        $res = $this->actingAs($this->employeeProduksi)->getJson('/api/knowledge-documents?q=ISO+9001');
        $res->assertOk();
        $this->assertCount(1, $res->json('data.data'));
        $this->assertSame($doc->id, $res->json('data.data.0.id'));
    }

    /**
     * PRD v2.0 §3.2: Knowledge Repository TIDAK memberi poin KPI apapun (baik XP maupun Poin CPS ERA) — murni referensi pasif.
     */
    public function test_passive_reference_rule_awards_zero_points_and_zero_xp(): void
    {
        $topic = KnowledgeTopic::create([
            'name' => 'K3 & Tanggap Darurat',
            'created_by' => $this->admin->id,
        ]);

        $doc = KnowledgeDocument::create([
            'title' => 'Prosedur Evakuasi Kebakaran Pabrik',
            'topic_id' => $topic->id,
            'division_id' => $this->divisionProduksi->id,
            'type' => 'sop',
            'file_url' => 'knowledge-documents/files/evakuasi.pdf',
            'created_by' => $this->supervisorProduksi->id,
            'status' => 'published',
        ]);

        $initialXp = $this->employeeProduksi->fresh()->xp;
        $initialTransactionsCount = PointTransaction::where('user_id', $this->employeeProduksi->id)->count();
        $initialKpiCount = UserKpiYearly::where('user_id', $this->employeeProduksi->id)->count();

        // 1. Employee mengakses listing dokumen
        $this->actingAs($this->employeeProduksi)->getJson('/api/knowledge-documents')->assertOk();

        // 2. Employee membuka detail dokumen
        $showRes = $this->actingAs($this->employeeProduksi)->getJson("/api/knowledge-documents/{$doc->id}");
        $showRes->assertOk();
        $this->assertNotNull($showRes->json('data.download_url'));

        // 3. Employee melakukan bookmark dokumen
        $this->actingAs($this->employeeProduksi)->postJson("/api/knowledge-documents/{$doc->id}/bookmark")->assertCreated();

        // 4. Verifikasi saldo XP, PointTransaction, dan KPI TETAP NOL / TIDAK BERTAMBAH
        $this->assertSame($initialXp, $this->employeeProduksi->fresh()->xp);
        $this->assertSame($initialTransactionsCount, PointTransaction::where('user_id', $this->employeeProduksi->id)->count());
        $this->assertSame($initialKpiCount, UserKpiYearly::where('user_id', $this->employeeProduksi->id)->count());
    }

    /**
     * PRD v2.0 §3.2: Dua sumber berjalan paralel: Kurasi manual (topic_id) vs Lesson Learned otomatis BA (source_ba_id).
     */
    public function test_dual_content_source_manual_curation_and_automated_ba_lesson_learned(): void
    {
        $topic = KnowledgeTopic::create([
            'name' => 'Instruksi Kerja Pemeliharaan',
            'created_by' => $this->admin->id,
        ]);

        // Sumber 1: Kurasi manual oleh Admin/Supervisor dengan topic_id
        $manualDoc = KnowledgeDocument::create([
            'title' => 'Instruksi Kerja Pelumasan Roda Gigi',
            'topic_id' => $topic->id,
            'division_id' => $this->divisionProduksi->id,
            'type' => 'sop',
            'created_by' => $this->admin->id,
            'status' => 'published',
        ]);

        // Sumber 2: Lesson learned otomatis hasil BA approved
        $incident = BaIncident::create([
            'nomor_ba' => 'BA-202609-999',
            'judul' => 'Insiden Overheat Bearing Pompa',
            'kronologi' => 'Bearing pompa hidrolik mengalami overheat akibat kurang pelumasan berkala.',
            'penyebab' => 'Jadwal greasing terlambat 2 minggu.',
            'kategori' => 'kerusakan_mesin',
            'tingkat_keparahan' => 'sedang',
            'division_id' => $this->divisionProduksi->id,
            'pelapor_id' => $this->employeeProduksi->id,
            'created_by' => $this->employeeProduksi->id,
            'status' => 'approved',
        ]);

        $lessonDoc = KnowledgeDocument::create([
            'title' => 'Lesson Learned: Insiden Overheat Bearing Pompa',
            'topic_id' => $topic->id,
            'division_id' => $this->divisionProduksi->id,
            'type' => 'lesson_learned',
            'source_ba_id' => $incident->id,
            'description' => 'Pelajaran dari insiden: pastikan jadwal greasing harian tercatat di checklist digital.',
            'created_by' => $this->quality->id,
            'status' => 'published',
        ]);

        // Keduanya tampil di repository
        $res = $this->actingAs($this->employeeProduksi)->getJson("/api/knowledge-documents?topic_id={$topic->id}");
        $res->assertOk();
        $this->assertCount(2, $res->json('data.data'));

        // Cek bahwa sumber BA memiliki data source_ba terhubung
        $items = collect($res->json('data.data'));
        $manualItem = $items->firstWhere('id', $manualDoc->id);
        $lessonItem = $items->firstWhere('id', $lessonDoc->id);

        $this->assertNull($manualItem['source_ba']);
        $this->assertSame('BA-202609-999', $lessonItem['source_ba']['nomor_ba']);
    }

    /**
     * PRD v2.0 §3.2: Format konten PDF dan Microsoft Office (.docx, .xlsx).
     */
    public function test_manual_creation_supports_pdf_and_office_file_formats(): void
    {
        $topic = KnowledgeTopic::create([
            'name' => 'Formulir & Template Mutu',
            'created_by' => $this->admin->id,
        ]);

        // 1. Upload file Word (.docx)
        $docWordRes = $this->actingAs($this->quality)->postJson('/api/knowledge-documents', [
            'title' => 'Template Lembar Periksa Harian QC',
            'topic_id' => $topic->id,
            'division_id' => $this->divisionProduksi->id,
            'type' => 'dokumen',
            'description' => 'Template berkas Microsoft Word untuk checklist inspeksi shift.',
            'file' => UploadedFile::fake()->create('template-qc.docx', 150, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]);

        $docWordRes->assertCreated();
        $this->assertNotNull($docWordRes->json('data.file_url'));

        // 2. Upload file Excel (.xlsx)
        $docExcelRes = $this->actingAs($this->quality)->postJson('/api/knowledge-documents', [
            'title' => 'Kalkulator Toleransi Dimensi Mesin Bubut',
            'topic_id' => $topic->id,
            'division_id' => $this->divisionProduksi->id,
            'type' => 'dokumen',
            'description' => 'Lembar kerja Microsoft Excel untuk verifikasi deviasi toleransi.',
            'file' => UploadedFile::fake()->create('kalkulator-toleransi.xlsx', 180, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ]);

        $docExcelRes->assertCreated();
        $this->assertNotNull($docExcelRes->json('data.file_url'));
    }

    /**
     * PRD v2.0 §3.2: API Knowledge Topics CRUD & Authorization.
     */
    public function test_knowledge_topics_api_crud_and_authorization(): void
    {
        // 1. Employee dilarang membuat topik
        $empCreate = $this->actingAs($this->employeeProduksi)->postJson('/api/knowledge-topics', [
            'name' => 'Topik Tak Berizin',
        ]);
        $empCreate->assertForbidden();

        // 2. Admin dapat membuat topik
        $adminCreate = $this->actingAs($this->admin)->postJson('/api/knowledge-topics', [
            'name' => 'Tata Kelola Gudang Raw Material',
            'description' => 'Standar penyimpanan dan safety stock',
        ]);
        $adminCreate->assertCreated();
        $topicId = $adminCreate->json('data.id');

        // 3. Semua role (termasuk Employee) dapat melihat daftar topik
        $listRes = $this->actingAs($this->employeeProduksi)->getJson('/api/knowledge-topics');
        $listRes->assertOk();
        $this->assertTrue(collect($listRes->json('data'))->contains('name', 'Tata Kelola Gudang Raw Material'));

        // 4. Admin dapat mengupdate topik
        $updateRes = $this->actingAs($this->admin)->patchJson("/api/knowledge-topics/{$topicId}", [
            'name' => 'Tata Kelola Gudang RM & Komponen',
        ]);
        $updateRes->assertOk();
        $this->assertSame('Tata Kelola Gudang RM & Komponen', $updateRes->json('data.name'));

        // 5. Admin dapat menghapus topik
        $deleteRes = $this->actingAs($this->admin)->deleteJson("/api/knowledge-topics/{$topicId}");
        $deleteRes->assertOk();
        $this->assertDatabaseMissing('knowledge_topics', ['id' => $topicId]);
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
