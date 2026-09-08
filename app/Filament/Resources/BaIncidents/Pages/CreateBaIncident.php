<?php

namespace App\Filament\Resources\BaIncidents\Pages;

use App\Filament\Resources\BaIncidents\BaIncidentResource;
use App\Services\BaIncidentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBaIncident extends CreateRecord
{
    protected static string $resource = BaIncidentResource::class;

    /**
     * Delegate record creation to BaIncidentService to ensure sequential number generation and activity logging.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(BaIncidentService::class)->create(
            $data,
            auth()->user(),
            $data['file_ba_url'],
            $data['file_ftk_url']
        );
    }
}
