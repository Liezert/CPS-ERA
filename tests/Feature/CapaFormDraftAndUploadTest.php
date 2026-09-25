<?php

namespace Tests\Feature;

use App\Livewire\Ba\Create as BaCreate;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Form CAPA (permintaan owner 2026-09-24): isian yang belum tersimpan bisa dipulihkan setelah halaman
 * dimuat ulang, unggahan video punya progress bar + pratinjau, dan status tampil tanpa garis bawah.
 */
class CapaFormDraftAndUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $reporter;

    private Division $produksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->produksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->reporter = User::factory()->create(['division_id' => $this->produksi->id]);
        $this->reporter->assignRole('employee');
    }

    public function test_restore_local_draft_fills_only_whitelisted_step_one_fields(): void
    {
        Livewire::actingAs($this->reporter)->test(BaCreate::class)
            ->call('restoreLocalDraft', [
                'lokasi' => 'Line Assembly 2',
                'deskripsiMasalah' => 'Kemasan bocor halus pada lot B-2026.',
                'why1' => 'Seal kurang panas',
                'isPotensiRisiko' => true,
                'divisionId' => (string) $this->produksi->id,
                // Tidak boleh ikut dipulihkan: bukan field form Langkah 1.
                'step' => 2,
                'hasExistingDriveVideo' => true,
                'nomorBaPreview' => 'BA-PALSU',
            ])
            ->assertSet('lokasi', 'Line Assembly 2')
            ->assertSet('deskripsiMasalah', 'Kemasan bocor halus pada lot B-2026.')
            ->assertSet('why1', 'Seal kurang panas')
            ->assertSet('isPotensiRisiko', true)
            ->assertSet('divisionId', $this->produksi->id)
            ->assertSet('step', 1)
            ->assertSet('hasExistingDriveVideo', false)
            ->assertNotSet('nomorBaPreview', 'BA-PALSU');
    }

    public function test_saving_a_draft_puts_its_id_in_the_url_and_tells_the_browser(): void
    {
        $component = Livewire::actingAs($this->reporter)->test(BaCreate::class)
            ->set('lokasi', 'Gudang')
            ->call('saveDraftOnly');

        $incident = BaIncident::where('created_by', $this->reporter->id)->firstOrFail();

        $component->assertSet('incidentId', $incident->id)
            ->assertDispatched('capa-draft-saved', id: $incident->id);

        // Muat ulang halaman dengan ID draf di URL -> isian kembali dari database.
        Livewire::actingAs($this->reporter)
            ->withQueryParams(['incidentId' => $incident->id])
            ->test(BaCreate::class)
            ->assertSet('baIncidentId', $incident->id)
            ->assertSet('lokasi', 'Gudang');
    }

    public function test_page_ships_local_draft_autosave_and_restore_banner(): void
    {
        $this->actingAs($this->reporter)->get(route('ba.create'))
            ->assertOk()
            ->assertSee('cps-era:capa-draft:'.$this->reporter->id.':baru', false)
            ->assertSee('x-on:input.debounce.500ms="saveLocalDraft()"', false)
            ->assertSee('x-on:capa-draft-submitted.window', false)
            ->assertSee('Buang isian yang dipulihkan');
    }

    public function test_video_step_has_real_upload_progress_and_preview(): void
    {
        $component = Livewire::actingAs($this->reporter)->test(BaCreate::class)
            ->set('divisionId', $this->produksi->id)
            ->set('lokasi', 'Line 1')
            ->set('deskripsiMasalah', 'Kemasan bocor halus.')
            ->set('why1', 'Seal kurang panas')
            ->set('kesimpulanAkarMasalah', 'Suhu seal tidak dikontrol.')
            ->set('koreksiDeskripsi', 'Sortir ulang lot.')
            ->set('korektifDeskripsi', 'Pasang kontrol suhu seal.')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->assertSeeHtml('x-on:livewire-upload-progress="progress = $event.detail.progress"')
            ->assertSeeHtml('role="progressbar"')
            ->assertSeeHtml('x-on:change="setPreview($event.target.files[0])"');

        $component->set('videoFile', UploadedFile::fake()->create('penanganan.mp4', 1024, 'video/mp4'))
            ->assertSee('Berhasil terunggah')
            ->assertSee('penanganan.mp4')
            ->assertSee('Pratinjau Video')
            ->assertSeeHtml('<video');
    }

    public function test_status_badge_is_shown_without_underscores(): void
    {
        $render = fn (string $status): string => trim(strip_tags(Blade::render('<x-ui.badge :status="$s" />', ['s' => $status])));

        $this->assertSame('Pending Supervisor', $render('pending_supervisor'));
        $this->assertSame('Pending HR', $render('pending_hr'));
        $this->assertSame('Revision Requested', $render('revision_requested'));
        $this->assertSame('Draft', $render('draft'));
    }
}
