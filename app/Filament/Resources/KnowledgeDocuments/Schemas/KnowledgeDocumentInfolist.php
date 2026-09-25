<?php

namespace App\Filament\Resources\KnowledgeDocuments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KnowledgeDocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Materi Pengetahuan')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul Pengetahuan')
                            ->weight('bold')
                            ->columnSpanFull(),
                        TextEntry::make('topic.name')
                            ->label('Topik Pengetahuan')
                            ->placeholder('Belum Dikategorikan'),
                        TextEntry::make('division.name')
                            ->label('Divisi')
                            ->placeholder('Semua Divisi (Umum)'),
                        TextEntry::make('type')
                            ->label('Jenis Materi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'dokumen' => 'primary',
                                'video' => 'info',
                                'presentasi' => 'warning',
                                'sop' => 'success',
                                'link' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'sop' => 'SOP',
                                default => ucfirst($state),
                            }),
                        TextEntry::make('status')
                            ->label('Status Publikasi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'published' => 'success',
                                'draft' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        TextEntry::make('creator.name')
                            ->label('Dibuat Oleh')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Waktu Dibuat')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('external_link')
                            ->label('Tautan Eksternal (URL)')
                            ->url(fn ($record) => $record->external_link, shouldOpenInNewTab: true)
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label('Deskripsi Materi')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
