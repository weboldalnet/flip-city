<?php

namespace Weboldalnet\FlipCity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Weboldalnet\FlipCity\Models\Entry
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon|null $end_time
 * @property float $rate
 * @property int $guest_count
 * @property bool $is_auto_closed
 * @property float|null $total_cost
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Weboldalnet\FlipCity\Models\User $user
 * @property-read \Weboldalnet\FlipCity\Models\Invoice|null $invoice
 */
class Entry extends Model
{
    protected $table = 'flip_city_entries';

    protected $fillable = [
        'user_id', 'start_time', 'end_time', 'rate',
        'guest_count', 'is_auto_closed', 'total_cost'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_auto_closed' => 'boolean',
        'rate' => 'float',
        'total_cost' => 'float',
        'guest_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'entry_id');
    }
}
