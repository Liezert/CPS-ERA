<?php

namespace App\Filament\Resources\Users\Concerns;

use Filament\Actions\Action;
use Illuminate\Support\HtmlString;

/**
 * Modal satu kali untuk menampilkan kata sandi sementara ke admin (setelah buat akun / reset).
 *
 * Sengaja modal, bukan Notification: notifikasi Filament disimpan di session (tabel sessions),
 * sedangkan argumen modal hanya hidup di state komponen Livewire di browser admin.
 * Argumen: name, password, dan redirect (opsional, URL tujuan setelah admin menekan Selesai).
 */
trait ShowsTemporaryPassword
{
    public function temporaryPasswordAction(): Action
    {
        return Action::make('temporaryPassword')
            ->modalHeading('Kata Sandi Sementara')
            ->modalDescription(fn (array $arguments): string => 'Serahkan kata sandi ini langsung kepada '.($arguments['name'] ?? 'karyawan')
                .'. Kata sandi hanya ditampilkan sekali dan wajib diganti saat login pertama.')
            ->modalContent(fn (array $arguments): HtmlString => new HtmlString(
                '<p style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.25rem; font-weight: 600; letter-spacing: 0.05em; text-align: center; user-select: all;">'
                .e($arguments['password'] ?? '')
                .'</p>'
            ))
            ->modalSubmitActionLabel('Selesai, sudah dicatat')
            ->modalCancelAction(false)
            ->modalCloseButton(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->action(function (array $arguments): void {
                if (filled($arguments['redirect'] ?? null)) {
                    $this->redirect($arguments['redirect']);
                }
            });
    }
}
