<?php

namespace App\Filament\Resources\BaIncidents\Pages;

use App\Filament\Resources\BaIncidents\BaIncidentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBaIncident extends EditRecord
{
    protected static string $resource = BaIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
