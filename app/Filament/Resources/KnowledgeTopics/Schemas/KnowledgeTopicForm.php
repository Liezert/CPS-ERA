<?php

namespace App\Filament\Resources\KnowledgeTopics\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KnowledgeTopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Topik Pengetahuan')
                    ->required()
                    ->maxLength(150)
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Deskripsi / Cakupan Topik')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
