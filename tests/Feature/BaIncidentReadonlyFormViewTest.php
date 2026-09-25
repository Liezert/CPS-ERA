<?php

namespace Tests\Feature;

use App\Filament\Resources\BaIncidents\Pages\ViewBaIncident;
use App\Livewire\Ba\Create as BaCreate;
use App\Livewire\Ba\Show as BaShow;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Halaman detail BA (Livewire Ba\Show) merender section 1-6 dengan komponen yang sama
 * dengan form pengisian employee (components/capa/form/*), dalam mode readonly.
 */
class BaIncidentReadonlyFormViewTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;

    protected User $admin;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->division = Division::where('name', 'Produksi')->firstOrFail();

        $this->admin = User::factory()->create(['division_id' => $this->division->id]);
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create(['division_id' => $this->division->id]);
        $this->employee->assignRole('employee');
    }

    protected function submittedIncident(array $overrides = []): BaIncident
    {
        return BaIncident::create(array_merge([
            'nomor_ba' => 'BA-2026-0077',
            'title' => 'CAPA: uji tampilan readonly',
            'division_id' => $this->division->id,
            'tanggal_pengisian' => '2026-09-10',
            'sumber_ketidaksesuaian' => 'audit',
            'tanggal_masalah' => '2026-09-09',
            'lokasi' => 'Lini Injeksi Nozzle 02',
            'deskripsi_masalah' => 'Suhu nozzle naik <script>alert(1)</script> & melewati batas',
            'why_1' => 'Temperatur nozzle melampaui 240C',
            'why_2' => 'Pendingin oli tidak mengalir',
            'why_3' => 'Katup solenoid tersumbat kerak',
            'why_4' => '',
            'why_5' => '',
            'kesimpulan_akar_masalah' => 'Tidak ada IK pembersihan saringan solenoid',
            'koreksi_deskripsi' => 'Mematikan mesin dan isolasi batch',
            'koreksi_pic' => 'Budi (SPV)',
            'koreksi_waktu' => 'Maks 1 Jam',
            'korektif_deskripsi' => 'Revisi checklist serah terima shift',
            'korektif_pic' => 'Siti (QC)',
            'korektif_waktu' => 'Maks 3 Hari',
            'is_potensi_risiko' => true,
            'is_potensi_peluang' => false,
            'status' => 'pending_hr', // menunggu review final HR (admin termasuk tim HR)
            'created_by' => $this->employee->id,
        ], $overrides));
    }

    public function test_detail_ba_menampilkan_enam_section_form_employee_dengan_nilai_readonly(): void
    {
        $incident = $this->submittedIncident();

        $html = Livewire::actingAs($this->admin)
            ->test(BaShow::class, ['incident' => $incident])
            ->assertSeeHtml('1. Informasi Dokumen &amp; Unit Kerja')
            ->assertSeeHtml('2. Sumber Ketidaksesuaian')
            ->assertSeeHtml('3. Informasi Kejadian &amp; Rincian Masalah')
            ->assertSeeHtml('4. Analisis Akar Masalah (5 Whys Causality Ladder)')
            ->assertSeeHtml('5. Rencana Penanganan: Tindakan Koreksi (Sementara) vs Tindakan Korektif (Akar Masalah)')
            ->assertSeeHtml('6. Identifikasi Dampak Lanjutan &amp; Potensi')
            ->html();

        // Nilai field tampil sebagai nilai statis (bukan binding Livewire).
        $this->assertStringContainsString('value="BA-2026-0077"', $html);
        $this->assertStringContainsString('value="2026-09-10"', $html);
        $this->assertStringContainsString('value="Lini Injeksi Nozzle 02"', $html);
        $this->assertStringContainsString('value="Katup solenoid tersumbat kerak"', $html);
        $this->assertStringContainsString('value="Siti (QC)"', $html);
        $this->assertStringContainsString('>Revisi checklist serah terima shift</textarea>', $html);
        $this->assertStringContainsString('Tidak ada IK pembersihan saringan solenoid</span>', $html, 'Banner rujukan section 5');

        // Pilihan tunggal & checkbox mencerminkan data BA.
        $this->assertMatchesRegularExpression('/<option value="'.$this->division->id.'"\s+selected/', $html);
        preg_match_all('/<input type="radio"[^>]*\bchecked\b[^>]*>/', $html, $checkedRadios);
        $this->assertCount(1, $checkedRadios[0], 'Tepat satu sumber ketidaksesuaian terpilih');
        $this->assertStringContainsString('value="audit"', $checkedRadios[0][0]);
        $this->assertSame(1, preg_match_all('/<input type="checkbox"[^>]*\bchecked\b/', $html), 'Hanya potensi risiko yang dicentang');

        // Readonly: tanpa binding, tanpa tombol edit 5 Whys.
        $this->assertStringNotContainsString('wire:model.live="lokasi"', $html);
        $this->assertStringNotContainsString('wire:model.live="why1"', $html);
        $this->assertStringNotContainsString('Tambah Tingkat Analisis Kausalitas', $html);
        $this->assertMatchesRegularExpression('/id="lokasi"[^>]*\breadonly\b/', $html);
        $this->assertMatchesRegularExpression('/id="division_id"[^>]*\bdisabled\b/', $html);

        // Tingkat Why tampil hanya bila terisi (why_3 terisi, why_4 kosong).
        $this->assertStringContainsString('visibleWhys >= 3 || true', $html);
        $this->assertStringContainsString('visibleWhys >= 4 || false', $html);

        // Isi berbahaya di-escape, dan penanda morph Livewire tidak masuk ke isi textarea.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertDoesNotMatchRegularExpression('/<textarea\b[^>]*>[^<]*<!--\[if/', $html);
    }

    public function test_employee_pembuat_melihat_tampilan_readonly_yang_sama(): void
    {
        $incident = $this->submittedIncident();

        Livewire::actingAs($this->employee)
            ->test(BaShow::class, ['incident' => $incident])
            ->assertSeeHtml('4. Analisis Akar Masalah (5 Whys Causality Ladder)')
            ->assertSeeHtml('value="Lini Injeksi Nozzle 02"')
            ->assertDontSeeHtml('wire:model.live="lokasi"');
    }

    public function test_sumber_lain_lain_menampilkan_rincian_readonly(): void
    {
        $incident = $this->submittedIncident([
            'sumber_ketidaksesuaian' => 'lain_lain',
            'sumber_ketidaksesuaian_lainnya' => 'Temuan patroli K3 harian',
        ]);

        Livewire::actingAs($this->admin)
            ->test(BaShow::class, ['incident' => $incident])
            ->assertSeeHtml('Sebutkan Rincian Sumber Lainnya')
            ->assertSeeHtml('value="Temuan patroli K3 harian"');
    }

    public function test_infolist_filament_mengikuti_isi_dan_urutan_section_form_employee(): void
    {
        $incident = $this->submittedIncident();

        $this->actingAs($this->admin)
            ->get("/admin/ba-incidents/{$incident->id}")
            ->assertOk()
            ->assertSeeInOrder([
                '1. Informasi Dokumen & Unit Kerja',
                'No. FTK / Register BA', 'Tanggal Pengisian', 'Bagian / Divisi',
                '2. Sumber Ketidaksesuaian',
                '3. Informasi Kejadian & Rincian Masalah',
                'Tanggal Kejadian Masalah', 'Lokasi / Tempat Kejadian', 'Uraian Masalah / Ketidaksesuaian',
                '4. Analisis Akar Masalah (5 Whys Causality Ladder)',
                'Katup solenoid tersumbat kerak',
                'Kesimpulan Akar Masalah (Root Cause)',
                '5. Rencana Penanganan: Tindakan Koreksi (Sementara) vs Tindakan Korektif (Akar Masalah)',
                'Tautan Rujukan Sasaran Tindakan Korektif',
                'PIC Pelaksana (Operator/SPV)', 'Batas Waktu Pelaksanaan',
                'PIC Penanggung Jawab', 'Target Tanggal Selesai',
                '6. Identifikasi Dampak Lanjutan & Potensi',
            ])
            ->assertSee('6. Identifikasi Dampak Lanjutan & Potensi')
            ->assertSee('7. Video Penanganan & Bukti')
            ->assertSee('8. Verifikasi & Approval Reviewer')
            // why_4 kosong: disembunyikan, sama seperti tampilan readonly form employee
            ->assertSee('visibleWhys >= 4 || false', false)
            // Tampilan admin = komponen form employee mode readonly
            ->assertSee('value="Lini Injeksi Nozzle 02"', false)
            ->assertDontSee('wire:model.live="lokasi"', false)
            // Tombol review admin dirender di action bar form
            ->assertSee('wire:click="mountAction(\'approve\')"', false)
            ->assertSee('wire:click="mountAction(\'reject\')"', false);
    }

    public function test_admin_dapat_menyetujui_dari_halaman_review(): void
    {
        $incident = $this->submittedIncident();

        Livewire::actingAs($this->admin)
            ->test(ViewBaIncident::class, ['record' => $incident->getRouteKey()])
            ->callAction('approve', ['status_verifikasi' => 'efektif', 'bukti_objektif' => 'Suhu stabil 230C selama 3 shift'])
            ->assertHasNoActionErrors();

        $this->assertSame('approved', $incident->fresh()->status);
    }

    public function test_form_edit_filament_tidak_memuat_field_reviewer(): void
    {
        $draft = $this->submittedIncident(['status' => 'draft']);

        $this->actingAs($this->admin)
            ->get("/admin/ba-incidents/{$draft->id}/edit")
            ->assertOk()
            ->assertSee('Analisis Akar Masalah (5 Whys)')
            ->assertDontSee('Verifikasi Tindakan Korektif (Reviewer)')
            ->assertDontSee('Bukti Objektif Efektivitas')
            ->assertDontSee('Catatan Penolakan (Jika status Rejected)');
    }

    public function test_form_employee_tetap_menampilkan_pesan_validasi_dari_komponen_bersama(): void
    {
        Livewire::actingAs($this->employee)
            ->test(BaCreate::class)
            ->set('lokasi', '')
            ->set('why1', '')
            ->call('nextStep')
            ->assertHasErrors(['lokasi', 'why1'])
            ->assertSeeHtml('wire:model.live="lokasi"')
            ->assertSee('Lokasi kejadian wajib diisi.')
            ->assertSee('Analisis Why pertama wajib diisi.');
    }
}
