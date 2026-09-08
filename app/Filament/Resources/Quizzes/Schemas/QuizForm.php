<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Models\LearningMaterial;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul Quiz / Misi')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('type')
                    ->label('Jenis')
                    ->options([
                        'mission_case_study' => 'Mission: Case Study',
                        'mission_quiz' => 'Mission: Quiz',
                        'post_test' => 'Post-Test (Learning)',
                    ])
                    ->default('mission_quiz')
                    ->required(),
                TextInput::make('points_reward')
                    ->label('Reward Poin')
                    ->numeric()
                    ->default(10)
                    ->required(),
                Select::make('related_type')
                    ->label('Terkait Dengan')
                    ->options([
                        'none' => 'Tidak Terkait (Misi Mandiri)',
                        'learning_material' => 'Learning Material (Post-Test)',
                        'ba_incident' => 'BA Incident',
                    ])
                    ->default('none')
                    ->live(),
                Select::make('related_id')
                    ->label('Materi Pembelajaran Terkait')
                    ->options(fn () => LearningMaterial::pluck('title', 'id'))
                    ->searchable()
                    ->visible(fn ($get) => $get('related_type') === 'learning_material'),
                Textarea::make('description')
                    ->label('Deskripsi / Petunjuk Pengerjaan')
                    ->rows(3)
                    ->columnSpanFull(),
                Repeater::make('questions')
                    ->label('Daftar Pertanyaan')
                    ->relationship('questions')
                    ->schema([
                        Textarea::make('question_text')
                            ->label('Teks Pertanyaan')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('order_index')
                            ->label('Nomor Urut')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Repeater::make('options')
                            ->label('Pilihan Jawaban')
                            ->relationship('options')
                            ->schema([
                                TextInput::make('option_text')
                                    ->label('Teks Pilihan')
                                    ->required()
                                    ->columnSpan(8),
                                Toggle::make('is_correct')
                                    ->label('Kunci Benar')
                                    ->default(false)
                                    ->columnSpan(4),
                            ])
                            ->columns(12)
                            ->minItems(2)
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}
