<?php

namespace Tests\Feature;

use App\Enums\BaIncidentStatus;
use App\Livewire\Ba\Create as BaCreate;
use App\Models\BaActivityLog;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Stage 2 alur approval CAPA: saveDraft() mempertahankan status revision_requested dan mencatat
 * field yang berubah, serta laporan yang sedang di-review/final tidak bisa diedit lagi.
 */
class SaveDraftRevisionTest extends TestCase
{
    use RefreshDatabase;

    private BaIncidentService $service;

    private Division $divisionProduksi;

    private User $reporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->service = app(BaIncidentService::class);
        $this->divisionProduksi = Division::where('name', 'Produksi')->firstOrFail();

        $this->reporter = User::factory()->create([
            'name' => 'Budi Operator',
            'division_id' => $this->divisionProduksi->id,
        ]);
        $this->reporter->assignRole('employee');
    }

    private function reportWithStatus(BaIncidentStatus $status): BaIncident
    {
        $incident = $this->service->saveDraft($this->formData(), $this->reporter);
        $incident->update(['status' => $status->value]);

        return $incident->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function formData(array $overrides = []): array
    {
        return array_merge([
            'division_id' => $this->divisionProduksi->id,
            'tanggal_pengisian' => '2026-09-20',
            'sumber_ketidaksesuaian' => 'laporan_ketidaksesuaian',
            'tanggal_masalah' => '2026-09-19',
            'lokasi' => 'Line Assembly 2',
            'deskripsi_masalah' => 'Produk cacat ditemukan di akhir line.',
            'why_1' => 'Mesin terlalu panas',
            'why_2' => 'Pendingin tidak berfungsi',
            'why_3' => 'Filter tersumbat',
            'why_4' => 'Jadwal perawatan terlewat',
            'why_5' => 'Belum ada pengingat perawatan',
            'kesimpulan_akar_masalah' => 'Perawatan preventif tidak terjadwal.',
            'koreksi_deskripsi' => 'Bersihkan filter',
            'koreksi_pic' => 'Teknisi A',
            'koreksi_waktu' => '2026-09-21',
            'korektif_deskripsi' => 'Buat jadwal perawatan mingguan',
            'korektif_pic' => 'Supervisor Maintenance',
            'korektif_waktu' => '2026-09-30',
            'is_potensi_risiko' => true,
            'is_potensi_peluang' => false,
        ], $overrides);
    }

    /**
     * @return list<string>
     */
    private function revisionNotes(BaIncident $incident): array
    {
        return BaActivityLog::where('ba_incident_id', $incident->id)
            ->where('action', 'Revisi BA disimpan')
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('note')
            ->all();
    }

    public function test_save_draft_keeps_revision_requested_and_logs_changed_fields_on_every_save(): void
    {
        $incident = $this->reportWithStatus(BaIncidentStatus::RevisionRequested);

        $this->service->saveDraft($this->formData(['why_2' => 'Pompa pendingin rusak']), $this->reporter, $incident->id);
        $this->assertSame(BaIncidentStatus::RevisionRequested->value, $incident->fresh()->status);

        $this->service->saveDraft($this->formData([
            'why_2' => 'Pompa pendingin rusak',
            'kesimpulan_akar_masalah' => 'Tidak ada inspeksi pompa pendingin.',
            'is_potensi_peluang' => true,
        ]), $this->reporter, $incident->id);
        $this->assertSame(BaIncidentStatus::RevisionRequested->value, $incident->fresh()->status);

        $notes = $this->revisionNotes($incident);
        $this->assertCount(2, $notes);

        $this->assertStringContainsString('Diubah oleh Budi Operator', $notes[0]);
        $this->assertStringContainsString('Why 2 ("Pendingin tidak berfungsi" → "Pompa pendingin rusak")', $notes[0]);
        $this->assertStringNotContainsString('Kesimpulan', $notes[0]);

        $this->assertStringNotContainsString('Why 2', $notes[1]);
        $this->assertStringContainsString('Kesimpulan Akar Masalah', $notes[1]);
        $this->assertStringContainsString('Potensi Peluang ("Tidak" → "Ya")', $notes[1]);
    }

    public function test_revision_save_without_content_change_writes_no_log_entry(): void
    {
        $incident = $this->reportWithStatus(BaIncidentStatus::RevisionRequested);

        $this->service->saveDraft($this->formData(), $this->reporter, $incident->id);

        $this->assertSame(BaIncidentStatus::RevisionRequested->value, $incident->fresh()->status);
        $this->assertSame([], $this->revisionNotes($incident));
    }

    public function test_plain_draft_stays_draft_without_revision_log(): void
    {
        $incident = $this->reportWithStatus(BaIncidentStatus::Draft);

        $this->service->saveDraft($this->formData(['why_1' => 'Suhu ruangan tinggi']), $this->reporter, $incident->id);

        $this->assertSame(BaIncidentStatus::Draft->value, $incident->fresh()->status);
        $this->assertSame('Suhu ruangan tinggi', $incident->fresh()->why_1);
        $this->assertSame([], $this->revisionNotes($incident));
    }

    public function test_reports_under_review_or_final_cannot_be_saved_as_draft(): void
    {
        foreach ([BaIncidentStatus::PendingSupervisor, BaIncidentStatus::PendingHr, BaIncidentStatus::Approved] as $status) {
            $incident = $this->reportWithStatus($status);

            try {
                $this->service->saveDraft($this->formData(['why_1' => 'Diubah diam-diam']), $this->reporter, $incident->id);
                $this->fail("saveDraft() seharusnya menolak laporan berstatus {$status->value}.");
            } catch (DomainException $exception) {
                $this->assertStringContainsString($incident->nomor_ba, $exception->getMessage());
            }

            $this->assertSame($status->value, $incident->fresh()->status);
            $this->assertSame('Mesin terlalu panas', $incident->fresh()->why_1);
        }
    }

    public function test_edit_form_refuses_reports_under_review_or_final(): void
    {
        foreach ([BaIncidentStatus::PendingHr, BaIncidentStatus::Approved] as $status) {
            $incident = $this->reportWithStatus($status);

            $this->actingAs($this->reporter)
                ->get(route('ba.create', ['incidentId' => $incident->id]))
                ->assertForbidden();
        }
    }

    public function test_edit_form_opens_for_revision_requested_report(): void
    {
        $incident = $this->reportWithStatus(BaIncidentStatus::RevisionRequested);

        Livewire::actingAs($this->reporter)
            ->test(BaCreate::class, ['incidentId' => $incident->id])
            ->assertSet('baIncidentId', $incident->id)
            ->assertSet('why1', 'Mesin terlalu panas');
    }

    public function test_form_redirects_when_report_enters_review_while_open(): void
    {
        $incident = $this->reportWithStatus(BaIncidentStatus::RevisionRequested);

        $component = Livewire::actingAs($this->reporter)
            ->test(BaCreate::class, ['incidentId' => $incident->id]);

        $incident->update(['status' => BaIncidentStatus::PendingHr->value]);

        $component->set('why1', 'Diubah setelah masuk review')
            ->call('saveDraftOnly')
            ->assertRedirect(route('ba.show', $incident->id));

        $this->assertSame(BaIncidentStatus::PendingHr->value, $incident->fresh()->status);
        $this->assertSame('Mesin terlalu panas', $incident->fresh()->why_1);
    }

    public function test_report_rejected_by_hr_is_final_and_cannot_be_revised(): void
    {
        $incident = $this->reportWithStatus(BaIncidentStatus::Rejected);

        $this->assertFalse($incident->isEditable());

        $this->expectException(DomainException::class);
        $this->service->saveDraft($this->formData(['why_1' => 'Suhu ruangan tinggi']), $this->reporter, $incident->id);
    }
}
