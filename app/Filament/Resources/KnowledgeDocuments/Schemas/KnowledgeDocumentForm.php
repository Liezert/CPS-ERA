<?php

namespace App\Filament\Resources\KnowledgeDocuments\Schemas;

use App\Models\KnowledgeDocument;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KnowledgeDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul Pengetahuan')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('division_id')
                    ->label('Divisi / Kategori')
                    ->relationship('division', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('type')
                    ->label('Jenis Materi')
                    ->options([
                        'dokumen' => 'Dokumen',
                        'video' => 'Video',
                        'presentasi' => 'Presentasi',
                        'sop' => 'SOP',
                        'link' => 'Link',
                        'lesson_learned' => 'Lesson Learned (Otomatis dari BA)',
                    ])
                    ->default('dokumen')
                    ->required()
                    ->disableOptionWhen(fn (string $value): bool => $value === 'lesson_learned')
                    ->disabled(fn (?KnowledgeDocument $record): bool => $record?->type === 'lesson_learned')
                    ->helperText(fn (?KnowledgeDocument $record): string => $record?->type === 'lesson_learned'
                        ? 'Tipe Lesson Learned dibuat secara otomatis oleh sistem saat BA ditinjau dan tidak dapat diubah secara manual.'
                        : 'Pilih jenis materi pengetahuan manual (Dokumen, Video, Presentasi, SOP, Link).'),
                Select::make('status')
                    ->label('Status Publikasi')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->default('published')
                    ->required(),
                TextInput::make('external_link')
                    ->label('Tautan Eksternal (URL)')
                    ->url()
                    ->placeholder('https://...')
                    ->maxLength(255)
                    ->columnSpanFull(),
                FileUpload::make('file_url')
                    ->label('File Materi')
                    ->disk('public')
                    ->directory('knowledge-documents/files')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'video/mp4',
                        'image/*',
                    ])
                    ->maxSize(20480)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Deskripsi Materi')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
