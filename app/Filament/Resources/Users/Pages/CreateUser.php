<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Concerns\ShowsTemporaryPassword;
use App\Filament\Resources\Users\UserResource;
use App\Services\EmployeeAccountService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    use ShowsTemporaryPassword;

    protected static string $resource = UserResource::class;

    /**
     * Private: tidak ikut diserialisasi ke state Livewire, hanya hidup selama request pembuatan.
     */
    private ?string $issuedPassword = null;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        ['user' => $user, 'password' => $this->issuedPassword] = app(EmployeeAccountService::class)->create($data);

        return $user;
    }

    /**
     * Akun sudah dibuat; tampilkan kata sandinya sekali lewat modal. halt() meng-commit transaksi
     * dan menahan redirect/notifikasi bawaan sampai admin menekan "Selesai" di modal.
     */
    protected function afterCreate(): void
    {
        $this->mountAction('temporaryPassword', [
            'name' => $this->getRecord()->name,
            'password' => $this->issuedPassword,
            'redirect' => UserResource::getUrl('edit', ['record' => $this->getRecord()]),
        ]);

        $this->halt();
    }
}
