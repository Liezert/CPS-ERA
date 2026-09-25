<?php

namespace App\Filament\Resources\KnowledgeDocuments\Tables;

use App\Models\KnowledgeDocument;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KnowledgeDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Pengetahuan')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('bold'),
                TextColumn::make('topic.name')
                    ->label('Topik')
                    ->placeholder('Belum Dikategorikan')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
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
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Waktu Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('topic_id')
                    ->label('Topik')
                    ->relationship('topic', 'name'),
                SelectFilter::make('division_id')
                    ->label('Divisi')
                    ->relationship('division', 'name'),
                SelectFilter::make('type')
                    ->label('Jenis Materi')
                    ->options([
                        'dokumen' => 'Dokumen',
                        'video' => 'Video',
                        'presentasi' => 'Presentasi',
                        'sop' => 'SOP',
                        'link' => 'Link',
                    ]),
                SelectFilter::make('status')
                    ->label('Status Publikasi')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (KnowledgeDocument $record): bool => auth()->user()?->can('update', $record) ?? false),
                DeleteAction::make()
                    ->visible(fn (KnowledgeDocument $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
                ]),
            ]);
    }
}
