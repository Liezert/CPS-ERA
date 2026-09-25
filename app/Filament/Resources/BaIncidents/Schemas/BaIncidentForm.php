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
use Filament\Support\Icons\Heroicon;

class BaIncidentForm
{
    /**
     * Helper text + ikon info bertooltip dari BaIncident::FIELD_GUIDES (sumber yang sama dengan form employee).
     */
    private static function guided(Textarea $field): Textarea
    {
        $guide = BaIncident::FIELD_GUIDES[$field->getName()];

        return $field
            ->helperText($guide['helper'])
            ->hintIcon(Heroicon::OutlinedInformationCircle, tooltip: collect($guide['guide'])
                ->map(fn (string $text, string $label): string => "{$label}: {$text}")
                ->values()
                ->map(fn (string $line, int $i): string => ($i + 1).'. '.$line)
                ->implode(' '));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dokumen & Kejadian')
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
                            ->required(),
                        static::guided(Textarea::make('deskripsi_masalah'))
                            ->label('Uraian Masalah / Ketidaksesuaian')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Analisis Akar Masalah (5 Whys)')
                    ->schema([
                        static::guided(Textarea::make('why_1'))
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
                        static::guided(Textarea::make('kesimpulan_akar_masalah'))
                            ->label('Kesimpulan Akar Masalah')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Tindakan Koreksi & Korektif')
                    ->schema([
                        static::guided(Textarea::make('koreksi_deskripsi'))
                            ->label('Tindakan Koreksi (Sementara)')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('koreksi_pic')
                            ->label('PIC Koreksi'),
                        TextInput::make('koreksi_waktu')
                            ->label('Waktu Pelaksanaan Koreksi'),
                        static::guided(Textarea::make('korektif_deskripsi'))
                            ->label('Tindakan Korektif (Akar Masalah)')
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

                // Field reviewer (status_verifikasi, bukti_objektif, alasan_tidak_efektif,
                // catatan_penolakan) sengaja tidak ada di form ini: hanya boleh diubah lewat
                // approveAction()/rejectAction(), yang memanggil BaIncidentService (lock,
                // idempotensi, pemberian poin). Form ini hanya untuk revisi isian employee.
            ]);
    }
}
