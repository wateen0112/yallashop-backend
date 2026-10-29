<?php

namespace App\Modules\Orders\Models;

use App\Modules\Users\Models\User;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'delivery_fee_total',
        'discount_total',
        'tax_total',
        'grand_total',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function subOrders(): HasMany
    {
        return $this->hasMany(SubOrder::class);
    }

    public function payment()
    {
        return $this->hasOne(\App\Modules\Payments\Models\Payment::class);
    }

    public function getCurrencySnapshotAttribute(): ?array
    {
        $subOrder = $this->subOrders->first();
        return $subOrder?->currency_snapshot;
    }
}