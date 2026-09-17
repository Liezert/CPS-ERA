<?php

namespace App\Filament\Resources\KpiSettings\Pages;

use App\Filament\Resources\KpiSettings\KpiSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKpiSettings extends ListRecords
{
    protected static string $resource = KpiSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
