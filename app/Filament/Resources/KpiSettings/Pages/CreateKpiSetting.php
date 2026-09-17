<?php

namespace App\Filament\Resources\KpiSettings\Pages;

use App\Filament\Resources\KpiSettings\KpiSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKpiSetting extends CreateRecord
{
    protected static string $resource = KpiSettingResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
