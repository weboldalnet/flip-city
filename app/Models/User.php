<?php

namespace Weboldalnet\FlipCity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Weboldalnet\FlipCity\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string|null $billing_details
 * @property bool $terms_accepted
 * @property string|null $qr_code_token
 * @property string|null $qr_code_svg
 * @property string|null $activation_token
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property bool $is_active
 * @property bool $is_blocked
 * @property float $balance
 * @property bool $card_registered
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Weboldalnet\FlipCity\Models\Entry[] $entries
 * @property-read \Illuminate\Database\Eloquent\Collection|\Weboldalnet\FlipCity\Models\Booking[] $bookings
 * @property-read \Illuminate\Database\Eloquent\Collection|\Weboldalnet\FlipCity\Models\Invoice[] $invoices
 */
class User extends Authenticatable
{
    use \Illuminate\Notifications\Notifiable;

    protected $table = 'flip_city_users';

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'billing_details',
        'terms_accepted', 'qr_code_token', 'qr_code_svg', 'activation_token', 'activated_at',
        'is_active', 'is_blocked', 'balance', 'card_registered'
    ];

    protected $casts = [
        'terms_accepted' => 'boolean',
        'is_active' => 'boolean',
        'is_blocked' => 'boolean',
        'card_registered' => 'boolean',
        'balance' => 'float',
        'activated_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'user_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'user_id');
    }
}
