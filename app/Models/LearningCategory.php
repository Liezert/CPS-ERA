<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'created_by',
])]
class LearningCategory extends Model
{
    use HasFactory;

    /**
     * Get the user that created the category.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the materials belonging to this category.
     *
     * @return HasMany<LearningMaterial, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class);
    }
}
