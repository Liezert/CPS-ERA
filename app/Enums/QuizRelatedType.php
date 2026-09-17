<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuizRelatedType: string implements HasColor, HasLabel
{
    case BaIncident = 'ba_incident';
    case LearningMaterial = 'learning_material';
    case None = 'none';
    case Video = 'video';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::BaIncident => 'Laporan BA/CAPA',
            self::LearningMaterial => 'Materi Learning',
            self::None => 'Misi Mandiri',
            self::Video => 'Video Learning',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BaIncident => 'danger',
            self::LearningMaterial => 'info',
            self::None => 'gray',
            self::Video => 'success',
        };
    }
}
