<?php

namespace Tests\Feature\Security;

use App\Livewire\Ba\Create;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit pre-launch (IDOR): user A tidak boleh menimpa atau mengirim draf CAPA milik user B,
 * baik lewat form Livewire (property ID diubah dari klien) maupun langsung lewat service.
 */
class BaDraftOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private BaIncidentService $service;

    private User $owner;

    private User $intruder;

    private BaIncident $draft;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $produksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->service = app(BaIncidentService::class);

        // Penyusup sedivisi: kasus terburuk, karena ia bisa melihat draf rekan satu divisinya.
        $this->owner = User::factory()->create(['division_id' => $produksi->id]);
        $this->owner->assignRole('employee');
        $this->intruder = User::factory()->create(['division_id' => $produksi->id]);
        $this->intruder->assignRole('employee');

        $this->draft = $this->service->saveDraft($this->stepOneData('Isi asli milik pelapor'), $this->owner);
    }

    /**
     * @return array<string, mixed>
     */
    private function stepOneData(string $deskripsi): array
    {
        return [
            'division_id' => $this->owner->division_id,
            'lokasi' => 'Line Assembly 2',
            'deskripsi_masalah' => $deskripsi,
            'why_1' => 'Mesin terlalu panas',
            'kesimpulan_akar_masalah' => 'Perawatan preventif tidak terjadwal.',
            'koreksi_deskripsi' => 'Bersihkan filter',
            'korektif_deskripsi' => 'Buat jadwal perawatan mingguan',
        ];
    }

    /**
     * Jalankan upaya pembajakan; ditolak (terkunci/terlarang) adalah hasil yang diharapkan.
     */
    private function attempt(callable $action): void
    {
        try {
            $action();
        } catch (CannotUpdateLockedPropertyException|AuthorizationException) {
            // Ditolak sebelum data tersentuh.
        }
    }

    public function test_draft_id_properties_on_the_form_are_locked(): void
    {
        foreach (['baIncidentId', 'incidentId'] as $property) {
            try {
                Livewire::actingAs($this->intruder)->test(Create::class)->set($property, $this->draft->id);
                $this->fail("Property {$property} masih bisa diubah dari klien.");
            } catch (CannotUpdateLockedPropertyException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_intruder_cannot_overwrite_a_draft_through_the_form(): void
    {
        $this->attempt(fn () => Livewire::actingAs($this->intruder)->test(Create::class)
            ->set('baIncidentId', $this->draft->id)
            ->set('divisionId', $this->intruder->division_id)
            ->set('lokasi', 'Line Assembly 2')
            ->set('deskripsiMasalah', 'Ditimpa oleh penyusup')
            ->set('why1', 'Mesin terlalu panas')
            ->set('kesimpulanAkarMasalah', 'Perawatan preventif tidak terjadwal.')
            ->set('koreksiDeskripsi', 'Bersihkan filter')
            ->set('korektifDeskripsi', 'Buat jadwal perawatan mingguan')
            ->call('nextStep'));

        $this->assertSame('Isi asli milik pelapor', $this->draft->fresh()->deskripsi_masalah);
    }

    public function test_intruder_cannot_submit_a_draft_through_the_form(): void
    {
        $this->attempt(fn () => Livewire::actingAs($this->intruder)->test(Create::class)
            ->set('baIncidentId', $this->draft->id)
            ->set('videoMethod', 'link')
            ->set('videoExternalLink', 'https://company.video/penyusup')
            ->call('submit'));

        $this->assertSame('draft', $this->draft->fresh()->status);
        $this->assertNull($this->draft->fresh()->video);
    }

    public function test_intruder_cannot_save_or_submit_a_draft_through_the_service(): void
    {
        $this->assertThrows(
            fn () => $this->service->saveDraft($this->stepOneData('Ditimpa oleh penyusup'), $this->intruder, $this->draft->id),
            AuthorizationException::class,
        );
        $this->assertThrows(
            fn () => $this->service->submitWithVideo($this->draft->fresh(), $this->intruder, ['video_external_link' => 'https://company.video/penyusup']),
            AuthorizationException::class,
        );

        $this->assertSame('Isi asli milik pelapor', $this->draft->fresh()->deskripsi_masalah);
        $this->assertSame('draft', $this->draft->fresh()->status);
    }

    public function test_owner_can_still_edit_and_submit_their_draft(): void
    {
        Livewire::actingAs($this->owner)->test(Create::class, ['incidentId' => $this->draft->id])
            ->assertSet('baIncidentId', $this->draft->id)
            ->set('deskripsiMasalah', 'Diperbarui oleh pelapor sendiri')
            ->call('nextStep')
            ->assertHasNoErrors()
            ->set('videoMethod', 'link')
            ->set('videoExternalLink', 'https://company.video/pelapor')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('Diperbarui oleh pelapor sendiri', $this->draft->fresh()->deskripsi_masalah);
        $this->assertSame('pending_supervisor', $this->draft->fresh()->status);
    }

    /**
     * Pengaman jaring: kegagalan lain (mis. validasi) tidak boleh dianggap "ditolak".
     */
    public function test_attempt_helper_does_not_swallow_unrelated_errors(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->attempt(fn () => throw new \RuntimeException('bukan penolakan otorisasi'));
    }
}
