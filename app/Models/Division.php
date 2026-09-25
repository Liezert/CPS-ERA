<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Division extends Model
{
    use HasFactory;

    /**
     * HRGA tetap disimpan sebagai divisi untuk profil user HRGA, tetapi HRGA adalah
     * role approval final, bukan divisi pelapor CAPA.
     */
    public const HRGA = 'HRGA';

    /**
     * Scope query to divisions that can file a CAPA report.
     */
    public function scopeReportable(Builder $query): Builder
    {
        return $query->where('name', '!=', self::HRGA);
    }

    /**
     * Get the users associated with the division.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
