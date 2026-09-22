<?php

namespace App\Filament\Resources\Users\Actions;

use App\Models\User;
use App\Services\EmployeeAccountService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Kembalikan akun karyawan ke kata sandi sementara (mis. karyawan lupa kata sandinya).
 * Dipakai di halaman Edit User dan di baris tabel daftar karyawan.
 */
class ResetPasswordAction
{
    public static function make(): Action
    {
        return Action::make('resetPassword')
            ->label('Reset Kata Sandi')
            ->icon(Heroicon::OutlinedKey)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Reset Kata Sandi Karyawan')
            ->modalDescription(fn (User $record): string => "Kata sandi {$record->name} dikembalikan ke kata sandi sementara "
                .EmployeeAccountService::TEMPORARY_PASSWORD.', dan karyawan wajib membuat kata sandi baru saat login berikutnya.')
            ->modalSubmitActionLabel('Reset Sekarang')
            ->action(function (User $record): void {
                app(EmployeeAccountService::class)->resetTemporaryPassword($record);

                Notification::make()
                    ->title("Kata sandi {$record->name} direset")
                    ->body('Kata sandi sementara: '.EmployeeAccountService::TEMPORARY_PASSWORD)
                    ->success()
                    ->send();
            });
    }
}
