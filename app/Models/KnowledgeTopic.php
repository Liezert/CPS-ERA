<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'created_by',
])]
class KnowledgeTopic extends Model
{
    use HasFactory;

    /**
     * Get the user who created this topic.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the knowledge documents associated with this topic.
     *
     * @return HasMany<KnowledgeDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class, 'topic_id');
    }
}
