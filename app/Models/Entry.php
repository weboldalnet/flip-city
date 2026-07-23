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
 * @property int $companions_count
 * @property bool $is_auto_closed
 * @property bool $is_failed
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
        'guest_count', 'companions_count', 'is_auto_closed', 'is_failed', 'total_cost'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_auto_closed' => 'boolean',
        'is_failed' => 'boolean',
        'rate' => 'float',
        'total_cost' => 'float',
        'guest_count' => 'integer',
        'companions_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'entry_id');
    }

    public function calculateCurrentCost(): float
    {
        $endTime = $this->end_time ?: now();
        $diffInSeconds = $this->start_time->diffInSeconds($endTime);
        $durationMinutes = ceil($diffInSeconds / 60);
        if ($durationMinutes < 1) $durationMinutes = 1;

        $companionPrice = (float) FlipCitySettings::get('companion_price', config('flip-city.companion_price', 500));
        $companionsCost = ($this->companions_count ?? 0) * $companionPrice;

        return round(($durationMinutes / 60) * $this->rate * $this->guest_count) + $companionsCost;
    }
}
