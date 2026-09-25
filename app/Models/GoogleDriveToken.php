<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

#[Fillable([
    'refresh_token',
    'connected_email',
    'connected_by',
    'connected_at',
])]
class GoogleDriveToken extends Model
{
    use HasUuids;

    /**
     * Koneksi Drive aktif; aplikasi hanya memakai satu akun bersama.
     */
    public static function active(): ?self
    {
        return static::query()->latest('connected_at')->first();
    }

    /**
     * Refresh token dalam bentuk terbaca (didekripsi saat diambil).
     */
    public function plainRefreshToken(): string
    {
        return Crypt::decryptString($this->refresh_token);
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
        ];
    }
}
