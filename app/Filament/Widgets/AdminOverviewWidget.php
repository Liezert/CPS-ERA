<?php

namespace App\Filament\Widgets;

use App\Enums\BaIncidentStatus;
use App\Models\BaIncident;
use App\Models\KnowledgeDocument;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * Matikan auto-polling bawaan StatsOverviewWidget.
     * Pada shared hosting dengan ~70 concurrent user, wire:poll
     * menimbulkan beban server berlebihan (hit tiap ~30 detik per user).
     */
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $learningCount = LearningMaterial::count();
        $quizCount = Quiz::count();
        $docCount = KnowledgeDocument::count();
        $baCount = BaIncident::count();
        $pendingBaCount = BaIncident::whereIn('status', [BaIncidentStatus::PendingSupervisor->value, BaIncidentStatus::PendingHr->value])->count();

        return [
            Stat::make('Materi Pembelajaran', (string) $learningCount)
                ->description('Total modul aktif di Learning Hub')
                ->icon(Heroicon::OutlinedBookmarkSquare)
                ->color('primary')
                ->url(route('filament.admin.resources.learning-materials.index')),

            Stat::make('Quiz & Post-Test', (string) $quizCount)
                ->description('Evaluasi materi & misi interaktif')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->url(route('filament.admin.resources.quizzes.index')),

            Stat::make('Knowledge Repository', (string) $docCount)
                ->description('Dokumen pengetahuan & SOP kerja')
                ->icon(Heroicon::OutlinedBookOpen)
                ->color('gray')
                ->url(route('filament.admin.resources.knowledge-documents.index')),

            Stat::make('Laporan CAPA', (string) $baCount)
                ->description("{$pendingBaCount} laporan menunggu review")
                ->icon(Heroicon::OutlinedDocumentText)
                ->color($pendingBaCount > 0 ? 'warning' : 'primary')
                ->url(route('filament.admin.resources.ba-incidents.index', ['tableFilters[status][value]' => 'draft'])),
        ];
    }
}
