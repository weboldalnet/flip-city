<?php

namespace Weboldalnet\FlipCity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Weboldalnet\FlipCity\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string|null $billing_zip
 * @property string|null $billing_city
 * @property string|null $billing_address
 * @property string|null $billing_details
 * @property bool $terms_accepted
 * @property string|null $qr_code_token
 * @property string|null $qr_code_svg
 * @property string|null $activation_token
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property bool $is_active
 * @property bool $is_blocked
 * @property string|null $blocked_reason
 * @property \Illuminate\Support\Carbon|null $blocked_at
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
        'name', 'email', 'phone', 'password', 'billing_zip', 'billing_city', 'billing_address', 'billing_details',
        'terms_accepted', 'qr_code_token', 'qr_code_svg', 'activation_token', 'activated_at',
        'is_active', 'is_blocked', 'blocked_reason', 'blocked_at', 'balance', 'card_registered'
    ];

    protected $casts = [
        'terms_accepted' => 'boolean',
        'is_active' => 'boolean',
        'is_blocked' => 'boolean',
        'card_registered' => 'boolean',
        'balance' => 'float',
        'activated_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'user_id');
    }

    public function lastEntry(): HasOne
    {
        return $this->hasOne(Entry::class, 'user_id')->latestOfMany('start_time');
    }

    public function unpaidAutoClosedEntries(): HasMany
    {
        return $this->hasMany(Entry::class, 'user_id')->where('is_auto_closed', true)->where('is_failed', true);
    }

    public function scopeWithCheckinNotice($query)
    {
        return $query->with('lastEntry')->withSum('unpaidAutoClosedEntries as unpaid_auto_closed_total', 'total_cost');
    }

    public function checkinNotice(): ?string
    {
        return \Weboldalnet\FlipCity\Support\AutoClose::checkinNotice($this);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'user_id');
    }

    public function ensureQrCode(): bool
    {
        if ($this->qr_code_token && $this->qr_code_svg) {
            return false;
        }

        if (!$this->qr_code_token) {
            $this->qr_code_token = \Illuminate\Support\Str::uuid()->toString();
        }

        $this->qr_code_svg = \Weboldalnet\FlipCity\Services\QRCodeService::generateQRCode($this->qr_code_token);

        return true;
    }
}
