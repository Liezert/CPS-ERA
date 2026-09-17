<?php

namespace App\Filament\Resources\BaIncidents\Schemas;

use App\Models\BaIncident;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BaIncidentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dokumen & Kejadian')
                    ->description('Rincian identitas laporan ketidaksesuaian.')
                    ->schema([
                        Select::make('division_id')
                            ->label('Divisi Terkait')
                            ->relationship('division', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        DatePicker::make('tanggal_pengisian')
                            ->label('Tanggal Pengisian')
                            ->default(now())
                            ->required(),
                        Select::make('sumber_ketidaksesuaian')
                            ->label('Sumber Ketidaksesuaian')
                            ->options(BaIncident::SUMBER_OPTIONS)
                            ->default('laporan_ketidaksesuaian')
                            ->required()
                            ->live(),
                        TextInput::make('sumber_ketidaksesuaian_lainnya')
                            ->label('Rincian Sumber Lainnya')
                            ->visible(fn ($get) => $get('sumber_ketidaksesuaian') === 'lain_lain')
                            ->required(fn ($get) => $get('sumber_ketidaksesuaian') === 'lain_lain'),
                        DatePicker::make('tanggal_masalah')
                            ->label('Tanggal Kejadian Masalah')
                            ->default(now())
                            ->required(),
                        TextInput::make('lokasi')
                            ->label('Lokasi / Tempat Kejadian')
                            ->placeholder('Contoh: Lini Injeksi Mesin 02')
                            ->required(),
                        Textarea::make('deskripsi_masalah')
                            ->label('Uraian Masalah / Ketidaksesuaian')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Analisis Akar Masalah (5 Whys)')
                    ->description('Penelusuran kausalitas hingga menemukan akar penyebab utama.')
                    ->schema([
                        Textarea::make('why_1')
                            ->label('1. Mengapa hal itu terjadi? (Why 1)')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('why_2')
                            ->label('2. Mengapa? (Why 2 - Opsional)')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('why_3')
                            ->label('3. Mengapa? (Why 3 - Opsional)')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('why_4')
                            ->label('4. Mengapa? (Why 4 - Opsional)')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('why_5')
                            ->label('5. Mengapa? (Why 5 - Opsional)')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('kesimpulan_akar_masalah')
                            ->label('Kesimpulan Akar Masalah')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Tindakan Koreksi & Korektif')
                    ->description('Rencana penanganan sementara dan perbaikan permanen.')
                    ->schema([
                        Textarea::make('koreksi_deskripsi')
                            ->label('Tindakan Koreksi (Sementara)')
                            ->placeholder('Tindakan cepat di lokasi...')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('koreksi_pic')
                            ->label('PIC Koreksi'),
                        TextInput::make('koreksi_waktu')
                            ->label('Waktu Pelaksanaan Koreksi'),
                        Textarea::make('korektif_deskripsi')
                            ->label('Tindakan Korektif (Akar Masalah)')
                            ->placeholder('Solusi permanen jangka panjang...')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('korektif_pic')
                            ->label('PIC Korektif'),
                        TextInput::make('korektif_waktu')
                            ->label('Target Waktu Penyelesaian Korektif'),
                        Checkbox::make('is_potensi_risiko')
                            ->label('Terdapat Potensi Risiko Signifikan'),
                        Checkbox::make('is_potensi_peluang')
                            ->label('Terdapat Potensi Peluang Improvement'),
                    ])->columns(2),

                Section::make('Verifikasi Tindakan Korektif (Reviewer)')
                    ->description('Evaluasi efektivitas tindakan oleh Atasan / Admin.')
                    ->schema([
                        Select::make('status_verifikasi')
                            ->label('Status Verifikasi')
                            ->options([
                                'efektif' => 'Efektif',
                                'tidak_efektif' => 'Tidak Efektif',
                            ])
                            ->live()
                            ->disabled(fn () => ! auth()->user()?->hasAnyRole(['admin', 'supervisor'])),
                        Textarea::make('bukti_objektif')
                            ->label('Bukti Objektif Efektivitas')
                            ->visible(fn ($get) => $get('status_verifikasi') === 'efektif')
                            ->disabled(fn () => ! auth()->user()?->hasAnyRole(['admin', 'supervisor']))
                            ->columnSpanFull(),
                        Textarea::make('alasan_tidak_efektif')
                            ->label('Alasan Ketidakefektifan')
                            ->visible(fn ($get) => $get('status_verifikasi') === 'tidak_efektif')
                            ->disabled(fn () => ! auth()->user()?->hasAnyRole(['admin', 'supervisor']))
                            ->columnSpanFull(),
                        Textarea::make('catatan_penolakan')
                            ->label('Catatan Penolakan (Jika status Rejected)')
                            ->disabled(fn () => ! auth()->user()?->hasAnyRole(['admin', 'supervisor']))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
