<?php

namespace App\Filament\Resources\Achievements\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AchievementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Achievement')
                    ->required()
                    ->maxLength(150)
                    ->placeholder('Contoh: Knowledge Contributor'),
                TextInput::make('icon')
                    ->label('Identifier Icon (Heroicon)')
                    ->required()
                    ->maxLength(50)
                    ->default('heroicon-o-trophy')
                    ->placeholder('Contoh: heroicon-o-trophy'),
                Textarea::make('description')
                    ->label('Deskripsi / Syarat Pencapaian')
                    ->required()
                    ->rows(3)
                    ->placeholder('Jelaskan kontribusi yang dibutuhkan untuk mendapatkan badge ini'),
            ]);
    }
}
