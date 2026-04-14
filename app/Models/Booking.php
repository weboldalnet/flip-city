<?php

namespace Weboldalnet\FlipCity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Weboldalnet\FlipCity\Models\Booking
 *
 * @property int $id
 * @property int $user_id
 * @property string $booking_date
 * @property string $booking_time
 * @property int $guest_count
 * @property string|null $qr_code_token
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Weboldalnet\FlipCity\Models\User $user
 */
class Booking extends Model
{
    protected $table = 'flip_city_bookings';

    protected $fillable = [
        'user_id', 'booking_date', 'booking_time',
        'guest_count', 'qr_code_token', 'status'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
