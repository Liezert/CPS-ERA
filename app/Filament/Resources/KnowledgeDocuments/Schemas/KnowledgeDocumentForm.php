<?php

namespace App\Filament\Resources\KnowledgeDocuments\Schemas;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeTopic;
use App\Models\User;
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
                Select::make('topic_id')
                    ->label('Topik Pengetahuan')
                    ->relationship('topic', 'name')
                    ->placeholder('Belum Dikategorikan')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nama Topik')
                            ->required()
                            ->maxLength(150),
                        Textarea::make('description')
                            ->label('Deskripsi / Cakupan Topik')
                            ->rows(3),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return KnowledgeTopic::create([
                            'name' => $data['name'],
                            'description' => $data['description'] ?? null,
                            'created_by' => auth()->id() ?? User::first()?->id ?? 1,
                        ])->id;
                    })
                    ->helperText('Pilih topik utama yang dikelola Admin/HRD'),
                Select::make('division_id')
                    ->label('Divisi (Filter Sekunder)')
                    ->relationship('division', 'name')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->helperText('Opsional: filter sekunder divisi yang berkaitan'),
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
                    ->label('File Materi (PDF / Microsoft Office / Dokumen)')
                    ->disk('public')
                    ->directory('knowledge-documents/files')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'video/mp4',
                        'image/*',
                    ])
                    ->maxSize(20480)
                    ->columnSpanFull()
                    ->helperText('Format didukung: PDF, Word (.doc, .docx), Excel (.xls, .xlsx), PowerPoint (.ppt, .pptx), Video MP4. Maksimal 20MB.'),
                Textarea::make('description')
                    ->label('Deskripsi Materi')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
