<?php

namespace App\Filament\Resources\LearningMaterials\Schemas;

use App\Models\LearningMaterial;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LearningMaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('learning_category_id')
                    ->label('Kategori Learning')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->label('Judul Materi')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('type')
                    ->label('Jenis Materi')
                    ->options([
                        'dokumen' => 'Dokumen',
                        'video' => 'Video',
                        'presentasi' => 'Presentasi',
                        'artikel' => 'Artikel',
                        'tutorial' => 'Tutorial',
                        'link' => 'Link',
                        'file_pendukung' => 'File Pendukung',
                    ])
                    ->default('dokumen')
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'candidate' => 'Candidate (dari BA)',
                        'published' => 'Published',
                    ])
                    ->default('published')
                    ->disabled(fn (?LearningMaterial $record): bool => (bool) ($record?->source_ba_id && ! auth()->user()?->can('manage-learning-materials')))
                    ->required(),
                TextInput::make('xp_reward')
                    ->label('XP Reward (Opsional)')
                    ->numeric()
                    ->nullable()
                    ->disabled(fn (): bool => ! (auth()->user()?->can('manage-learning-materials') ?? false))
                    ->helperText(fn (): string => (auth()->user()?->can('manage-learning-materials') ?? false)
                        ? 'Poin XP opsional yang didapat karyawan setelah menyelesaikan materi'
                        : 'Penetapan reward XP hanya dapat diatur oleh role Quality/HRGA dan Admin.'),
                TextInput::make('content_url')
                    ->label('Tautan / URL Konten')
                    ->url()
                    ->placeholder('https://...')
                    ->maxLength(255)
                    ->columnSpanFull(),
                FileUpload::make('content_url')
                    ->label('File Materi (Opsional)')
                    ->disk('public')
                    ->directory('learning-materials/files')
                    ->maxSize(20480)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(4)
                    ->columnSpanFull(),

                // Section Opsi Pembuatan Evaluasi Post-Test
                Section::make('Evaluasi Post-Test (Opsional)')
                    ->description('Sertakan evaluasi Post-Test pilihan ganda yang wajib diselesaikan user setelah materi tuntas.')
                    ->collapsible()
                    ->columnSpanFull()
                    ->components([
                        Toggle::make('has_post_test')
                            ->label('Aktifkan Post-Test untuk materi ini')
                            ->default(false)
                            ->live(),
                        TextInput::make('post_test_title')
                            ->label('Judul Post-Test')
                            ->placeholder('Contoh: Post-Test Pemahaman Materi')
                            ->required(fn ($get) => (bool) $get('has_post_test'))
                            ->visible(fn ($get) => (bool) $get('has_post_test'))
                            ->columnSpanFull(),
                        TextInput::make('post_test_points')
                            ->label('Reward Poin KPI')
                            ->numeric()
                            ->default(20)
                            ->required(fn ($get) => (bool) $get('has_post_test'))
                            ->visible(fn ($get) => (bool) $get('has_post_test')),
                        Textarea::make('post_test_description')
                            ->label('Petunjuk / Arahan Pengerjaan')
                            ->placeholder('Jawab seluruh pertanyaan evaluasi dengan teliti...')
                            ->rows(2)
                            ->visible(fn ($get) => (bool) $get('has_post_test'))
                            ->columnSpanFull(),
                        Repeater::make('post_test_questions')
                            ->label('Daftar Pertanyaan Post-Test')
                            ->visible(fn ($get) => (bool) $get('has_post_test'))
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
                    ]),
            ]);
    }
}
