<?php

namespace App\Filament\Resources\LearningMaterials\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                        'published' => 'Published',
                    ])
                    ->default('published')
                    ->required(),
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
            ]);
    }
}
