<?php

namespace App\Models;

use Database\Factories\BaActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ba_incident_id',
    'actor_id',
    'action',
    'note',
])]
class BaActivityLog extends Model
{
    /** @use HasFactory<BaActivityLogFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the incident associated with the activity log.
     *
     * @return BelongsTo<BaIncident, $this>
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(BaIncident::class, 'ba_incident_id');
    }

    /**
     * Get the user who performed the action.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
