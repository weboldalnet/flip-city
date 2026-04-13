<?php

namespace Weboldalnet\FlipCity\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Weboldalnet\FlipCity\Models\DailySummary
 *
 * @property int $id
 * @property string $summary_date
 * @property float $total_cash
 * @property float $total_card
 * @property float $total_auto
 * @property bool $is_closed
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DailySummary extends Model
{
    protected $table = 'flip_city_daily_summaries';

    protected $fillable = [
        'summary_date', 'total_cash', 'total_card',
        'total_auto', 'is_closed', 'closed_at'
    ];

    protected $casts = [
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
        'total_cash' => 'float',
        'total_card' => 'float',
        'total_auto' => 'float',
    ];
}
