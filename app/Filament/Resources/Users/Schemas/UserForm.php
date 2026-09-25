<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Division;
use App\Models\User;
use App\Services\EmployeeAccountService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Karyawan')
                    ->description('Akun baru mendapat kata sandi sementara acak (ditampilkan sekali setelah disimpan) dan wajib diganti karyawan saat login pertama.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true),
                        TextInput::make('employee_id')
                            ->label('NIK')
                            ->required()
                            ->maxLength(20)
                            ->unique(User::class, 'employee_id', ignoreRecord: true),
                        Select::make('division_id')
                            ->label('Divisi')
                            // Termasuk HRGA: identitas karyawan boleh berdivisi HRGA, yang dibatasi
                            // hanya form pelaporan CAPA.
                            ->options(fn (): array => Division::orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('jabatan')
                            ->label('Posisi / Jabatan')
                            ->maxLength(100),
                        Select::make('role')
                            ->label('Role')
                            ->options(EmployeeAccountService::ROLES)
                            ->default('employee')
                            ->required(),
                    ]),
            ]);
    }
}
