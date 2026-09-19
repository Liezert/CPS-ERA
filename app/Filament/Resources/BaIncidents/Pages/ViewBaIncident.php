<?php

namespace App\Filament\Resources\BaIncidents\Pages;

use App\Enums\QuizRelatedType;
use App\Filament\Resources\BaIncidents\BaIncidentResource;
use App\Models\BaIncident;
use App\Models\Quiz;
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

            // Action Stage D: Buat / Edit Post-Test Pasca-Approval
            Action::make('post_test')
                ->label(fn (BaIncident $record): string => Quiz::where('related_type', QuizRelatedType::BaIncident->value)
                    ->where('related_id', $record->id)
                    ->exists() ? 'Edit Post-Test' : 'Buat Post-Test')
                ->icon(fn (BaIncident $record): string => Quiz::where('related_type', QuizRelatedType::BaIncident->value)
                    ->where('related_id', $record->id)
                    ->exists() ? 'heroicon-o-pencil-square' : 'heroicon-o-academic-cap')
                ->color(fn (BaIncident $record): string => Quiz::where('related_type', QuizRelatedType::BaIncident->value)
                    ->where('related_id', $record->id)
                    ->exists() ? 'gray' : 'primary')
                ->visible(fn (BaIncident $record): bool => $record->status === 'approved')
                ->url(function (BaIncident $record): string {
                    $existingQuiz = Quiz::where('related_type', QuizRelatedType::BaIncident->value)
                        ->where('related_id', $record->id)
                        ->first();

                    if ($existingQuiz) {
                        return route('filament.admin.resources.quizzes.edit', ['record' => $existingQuiz->id]);
                    }

                    return route('filament.admin.resources.quizzes.create', [
                        'related_type' => QuizRelatedType::BaIncident->value,
                        'related_id' => $record->id,
                        'title' => 'Post-Test CAPA: '.$record->nomor_ba,
                    ]);
                }),

            EditAction::make()
                ->visible(fn (BaIncident $record): bool => in_array($record->status, ['draft', 'created', 'rejected'], true) && auth()->user()->can('update', $record)),
        ];
    }
}
