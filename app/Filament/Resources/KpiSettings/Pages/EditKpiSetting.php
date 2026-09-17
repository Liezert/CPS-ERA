<?php

namespace App\Filament\Resources\KpiSettings\Pages;

use App\Filament\Resources\KpiSettings\KpiSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKpiSetting extends EditRecord
{
    protected static string $resource = KpiSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
