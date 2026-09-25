<?php

namespace App\Filament\Widgets;

use App\Enums\BaIncidentStatus;
use App\Models\BaIncident;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions-widget';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'pendingDraftCount' => BaIncident::whereIn('status', [BaIncidentStatus::PendingSupervisor->value, BaIncidentStatus::PendingHr->value])->count(),
        ];
    }
}
