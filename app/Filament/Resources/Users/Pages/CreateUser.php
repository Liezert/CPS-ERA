<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\EmployeeAccountService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(EmployeeAccountService::class)->create($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Akun karyawan dibuat. Kata sandi sementara: '.EmployeeAccountService::TEMPORARY_PASSWORD;
    }
}
