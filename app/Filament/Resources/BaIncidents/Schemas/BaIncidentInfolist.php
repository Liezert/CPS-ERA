<?php

namespace App\Filament\Resources\BaIncidents\Schemas;

use App\Models\BaIncident;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class BaIncidentInfolist
{
    /**
     * Helper to render consistent section header with category badge (matching Employee POV).
     */
    protected static function sectionHeader(string $title, string $badge, string $variant = 'neutral'): HtmlString
    {
        return new HtmlString(view('components.capa.section-header', [
            'title' => $title,
            'badge' => $badge,
            'badgeVariant' => $variant,
        ])->render());
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── Status laporan (di luar form employee, setara header halaman detail) ──
                Section::make(static::sectionHeader('Status Laporan', 'Metadata'))
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status BA')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'warning',
                                'submitted', 'created' => 'info',
                                'approved', 'reviewed', 'closed' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'draft' => 'Draft',
                                'submitted' => 'Diajukan',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                                'closed' => 'Selesai',
                                default => ucfirst($state),
                            }),
                        TextEntry::make('creator.name')
                            ->label('Dibuat Oleh'),
                        TextEntry::make('created_at')
                            ->label('Waktu Dibuat')
                            ->dateTime('d M Y H:i'),
                    ])->columns(3),

                // Section 1-6 mengikuti isi & urutan form pengisian employee
                // (components/capa/form/*): judul, badge, dan label field sama persis.

                // ── Section 1: Informasi Dokumen & Unit Kerja ─────────────────
                Section::make(static::sectionHeader('1. Informasi Dokumen & Unit Kerja', 'Data Identifikasi'))
                    ->schema([
                        TextEntry::make('nomor_ba')
                            ->label('No. FTK / Register BA')
                            ->weight('bold')
                            ->copyable(),
                        TextEntry::make('tanggal_pengisian')
                            ->label('Tanggal Pengisian')
                            ->date('d M Y'),
                        TextEntry::make('division.name')
                            ->label('Bagian / Divisi'),
                    ])->columns(3),

                // ── Section 2: Sumber Ketidaksesuaian ────────────────────────
                Section::make(static::sectionHeader('2. Sumber Ketidaksesuaian', 'Pilihan Tunggal'))
                    ->schema([
                        TextEntry::make('sumber_ketidaksesuaian')
                            ->label('Kategori Sumber')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(fn (?string $state): string => BaIncident::SUMBER_OPTIONS[$state] ?? ucfirst(str_replace('_', ' ', (string) $state))),
                        TextEntry::make('sumber_ketidaksesuaian_lainnya')
                            ->label('Sebutkan Rincian Sumber Lainnya')
                            ->placeholder('-')
                            ->visible(fn ($record): bool => $record->sumber_ketidaksesuaian === 'lain_lain'),
                    ]),

                // ── Section 3: Informasi Kejadian & Rincian Masalah ──────────
                Section::make(static::sectionHeader('3. Informasi Kejadian & Rincian Masalah', 'Fakta Lapangan'))
                    ->schema([
                        TextEntry::make('tanggal_masalah')
                            ->label('Tanggal Kejadian Masalah')
                            ->date('d M Y'),
                        TextEntry::make('lokasi')
                            ->label('Lokasi / Tempat Kejadian'),
                        TextEntry::make('deskripsi_masalah')
                            ->label('Uraian Masalah / Ketidaksesuaian')
                            ->columnSpanFull(),
                    ])->columns(2),

                // ── Section 4: Analisis Akar Masalah (5 Whys) ────────────────
                Section::make(static::sectionHeader('4. Analisis Akar Masalah (5 Whys Causality Ladder)', 'Kaizen RCA', 'rca'))
                    ->schema([
                        TextEntry::make('why_1')->label('Why 1')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('why_2')->label('Why 2')->visible(fn ($record): bool => filled($record->why_2))->columnSpanFull(),
                        TextEntry::make('why_3')->label('Why 3')->visible(fn ($record): bool => filled($record->why_3))->columnSpanFull(),
                        TextEntry::make('why_4')->label('Why 4')->visible(fn ($record): bool => filled($record->why_4))->columnSpanFull(),
                        TextEntry::make('why_5')->label('Why 5')->visible(fn ($record): bool => filled($record->why_5))->columnSpanFull(),
                        TextEntry::make('kesimpulan_akar_masalah')
                            ->label('Kesimpulan Akar Masalah (Root Cause)')
                            ->weight('bold')
                            ->columnSpanFull(),
                    ]),

                // ── Section 5: Rencana Penanganan (Shared Component) ─────────
                Section::make(static::sectionHeader('5. Rencana Penanganan: Tindakan Koreksi (Sementara) vs Tindakan Korektif (Akar Masalah)', ''))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('rujukan_akar_masalah')
                            ->label('Tautan Rujukan Sasaran Tindakan Korektif')
                            ->state(fn ($record): string => $record->kesimpulan_akar_masalah ?: '(Menunggu pengisian Kesimpulan Akar Masalah pada Bagian 4)')
                            ->columnSpanFull(),
                        ViewEntry::make('rencana_penanganan')
                            ->view('components.capa.koreksi-korektif-grid')
                            ->columnSpanFull(),
                    ]),

                // ── Section 6: Identifikasi Dampak Lanjutan & Potensi ─────────
                Section::make(static::sectionHeader('6. Identifikasi Dampak Lanjutan & Potensi', 'Manajemen Risiko'))
                    ->columnSpanFull()
                    ->schema([
                        IconEntry::make('is_potensi_risiko')
                            ->label('Potensi Risiko Signifikan')
                            ->boolean()
                            ->trueColor('danger')
                            ->falseColor('gray'),
                        IconEntry::make('is_potensi_peluang')
                            ->label('Potensi Peluang Improvement')
                            ->boolean()
                            ->trueColor('success')
                            ->falseColor('gray'),
                    ])->columns(2),

                // ── Section 7: Video Penanganan & Bukti (Shared Component) ───
                Section::make(static::sectionHeader('7. Video Penanganan & Bukti', 'Dokumentasi Visual'))
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('video_penanganan')
                            ->view('components.capa.video-player')
                            ->columnSpanFull(),
                    ]),

                // ── Section 8: Verifikasi & Approval ─────────────────────────
                Section::make(static::sectionHeader('8. Verifikasi & Approval', 'Evaluasi Reviewer'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('status_verifikasi')
                            ->label('Status Verifikasi')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'efektif' => 'success',
                                'tidak_efektif' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'efektif' => 'Efektif',
                                'tidak_efektif' => 'Tidak Efektif',
                                default => 'Belum Diverifikasi',
                            })
                            ->placeholder('Belum diverifikasi'),
                        TextEntry::make('reviewer.name')
                            ->label('Ditinjau Oleh')
                            ->placeholder('-'),
                        TextEntry::make('reviewed_at')
                            ->label('Waktu Review')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('closed_at')
                            ->label('Waktu Selesai')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('bukti_objektif')
                            ->label('Bukti Objektif Efektivitas')
                            ->visible(fn ($record): bool => $record->status_verifikasi === 'efektif')
                            ->columnSpanFull(),
                        TextEntry::make('alasan_tidak_efektif')
                            ->label('Alasan Ketidakefektifan')
                            ->visible(fn ($record): bool => $record->status_verifikasi === 'tidak_efektif')
                            ->columnSpanFull(),
                        TextEntry::make('catatan_penolakan')
                            ->label('Catatan Penolakan / Revisi')
                            ->visible(fn ($record): bool => ! empty($record->catatan_penolakan))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
