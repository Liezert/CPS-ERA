<?php

namespace App\Filament\Resources\LearningCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LearningCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kategori Learning')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
            ]);
    }
}
