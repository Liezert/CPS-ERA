<?php

namespace Tests\Feature;

use App\Models\BaActivityLog;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BaIncidentTest extends TestCase
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

    public function test_generate_nomor_ba_format_and_sequence(): void
    {
        $service = app(BaIncidentService::class);

        $num1 = $service->generateNomorBa(2026);
        $this->assertSame('BA-2026-0001', $num1);

        // Create incident with that number
        BaIncident::create([
            'nomor_ba' => $num1,
            'division_id' => $this->divisionProduksi->id,
            'file_ba_url' => '/storage/test_ba.pdf',
            'file_ftk_url' => '/storage/test_ftk.pdf',
            'status' => 'created',
            'created_by' => $this->employeeProduksi->id,
        ]);

        $num2 = $service->generateNomorBa(2026);
        $this->assertSame('BA-2026-0002', $num2);

        // Test annual sequence reset
        $numNextYear = $service->generateNomorBa(2027);
        $this->assertSame('BA-2027-0001', $numNextYear);
    }

    public function test_status_transitions_are_strictly_linear(): void
    {
        $service = app(BaIncidentService::class);

        $incident = $service->create(
            ['division_id' => $this->divisionProduksi->id],
            $this->employeeProduksi,
            UploadedFile::fake()->create('ba.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('ftk.pdf', 100, 'application/pdf')
        );

        $this->assertTrue($incident->isCreated());

        // Attempt invalid jump: created -> closed
        $this->expectException(DomainException::class);
        $service->close($incident, $this->supervisorProduksi);
    }

    public function test_cannot_transition_backwards(): void
    {
        $service = app(BaIncidentService::class);

        $incident = $service->create(
            ['division_id' => $this->divisionProduksi->id],
            $this->employeeProduksi,
            UploadedFile::fake()->create('ba.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('ftk.pdf', 100, 'application/pdf')
        );

        $reviewedIncident = $service->review($incident, $this->supervisorProduksi);
        $this->assertTrue($reviewedIncident->isReviewed());

        // Attempt review again on reviewed
        $this->expectException(DomainException::class);
        $service->review($reviewedIncident, $this->supervisorProduksi);
    }

    public function test_review_triggers_automatic_lesson_learned_knowledge_document(): void
    {
        $service = app(BaIncidentService::class);

        $incident = $service->create(
            ['division_id' => $this->divisionProduksi->id],
            $this->employeeProduksi,
            UploadedFile::fake()->create('kronologi.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('ftk.pdf', 100, 'application/pdf')
        );

        $this->assertDatabaseMissing('knowledge_documents', [
            'source_ba_id' => $incident->id,
        ]);

        $service->review($incident, $this->supervisorProduksi, 'Verified corrective action');

        $this->assertDatabaseHas('knowledge_documents', [
            'source_ba_id' => $incident->id,
            'division_id' => $this->divisionProduksi->id,
            'type' => 'lesson_learned',
            'status' => 'published',
            'created_by' => $this->supervisorProduksi->id,
        ]);

        $doc = KnowledgeDocument::where('source_ba_id', $incident->id)->first();
        $this->assertNotNull($doc);
        $this->assertStringContainsString($incident->nomor_ba, $doc->title);
    }

    public function test_ba_activity_logs_recorded_on_every_step(): void
    {
        $service = app(BaIncidentService::class);

        $incident = $service->create(
            ['division_id' => $this->divisionProduksi->id],
            $this->employeeProduksi,
            UploadedFile::fake()->create('ba.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('ftk.pdf', 100, 'application/pdf')
        );

        $this->assertDatabaseHas('ba_activity_logs', [
            'ba_incident_id' => $incident->id,
            'actor_id' => $this->employeeProduksi->id,
            'action' => 'BA dibuat',
        ]);

        $service->review($incident, $this->supervisorProduksi, 'Review catatan OK');

        $this->assertDatabaseHas('ba_activity_logs', [
            'ba_incident_id' => $incident->id,
            'actor_id' => $this->supervisorProduksi->id,
            'action' => 'Ditinjau oleh '.$this->supervisorProduksi->name,
        ]);

        $service->close($incident, $this->supervisorProduksi, 'Selesai');

        $this->assertDatabaseHas('ba_activity_logs', [
            'ba_incident_id' => $incident->id,
            'actor_id' => $this->supervisorProduksi->id,
            'action' => 'Ditutup oleh '.$this->supervisorProduksi->name,
        ]);

        $this->assertSame(3, BaActivityLog::where('ba_incident_id', $incident->id)->count());
    }

    public function test_rbac_supervisor_only_can_review_own_division(): void
    {
        $service = app(BaIncidentService::class);

        $incidentProduksi = $service->create(
            ['division_id' => $this->divisionProduksi->id],
            $this->employeeProduksi,
            UploadedFile::fake()->create('ba.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('ftk.pdf', 100, 'application/pdf')
        );

        // Supervisor Engineering CANNOT review Produksi incident
        $resForbidden = $this->actingAs($this->supervisorEngineering)
            ->patchJson("/api/ba-incidents/{$incidentProduksi->id}/review", [
                'note' => 'Unauthorized review',
            ]);
        $resForbidden->assertForbidden();

        // Employee CANNOT review
        $resEmpForbidden = $this->actingAs($this->employeeProduksi)
            ->patchJson("/api/ba-incidents/{$incidentProduksi->id}/review");
        $resEmpForbidden->assertForbidden();

        // Supervisor Produksi CAN review
        $resAllowed = $this->actingAs($this->supervisorProduksi)
            ->patchJson("/api/ba-incidents/{$incidentProduksi->id}/review", [
                'note' => 'Approved by supervisor',
            ]);
        $resAllowed->assertOk();
        $this->assertSame('approved', $incidentProduksi->fresh()->status);
    }

    public function test_quality_and_admin_can_review_any_division(): void
    {
        $service = app(BaIncidentService::class);

        // Quality reviews Produksi incident
        $incident1 = $service->create(
            ['division_id' => $this->divisionProduksi->id],
            $this->employeeProduksi,
            UploadedFile::fake()->create('ba.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('ftk.pdf', 100, 'application/pdf')
        );

        $resQuality = $this->actingAs($this->quality)
            ->patchJson("/api/ba-incidents/{$incident1->id}/review");
        $resQuality->assertOk();

        // Admin reviews Engineering incident
        $incident2 = $service->create(
            ['division_id' => $this->divisionEngineering->id],
            $this->supervisorEngineering,
            UploadedFile::fake()->create('ba.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('ftk.pdf', 100, 'application/pdf')
        );

        $resAdmin = $this->actingAs($this->admin)
            ->patchJson("/api/ba-incidents/{$incident2->id}/review");
        $resAdmin->assertOk();
    }

    public function test_api_endpoints_full_lifecycle(): void
    {
        // 1. POST /api/ba-incidents (Create)
        $createRes = $this->actingAs($this->employeeProduksi)->postJson('/api/ba-incidents', [
            'division_id' => $this->divisionProduksi->id,
            'file_ba' => UploadedFile::fake()->create('kronologi.pdf', 100, 'application/pdf'),
            'file_ftk' => UploadedFile::fake()->create('ftk.pdf', 100, 'application/pdf'),
        ]);

        $createRes->assertCreated();
        $incidentId = $createRes->json('data.id');
        $this->assertNotEmpty($incidentId);

        // 2. GET /api/ba-incidents (List)
        $listRes = $this->actingAs($this->employeeProduksi)->getJson('/api/ba-incidents');
        $listRes->assertOk();
        $this->assertNotEmpty($listRes->json('data.data'));

        // 3. GET /api/ba-incidents/{id} (Show)
        $showRes = $this->actingAs($this->employeeProduksi)->getJson("/api/ba-incidents/{$incidentId}");
        $showRes->assertOk();
        $this->assertSame('submitted', $showRes->json('data.status'));

        // 4. PATCH /api/ba-incidents/{id}/review
        $reviewRes = $this->actingAs($this->supervisorProduksi)->patchJson("/api/ba-incidents/{$incidentId}/review", [
            'note' => 'Validasi FTK beres',
        ]);
        $reviewRes->assertOk();
        $this->assertSame('approved', $reviewRes->json('data.status'));

        // 5. PATCH /api/ba-incidents/{id}/close
        $closeRes = $this->actingAs($this->supervisorProduksi)->patchJson("/api/ba-incidents/{$incidentId}/close", [
            'note' => 'Penanganan selesai',
        ]);
        $closeRes->assertOk();
        $this->assertSame('approved', $closeRes->json('data.status'));

        // 6. GET /api/ba-incidents/{id}/activity-log
        $logRes = $this->actingAs($this->employeeProduksi)->getJson("/api/ba-incidents/{$incidentId}/activity-log");
        $logRes->assertOk();
        $this->assertCount(3, $logRes->json('data'));
    }

    public function test_create_ba_via_json_payload_with_file_urls(): void
    {
        // Test creation using JSON payload with file_ba and file_ftk as strings (fallback user division)
        $res = $this->actingAs($this->employeeProduksi)->postJson('/api/ba-incidents', [
            'file_ba' => 'https://example.com/ba.pdf',
            'file_ftk' => 'https://example.com/ftk.pdf',
        ]);

        $res->assertCreated();
        $this->assertNotEmpty($res->json('data.nomor_ba'));
        $this->assertSame($this->divisionProduksi->id, $res->json('data.division_id'));
    }
}
