<?php

namespace Tests\Feature;

use App\Enums\QuizRelatedType;
use App\Filament\Resources\BaIncidents\Pages\ViewBaIncident;
use App\Livewire\Ba\Show as BaShow;
use App\Livewire\Learning\Index as LearningIndex;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\User;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Hasil laporan CAPA yang disetujui HRGA masuk ke Learning (bukan Knowledge Repository) dan
 * baru terbit setelah HRGA membuat post-test-nya.
 */
class BaLearningPipelineTest extends TestCase
{
    use RefreshDatabase;

    private User $reporter;

    private User $quality;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $produksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->reporter = User::factory()->create(['division_id' => $produksi->id]);
        $this->reporter->assignRole('employee');
        $this->quality = User::factory()->create(['division_id' => Division::where('name', 'Engineering')->value('id')]);
        $this->quality->assignRole('quality');
    }

    private function approvedIncident(): BaIncident
    {
        $service = app(BaIncidentService::class);
        $supervisor = User::factory()->create(['division_id' => $this->reporter->division_id]);
        $supervisor->assignRole('supervisor');

        $incident = $service->saveDraft([
            'division_id' => $this->reporter->division_id,
            'lokasi' => 'Line Assembly 2',
            'deskripsi_masalah' => 'Produk cacat ditemukan di akhir line.',
            'why_1' => 'Mesin terlalu panas',
            'kesimpulan_akar_masalah' => 'Perawatan preventif tidak terjadwal.',
            'koreksi_deskripsi' => 'Bersihkan filter',
            'korektif_deskripsi' => 'Buat jadwal perawatan mingguan',
        ], $this->reporter);
        $service->submitWithVideo($incident, $this->reporter, ['video_external_link' => 'https://company.video/capa-01']);
        $service->approveAsSupervisor($incident->fresh(), $supervisor);

        return $service->approve($incident->fresh(), $this->quality, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Tidak ada produk cacat selama 2 minggu.',
        ]);
    }

    public function test_approved_report_goes_to_learning_and_is_published_once_hr_creates_the_post_test(): void
    {
        $incident = $this->approvedIncident();
        $material = $incident->learningMaterial;

        // Tidak masuk Knowledge Repository; di Learning masih tersembunyi sampai post-test ada.
        $this->assertDatabaseMissing('knowledge_documents', ['source_ba_id' => $incident->id]);
        $this->assertSame('candidate', $material->status);
        Livewire::actingAs($this->reporter)->test(LearningIndex::class)->assertDontSee($material->title);
        Livewire::actingAs($this->reporter)->test(BaShow::class, ['incident' => $incident])
            ->assertSee('setelah HRGA menyiapkan post-test')
            ->assertDontSee('Buka di Learning');

        // Tombol "Buat Post-Test" di review CAPA membuka form kuis yang terikat ke materi Learning-nya.
        Livewire::actingAs($this->quality)->test(ViewBaIncident::class, ['record' => $incident->id])
            ->assertActionHasUrl('post_test', route('filament.admin.resources.quizzes.create', [
                'related_type' => QuizRelatedType::LearningMaterial->value,
                'related_id' => $material->id,
                'title' => 'Post-Test CAPA: '.$incident->nomor_ba,
            ]));

        // Form Filament mengisi related_type sebagai enum; post-test dibuat -> materi langsung terbit.
        $postTest = Quiz::create([
            'title' => 'Post-Test CAPA: '.$incident->nomor_ba,
            'type' => 'post_test',
            'related_type' => QuizRelatedType::LearningMaterial,
            'related_id' => $material->id,
            'points_reward' => 10,
        ]);

        $this->assertSame('published', $material->fresh()->status);
        $this->assertTrue($material->fresh()->postTest->is($postTest));
        Livewire::actingAs($this->reporter)->test(LearningIndex::class)->assertSee($material->title);
        Livewire::actingAs($this->reporter)->test(BaShow::class, ['incident' => $incident->fresh()])
            ->assertSee('Buka di Learning');

        // Setelah ada post-test, tombol di review CAPA beralih ke mode edit.
        Livewire::actingAs($this->quality)->test(ViewBaIncident::class, ['record' => $incident->id])
            ->assertActionHasUrl('post_test', route('filament.admin.resources.quizzes.edit', ['record' => $postTest->id]));
    }

    public function test_learning_page_embeds_drive_video_in_an_iframe(): void
    {
        $material = $this->approvedIncident()->learningMaterial;
        $material->update([
            'status' => 'published',
            'content_url' => 'https://drive.google.com/file/d/1IBe-loIfrBnJiVUG7SN__45M40MAnz-0/preview',
        ]);

        $this->actingAs($this->reporter)->get(route('learning.show', $material))
            ->assertOk()
            ->assertSee('<iframe src="https://drive.google.com/file/d/1IBe-loIfrBnJiVUG7SN__45M40MAnz-0/preview"', false)
            ->assertSee('https://drive.google.com/file/d/1IBe-loIfrBnJiVUG7SN__45M40MAnz-0/view', false)
            ->assertDontSee('Tonton Video di Tab Baru');
    }

    public function test_non_post_test_quizzes_and_regular_materials_are_not_auto_published(): void
    {
        $incident = $this->approvedIncident();
        $material = $incident->learningMaterial;

        Quiz::create([
            'title' => 'Misi terkait',
            'type' => 'mission_quiz',
            'related_type' => QuizRelatedType::LearningMaterial->value,
            'related_id' => $material->id,
            'points_reward' => 10,
        ]);
        $this->assertSame('candidate', $material->fresh()->status);

        // Draf materi biasa (bukan dari CAPA) tetap draf walau diberi post-test.
        $draft = LearningMaterial::create([
            'learning_category_id' => $material->learning_category_id,
            'title' => 'Materi draf biasa',
            'type' => 'artikel',
            'status' => 'draft',
            'created_by' => $this->quality->id,
        ]);
        Quiz::create([
            'title' => 'Post-test draf',
            'type' => 'post_test',
            'related_type' => QuizRelatedType::LearningMaterial->value,
            'related_id' => $draft->id,
            'points_reward' => 10,
        ]);
        $this->assertSame('draft', $draft->fresh()->status);
    }
}
