<?php

namespace App\Filament\Resources\KpiSettings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KpiSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('period_start')
                    ->label('Tanggal Mulai Periode')
                    ->required(),

                DatePicker::make('period_end')
                    ->label('Tanggal Akhir Periode')
                    ->required()
                    ->afterOrEqual('period_start')
                    ->helperText('Inklusif sampai pukul 23:59:59 WIB. Progres KPI dihitung ulang dari 0 di setiap periode.'),

                TextInput::make('target_materials')
                    ->label('Target Materi Lulus 100%')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(5)
                    ->required()
                    ->helperText('Jumlah materi Learning berbeda yang post-test-nya harus lulus dengan skor 100% di periode ini.'),
            ]);
    }
}
