<?php

namespace App\Filament\Resources\LearningMaterials\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LearningMaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Materi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dokumen' => 'primary',
                        'video' => 'info',
                        'presentasi' => 'warning',
                        'artikel' => 'success',
                        'tutorial' => 'purple',
                        'link' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'candidate' => 'info',
                        'draft' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'candidate' => 'Candidate (dari BA)',
                        default => ucfirst($state),
                    }),
                TextColumn::make('xp_reward')
                    ->label('XP Reward')
                    ->numeric()
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->toggleable(),
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
                SelectFilter::make('learning_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                SelectFilter::make('type')
                    ->label('Jenis Materi')
                    ->options([
                        'dokumen' => 'Dokumen',
                        'video' => 'Video',
                        'presentasi' => 'Presentasi',
                        'artikel' => 'Artikel',
                        'tutorial' => 'Tutorial',
                        'link' => 'Link',
                        'file_pendukung' => 'File Pendukung',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'candidate' => 'Candidate (dari BA)',
                        'published' => 'Published',
                        'draft' => 'Draft',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
