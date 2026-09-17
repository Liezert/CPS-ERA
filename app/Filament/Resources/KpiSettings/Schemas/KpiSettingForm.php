<?php

namespace App\Filament\Resources\KpiSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KpiSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('target_video_count')
                    ->label('Target Minimal Video Selesai')
                    ->numeric()
                    ->minValue(1)
                    ->default(10)
                    ->required()
                    ->helperText('Target minimal video yang kuis post-test-nya harus lulus per periode.'),

                Select::make('period_type')
                    ->label('Jenis Periode')
                    ->options([
                        'monthly' => 'Bulanan (Monthly)',
                        'quarterly' => 'Kuartalan (Quarterly)',
                        'all_time' => 'Sepanjang Waktu (All Time)',
                    ])
                    ->default('monthly')
                    ->required()
                    ->helperText('Rentang periode perhitungan target KPI video'),

                TextInput::make('points_reward')
                    ->label('Poin Bonus Saat KPI 100%')
                    ->numeric()
                    ->minValue(0)
                    ->default(50)
                    ->helperText('Poin bonus tambahan bagi karyawan yang berhasil memenuhi 100% target'),
            ]);
    }
}
