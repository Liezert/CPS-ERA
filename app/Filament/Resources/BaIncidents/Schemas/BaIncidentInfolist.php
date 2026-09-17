<?php

namespace App\Filament\Resources\BaIncidents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BaIncidentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Laporan')
                    ->schema([
                        TextEntry::make('nomor_ba')
                            ->label('Nomor BA / FTK')
                            ->weight('bold')
                            ->copyable(),
                        TextEntry::make('division.name')
                            ->label('Divisi Terkait'),
                        TextEntry::make('status')
                            ->label('Status BA')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'submitted', 'created' => 'warning',
                                'approved', 'reviewed', 'closed' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('creator.name')
                            ->label('Dibuat Oleh'),
                        TextEntry::make('tanggal_pengisian')
                            ->label('Tanggal Pengisian')
                            ->date('d M Y'),
                        TextEntry::make('tanggal_masalah')
                            ->label('Tanggal Kejadian')
                            ->date('d M Y'),
                        TextEntry::make('lokasi')
                            ->label('Lokasi Kejadian'),
                        TextEntry::make('sumber_ketidaksesuaian')
                            ->label('Sumber Ketidaksesuaian')
                            ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', (string) $state))),
                        TextEntry::make('deskripsi_masalah')
                            ->label('Uraian Masalah / Ketidaksesuaian')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Analisis Akar Masalah (5 Whys)')
                    ->schema([
                        TextEntry::make('why_1')->label('Why 1')->columnSpanFull(),
                        TextEntry::make('why_2')->label('Why 2')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('why_3')->label('Why 3')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('why_4')->label('Why 4')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('why_5')->label('Why 5')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('kesimpulan_akar_masalah')
                            ->label('Kesimpulan Akar Masalah')
                            ->weight('bold')
                            ->columnSpanFull(),
                    ]),

                Section::make('Tindakan Koreksi & Korektif')
                    ->schema([
                        TextEntry::make('koreksi_deskripsi')->label('Tindakan Koreksi (Sementara)')->columnSpanFull(),
                        TextEntry::make('koreksi_pic')->label('PIC Koreksi')->placeholder('-'),
                        TextEntry::make('koreksi_waktu')->label('Waktu Koreksi')->placeholder('-'),
                        TextEntry::make('korektif_deskripsi')->label('Tindakan Korektif (Akar Masalah)')->columnSpanFull(),
                        TextEntry::make('korektif_pic')->label('PIC Korektif')->placeholder('-'),
                        TextEntry::make('korektif_waktu')->label('Target Waktu Korektif')->placeholder('-'),
                    ])->columns(2),

                Section::make('Video Penanganan & Bukti')
                    ->schema([
                        TextEntry::make('video.title')
                            ->label('Judul Video')
                            ->placeholder('Belum dilampirkan'),
                        TextEntry::make('video.video_external_link')
                            ->label('Tautan Video Eksternal')
                            ->url(fn ($record) => $record->video?->video_external_link, shouldOpenInNewTab: true)
                            ->placeholder('-'),
                        TextEntry::make('video.video_file_url')
                            ->label('Berkas Video Lokal')
                            ->url(fn ($record) => $record->video?->video_file_url, shouldOpenInNewTab: true)
                            ->placeholder('-'),
                    ])->columns(2),

                Section::make('Verifikasi & Peninjauan')
                    ->schema([
                        TextEntry::make('status_verifikasi')
                            ->label('Status Verifikasi')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'efektif' => 'success',
                                'tidak_efektif' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('Belum diverifikasi'),
                        TextEntry::make('reviewer.name')
                            ->label('Ditinjau Oleh')
                            ->placeholder('-'),
                        TextEntry::make('reviewed_at')
                            ->label('Waktu Review')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('bukti_objektif')
                            ->label('Bukti Objektif Efektivitas')
                            ->visible(fn ($record) => $record->status_verifikasi === 'efektif')
                            ->columnSpanFull(),
                        TextEntry::make('alasan_tidak_efektif')
                            ->label('Alasan Ketidakefektifan')
                            ->visible(fn ($record) => $record->status_verifikasi === 'tidak_efektif')
                            ->columnSpanFull(),
                        TextEntry::make('catatan_penolakan')
                            ->label('Catatan Penolakan / Revisi')
                            ->visible(fn ($record) => ! empty($record->catatan_penolakan))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
