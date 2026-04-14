<?php

namespace Weboldalnet\FlipCity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Weboldalnet\FlipCity\Models\Invoice
 *
 * @property int $id
 * @property int|null $entry_id
 * @property int|null $user_id
 * @property float $amount
 * @property string $payment_method
 * @property float|null $cash_received
 * @property float|null $change_given
 * @property string|null $invoice_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Weboldalnet\FlipCity\Models\Entry|null $entry
 * @property-read \Weboldalnet\FlipCity\Models\User|null $user
 */
class Invoice extends Model
{
    protected $table = 'flip_city_invoices';

    protected $fillable = [
        'entry_id', 'user_id', 'amount', 'payment_method',
        'cash_received', 'change_given', 'invoice_number'
    ];

    protected $casts = [
        'amount' => 'float',
        'cash_received' => 'float',
        'change_given' => 'float',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
