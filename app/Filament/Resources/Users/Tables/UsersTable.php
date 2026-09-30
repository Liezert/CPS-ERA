<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\Actions\ResetPasswordAction;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\EmployeeAccountService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;
use Illuminate\Support\Js;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('employee_id')
                    ->label('ID Pegawai')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => EmployeeAccountService::ROLES[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => $state === 'admin' ? 'danger' : ($state === 'employee' ? 'gray' : 'info')),
                TextColumn::make('must_change_password')
                    ->label('Kata Sandi')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Sementara' : 'Pribadi')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                TextColumn::make('xp')
                    ->label('XP Saat Ini')
                    ->numeric()
                    ->badge()
                    ->color('primary')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('division_id')
                    ->label('Divisi')
                    ->relationship('division', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                // Aksi jarang & sensitif dilipat ke menu "⋮" supaya tabel ratusan baris tidak penuh link berwarna.
                ActionGroup::make([
                    static::deleteAction(),
                    ResetPasswordAction::make()->color('danger'),
                    Action::make('adjustXp')
                        ->label('Koreksi XP')
                        ->icon(Heroicon::OutlinedAdjustmentsVertical)
                        ->color('warning')
                        ->visible(fn (): bool => auth()->user()?->can('adjust-xp-manual') ?? false)
                        ->modalHeading('Koreksi XP Manual (Buku Besar)')
                        ->modalDescription('Koreksi dicatat di riwayat XP karyawan dan langsung mengubah total XP-nya.')
                        ->form([
                            TextInput::make('points')
                                ->label('Nilai Koreksi XP (+/-)')
                                ->numeric()
                                ->required()
                                ->helperText('Gunakan angka positif untuk penambahan (contoh: 100), atau angka negatif untuk pengurangan (contoh: -50).'),
                            Textarea::make('description')
                                ->label('Alasan / Catatan Koreksi')
                                ->required()
                                ->rows(3)
                                ->placeholder('Contoh: Koreksi kelebihan klaim poin misi...'),
                        ])
                        ->action(function (User $record, array $data): void {
                            $points = (int) $data['points'];

                            // PRD v2.0 §2.2 & prompt: WAJIB selalu insert ke point_transactions,
                            // JANGAN pernah mengedit kolom users.xp secara langsung!
                            PointTransaction::create([
                                'user_id' => $record->id,
                                'ledger_type' => PointTransaction::LEDGER_XP,
                                'points' => $points,
                                'source_type' => 'admin_adjustment',
                                'description' => trim((string) $data['description']),
                                'created_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Koreksi XP Berhasil Dicatat')
                                ->body("Transaksi koreksi {$points} XP untuk {$record->name} berhasil dicatat ke buku besar.")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    /**
     * Hapus akun dengan konfirmasi ketik-ulang nama (seperti menghapus repository): tombol hapus baru
     * aktif setelah nama diketik persis, dan server memeriksanya lagi. Akun yang tercatat sebagai
     * pembuat laporan/materi/riwayat tidak bisa dihapus (foreign key) agar jejak audit tetap utuh.
     */
    private static function deleteAction(): Action
    {
        return Action::make('deleteUser')
            ->label('Hapus Akun')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->visible(fn (User $record): bool => (auth()->user()?->hasRole('admin') ?? false) && $record->isNot(auth()->user()))
            ->modalHeading(fn (User $record): string => "Hapus akun {$record->name}?")
            ->modalDescription('Akun, XP, poin, progres belajar, dan notifikasinya dihapus permanen dan tidak bisa dikembalikan.')
            ->schema(fn (User $record): array => [
                TextInput::make('confirm_name')
                    ->label("Ketik \"{$record->name}\" untuk konfirmasi")
                    ->autocomplete(false)
                    ->required()
                    ->in([$record->name])
                    ->validationMessages(['in' => 'Nama yang diketik tidak sama dengan nama akun.']),
            ])
            ->modalSubmitAction(fn (Action $action, User $record): Action => $action
                ->label('Hapus Akun Permanen')
                ->color('danger')
                ->extraAttributes([
                    // Tombol terkunci sampai nama diketik persis (state form aksi ada di mountedActions.*.data).
                    'x-bind:disabled' => "(\$wire.mountedActions?.at(-1)?.data?.confirm_name ?? '') !== ".Js::from($record->name),
                ]))
            ->action(function (User $record): void {
                try {
                    $record->delete();
                } catch (QueryException) {
                    Notification::make()
                        ->title('Akun tidak bisa dihapus')
                        ->body("{$record->name} masih tercatat sebagai pembuat laporan CAPA, materi, video, atau riwayat review. Data itu dipertahankan untuk audit.")
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()->title("Akun {$record->name} dihapus")->success()->send();
            });
    }
}
