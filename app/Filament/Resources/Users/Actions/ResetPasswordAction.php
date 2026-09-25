<?php

namespace App\Filament\Resources\Users\Actions;

use App\Models\User;
use App\Services\EmployeeAccountService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

/**
 * Ganti kata sandi karyawan dengan kata sandi sementara acak (mis. karyawan lupa kata sandinya).
 * Dipakai di halaman Edit User dan di baris tabel daftar karyawan; halaman pemakainya wajib
 * memakai trait ShowsTemporaryPassword untuk modal hasilnya.
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
            ->modalDescription(fn (User $record): string => "Kata sandi {$record->name} diganti dengan kata sandi sementara acak yang baru. "
                .'Kata sandi ditampilkan sekali setelah reset, dan karyawan wajib membuat kata sandi baru saat login berikutnya.')
            ->modalSubmitActionLabel('Reset Sekarang')
            ->action(function (User $record, Component $livewire): void {
                $password = app(EmployeeAccountService::class)->resetTemporaryPassword($record);

                // Modal, bukan Notification: isi notifikasi Filament tersimpan di session (DB).
                $livewire->replaceMountedAction('temporaryPassword', [
                    'name' => $record->name,
                    'password' => $password,
                ]);
            });
    }
}
