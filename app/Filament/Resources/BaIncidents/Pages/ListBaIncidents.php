<?php

namespace App\Filament\Resources\BaIncidents\Pages;

use App\Filament\Resources\BaIncidents\BaIncidentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBaIncidents extends ListRecords
{
    protected static string $resource = BaIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
