<?php

namespace App\Filament\Resources\Quizzes\Tables;

use App\Enums\QuizRelatedType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Quiz / Misi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mission_case_study' => 'info',
                        'mission_quiz' => 'primary',
                        'post_test' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'mission_case_study' => 'Case Study',
                        'mission_quiz' => 'Quiz Mission',
                        'post_test' => 'Post-Test',
                        default => ucfirst($state),
                    }),
                TextColumn::make('points_reward')
                    ->label('Reward Poin')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn ($state) => "+{$state} Pts")
                    ->sortable(),
                TextColumn::make('questions_count')
                    ->label('Jumlah Soal')
                    ->counts('questions')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('related_type')
                    ->label('Kaitan')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => QuizRelatedType::tryFrom($state ?? '')?->getLabel() ?? $state)
                    ->color(fn (?string $state): string|array|null => QuizRelatedType::tryFrom($state ?? '')?->getColor() ?? 'gray')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Waktu Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'mission_case_study' => 'Mission: Case Study',
                        'mission_quiz' => 'Mission: Quiz',
                        'post_test' => 'Post-Test',
                    ]),
                SelectFilter::make('related_type')
                    ->label('Terkait')
                    ->options(QuizRelatedType::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
