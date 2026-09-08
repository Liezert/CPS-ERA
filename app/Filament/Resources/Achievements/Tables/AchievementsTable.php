<?php

namespace App\Filament\Resources\Achievements\Tables;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AchievementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Badge')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('icon')
                    ->label('Icon')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('users_count')
                    ->label('Jumlah Penerima')
                    ->counts('users')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('unlockForUser')
                    ->label('Unlock untuk User')
                    ->icon('heroicon-o-gift')
                    ->color('success')
                    ->modalHeading(fn (Achievement $record): string => "Unlock Badge '{$record->name}' untuk User")
                    ->modalDescription('Pilih user yang akan diberikan badge pencapaian ini. User akan otomatis menerima notifikasi achievement baru.')
                    ->schema([
                        Select::make('user_id')
                            ->label('Pilih Karyawan / User')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Achievement $record, array $data): void {
                        $userId = $data['user_id'];

                        $exists = UserAchievement::where('user_id', $userId)
                            ->where('achievement_id', $record->id)
                            ->exists();

                        if ($exists) {
                            FilamentNotification::make()
                                ->title('User sudah memiliki achievement ini!')
                                ->warning()
                                ->send();

                            return;
                        }

                        // Menyimpan user_achievement memicu UserAchievementObserver
                        // yang otomatis mengirimkan notifikasi 'achievement_baru' ke user.
                        UserAchievement::create([
                            'user_id' => $userId,
                            'achievement_id' => $record->id,
                            'unlocked_at' => now(),
                        ]);

                        FilamentNotification::make()
                            ->title("Achievement '{$record->name}' berhasil dibuka untuk user!")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
            ]);
    }
}
