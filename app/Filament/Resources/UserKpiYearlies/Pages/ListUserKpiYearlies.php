<?php

namespace App\Filament\Resources\UserKpiYearlies\Pages;

use App\Filament\Resources\UserKpiYearlies\UserKpiYearlyResource;
use Filament\Resources\Pages\ListRecords;

class ListUserKpiYearlies extends ListRecords
{
    protected static string $resource = UserKpiYearlyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
