<?php

namespace App\Filament\Resources\BaIncidents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BaIncidentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nomor_ba')
                    ->label('Nomor BA')
                    ->weight('bold')
                    ->copyable(),
                TextEntry::make('division.name')
                    ->label('Divisi'),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'warning',
                        'reviewed' => 'info',
                        'closed' => 'success',
                        default => 'gray',
                    }),
                TextEntry::make('creator.name')
                    ->label('Dibuat Oleh'),
                TextEntry::make('file_ba_url')
                    ->label('File BA (Kronologi)')
                    ->url(fn ($record) => $record->file_ba_url, shouldOpenInNewTab: true),
                TextEntry::make('file_ftk_url')
                    ->label('Form Tindakan Korektif (FTK)')
                    ->url(fn ($record) => $record->file_ftk_url, shouldOpenInNewTab: true),
                TextEntry::make('reviewer.name')
                    ->label('Ditinjau Oleh')
                    ->placeholder('-'),
                TextEntry::make('reviewed_at')
                    ->label('Waktu Review')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                TextEntry::make('closed_at')
                    ->label('Waktu Ditutup')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ]);
    }
}
