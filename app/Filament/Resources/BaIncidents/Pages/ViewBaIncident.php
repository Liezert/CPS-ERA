<?php

namespace App\Filament\Resources\BaIncidents\Pages;

use App\Filament\Resources\BaIncidents\BaIncidentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBaIncident extends ViewRecord
{
    protected static string $resource = BaIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
