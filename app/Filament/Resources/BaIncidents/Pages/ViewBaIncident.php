<?php

namespace App\Filament\Resources\BaIncidents\Pages;

use App\Enums\QuizRelatedType;
use App\Filament\Resources\BaIncidents\BaIncidentResource;
use App\Models\BaIncident;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View as ViewContract;

class ViewBaIncident extends ViewRecord
{
    protected static string $resource = BaIncidentResource::class;

    public function getTitle(): string
    {
        return 'Review Laporan CAPA';
    }

    /**
     * Header & isi halaman memakai desain form pengisian employee (components/capa/form/*)
     * dalam mode readonly. Header actions tetap didaftarkan agar modal approve/reject
     * dapat di-mount dari tombol di action bar review.
     */
    public function getHeader(): ?ViewContract
    {
        return view('filament.resources.ba-incidents.review-header', [
            'record' => $this->getRecord(),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.resources.ba-incidents.review-form'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Action Stage C: Approve & Verifikasi langsung dari View page
            BaIncidentResource::approveAction(),

            // Action Stage C (Pendukung): Tolak / Minta Revisi langsung dari View page
            BaIncidentResource::rejectAction(),

            BaIncidentResource::deleteArchivedVideoAction(),

            // Stage D: Post-test pasca-approval menempel ke materi Learning hasil laporan ini;
            // begitu dibuat, materinya terbit di Learning (QuizObserver::publishBaLearningMaterial).
            Action::make('post_test')
                ->label(fn (BaIncident $record): string => $record->learningMaterial?->postTest ? 'Edit Post-Test' : 'Buat Post-Test')
                ->icon(fn (BaIncident $record): string => $record->learningMaterial?->postTest ? 'heroicon-o-pencil-square' : 'heroicon-o-academic-cap')
                ->color(fn (BaIncident $record): string => $record->learningMaterial?->postTest ? 'gray' : 'primary')
                ->visible(fn (BaIncident $record): bool => $record->status === 'approved' && $record->learningMaterial !== null)
                ->url(function (BaIncident $record): string {
                    $material = $record->learningMaterial;

                    if ($material->postTest) {
                        return route('filament.admin.resources.quizzes.edit', ['record' => $material->postTest->id]);
                    }

                    return route('filament.admin.resources.quizzes.create', [
                        'related_type' => QuizRelatedType::LearningMaterial->value,
                        'related_id' => $material->id,
                        'title' => 'Post-Test CAPA: '.$record->nomor_ba,
                    ]);
                }),

            EditAction::make()
                ->visible(fn (BaIncident $record): bool => $record->isEditable() && auth()->user()->can('update', $record)),
        ];
    }
}
