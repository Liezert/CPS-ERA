<?php

namespace App\Filament\Resources\BaIncidents\Pages;

use App\Enums\BaIncidentStatus;
use App\Filament\Resources\BaIncidents\BaIncidentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBaIncidents extends ListRecords
{
    protected static string $resource = BaIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Tab antrean kerja di atas query resource. Query dasarnya sendiri tetap tanpa filter status
     * (Supervisor dibatasi divisinya, HR lintas divisi), supaya laporan yang sudah pindah tahap
     * tetap bisa dibuka lewat tab "Semua".
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'pending_supervisor' => $this->statusTab(BaIncidentStatus::PendingSupervisor),
            'pending_hr' => $this->statusTab(BaIncidentStatus::PendingHr),
            'all' => Tab::make('Semua'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return auth()->user()?->hasRole('supervisor') ? 'pending_supervisor' : 'pending_hr';
    }

    private function statusTab(BaIncidentStatus $status): Tab
    {
        return Tab::make($status->getLabel())
            ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', $status->value)->count())
            ->badgeColor($status->getColor())
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status->value));
    }
}
