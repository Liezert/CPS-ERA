<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Enums\QuizRelatedType;
use App\Models\BaIncident;
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
                    ->default(fn () => request()->query('title'))
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
                    ->default(fn () => request()->filled('related_type') ? 'post_test' : 'mission_quiz')
                    ->required(),
                TextInput::make('points_reward')
                    ->label('Reward Poin')
                    ->numeric()
                    ->default(10)
                    ->required(),
                Select::make('related_type')
                    ->label('Terkait Dengan')
                    ->options(QuizRelatedType::class)
                    // Post-test hasil CAPA ditempel ke materi Learning-nya (tombol Buat Post-Test di review CAPA);
                    // kuis yang ditempel langsung ke laporan BA tidak punya halaman pengerjaan bagi karyawan.
                    ->disableOptionWhen(fn (string $value): bool => $value === QuizRelatedType::BaIncident->value)
                    ->default(fn () => request()->query('related_type') ?? QuizRelatedType::None->value)
                    ->live(),
                Select::make('related_id')
                    ->label(function ($get): string {
                        $type = $get('related_type');
                        $value = $type instanceof \BackedEnum ? $type->value : (string) $type;

                        return match ($value) {
                            'ba_incident' => 'Laporan BA / CAPA Terkait',
                            'learning_material' => 'Materi Pembelajaran Terkait',
                            default => 'Entitas Terkait',
                        };
                    })
                    ->default(fn () => request()->query('related_id'))
                    ->options(function ($get): array {
                        $type = $get('related_type');
                        $value = $type instanceof \BackedEnum ? $type->value : (string) $type;

                        return match ($value) {
                            'ba_incident' => BaIncident::orderBy('created_at', 'desc')->get()->mapWithKeys(fn ($ba) => [
                                $ba->id => $ba->nomor_ba.' — '.($ba->title ?: 'CAPA '.$ba->nomor_ba),
                            ])->toArray(),
                            'learning_material' => LearningMaterial::orderBy('title')->pluck('title', 'id')->toArray(),
                            default => [],
                        };
                    })
                    ->searchable()
                    ->visible(function ($get): bool {
                        $type = $get('related_type');
                        $value = $type instanceof \BackedEnum ? $type->value : (string) $type;

                        return in_array($value, ['learning_material', 'ba_incident'], true);
                    }),
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
                        Toggle::make('allow_multiple_answers')
                            ->label('Bisa Lebih Dari 1 Jawaban Benar (Multi-Select)')
                            ->helperText('Jika diaktifkan, peserta harus memilih persis semua opsi benar untuk mendapatkan nilai penuh pada soal ini.')
                            ->default(false),
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
