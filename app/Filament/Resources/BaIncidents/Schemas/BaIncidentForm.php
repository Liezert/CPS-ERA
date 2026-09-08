<?php

namespace App\Filament\Resources\BaIncidents\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class BaIncidentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('division_id')
                    ->label('Divisi')
                    ->relationship('division', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                FileUpload::make('file_ba_url')
                    ->label('File BA (Kronologi Kejadian)')
                    ->disk('public')
                    ->directory('ba-incidents/ba')
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/*'])
                    ->maxSize(20480)
                    ->required(),
                FileUpload::make('file_ftk_url')
                    ->label('Form Tindakan Korektif (FTK)')
                    ->disk('public')
                    ->directory('ba-incidents/ftk')
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/*'])
                    ->maxSize(20480)
                    ->required(),
            ]);
    }
}
