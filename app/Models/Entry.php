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
 * @property int $children_count
 * @property float|null $child_rate
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
        'guest_count', 'companions_count', 'is_auto_closed', 'is_failed', 'total_cost',
        'children_count', 'child_rate'
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
        'children_count' => 'integer',
        'child_rate' => 'float',
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
        return $this->costBreakdown()['total'];
    }

    public function adultsCount(): int
    {
        return max(0, (int) $this->guest_count - (int) $this->children_count);
    }

    /**
     * Díjszámítás egy helyen: felnőtt és gyerek percdíj a minimum perccel, a kísérő egyszeri fix díja.
     * $adults / $children: részleges távozásnál csak a távozók; $withCompanions: a kísérő díja ekkor nem jár.
     */
    public function costBreakdown($endTime = null, ?int $adults = null, ?int $children = null, bool $withCompanions = true): array
    {
        $endTime = $endTime ?: ($this->end_time ?: now());
        $minutes = (int) ceil($this->start_time->diffInSeconds($endTime) / 60);
        if ($minutes < 1) {
            $minutes = 1;
        }

        $minMinutes = (int) FlipCitySettings::get('min_minutes', config('flip-city.min_minutes', 0));
        $billedMinutes = max($minutes, $minMinutes);

        $adults = $adults ?? $this->adultsCount();
        $children = $children ?? (int) $this->children_count;
        $adultRate = (float) $this->rate;
        $childRate = $this->child_rate !== null ? (float) $this->child_rate : $adultRate;

        $adultCost = round(($billedMinutes / 60) * $adultRate * $adults);
        $childCost = round(($billedMinutes / 60) * $childRate * $children);

        $companions = $withCompanions ? (int) $this->companions_count : 0;
        $companionPrice = (float) FlipCitySettings::get('companion_price', config('flip-city.companion_price', 500));
        $companionsCost = $companions * $companionPrice;

        return [
            'minutes' => $minutes,
            'billed_minutes' => $billedMinutes,
            'min_minutes' => $minMinutes,
            'min_applied' => $billedMinutes > $minutes,
            'adults' => $adults,
            'children' => $children,
            'adult_rate' => $adultRate,
            'child_rate' => $childRate,
            'adult_cost' => $adultCost,
            'child_cost' => $childCost,
            'companions' => $companions,
            'companion_price' => $companionPrice,
            'companions_cost' => $companionsCost,
            'total' => $adultCost + $childCost + $companionsCost,
        ];
    }
}
