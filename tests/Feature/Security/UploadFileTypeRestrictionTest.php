<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\KnowledgeDocuments\Pages\CreateKnowledgeDocument;
use App\Filament\Resources\LearningMaterials\Pages\CreateLearningMaterial;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\FakesGoogleDrive;
use Tests\TestCase;

/**
 * Audit pre-launch: berkas yang disimpan ke disk 'public' tidak boleh berupa HTML/SVG
 * (dilayani dari origin aplikasi -> stored XSS). Dokumen valid (PDF) tetap diterima.
 */
class UploadFileTypeRestrictionTest extends TestCase
{
    use FakesGoogleDrive;
    use RefreshDatabase;

    private User $quality;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake('public');

        $this->quality = User::factory()->create(['division_id' => Division::where('name', 'Engineering')->value('id')]);
        $this->quality->assignRole('quality');
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function maliciousFiles(): array
    {
        return [
            'html' => UploadedFile::fake()->createWithContent('panduan.html', '<html><script>alert(document.cookie)</script></html>'),
            'svg' => UploadedFile::fake()->createWithContent('diagram.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'),
        ];
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('sop.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
    }

    private function createLearningMaterial(UploadedFile $file): Testable
    {
        $category = LearningCategory::firstOrCreate(['name' => 'Kategori Uji'], ['created_by' => $this->quality->id]);

        return Livewire::actingAs($this->quality)
            ->test(CreateLearningMaterial::class)
            ->fillForm([
                'learning_category_id' => $category->id,
                'title' => 'Materi '.$file->getClientOriginalName(),
                'type' => 'dokumen',
                'status' => 'published',
                'content_url' => $file,
            ])
            ->call('create');
    }

    private function createKnowledgeDocument(UploadedFile $file): Testable
    {
        return Livewire::actingAs($this->quality)
            ->test(CreateKnowledgeDocument::class)
            ->fillForm([
                'title' => 'Dokumen '.$file->getClientOriginalName(),
                'division_id' => $this->quality->division_id,
                'type' => 'dokumen',
                'status' => 'published',
                'file_url' => $file,
            ])
            ->call('create');
    }

    public function test_learning_material_upload_rejects_html_and_svg(): void
    {
        foreach ($this->maliciousFiles() as $type => $file) {
            $this->createLearningMaterial($file)->assertHasFormErrors(['content_url']);
            $this->assertSame(0, LearningMaterial::count(), "Berkas {$type} tidak boleh tersimpan.");
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_learning_material_upload_accepts_a_valid_pdf(): void
    {
        $this->createLearningMaterial($this->pdf())->assertHasNoFormErrors();

        $this->assertSame(1, LearningMaterial::count());
    }

    public function test_knowledge_document_upload_rejects_html_and_svg(): void
    {
        foreach ($this->maliciousFiles() as $type => $file) {
            $this->createKnowledgeDocument($file)->assertHasFormErrors(['file_url']);
            $this->assertSame(0, KnowledgeDocument::count(), "Berkas {$type} tidak boleh tersimpan.");
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_knowledge_document_upload_accepts_a_valid_pdf(): void
    {
        $this->connectDriveAccount();
        $this->fakeGoogleDrive();

        $this->createKnowledgeDocument($this->pdf())->assertHasNoFormErrors();

        $this->assertSame(1, KnowledgeDocument::count());
    }
}
