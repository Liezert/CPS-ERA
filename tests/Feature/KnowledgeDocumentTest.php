<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Models\UserBookmark;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_manual_creation_rejects_lesson_learned(): void
    {
        // Attempting to manually create with type=lesson_learned must fail with 422
        $response = $this->actingAs($this->employeeProduksi)->postJson('/api/knowledge-documents', [
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
        $response = $this->actingAs($this->employeeProduksi)->postJson('/api/knowledge-documents', [
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
            'created_by' => $this->employeeProduksi->id,
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
}
