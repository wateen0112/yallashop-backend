<?php

namespace App\Modules\Orders\Models;

use App\Modules\Vendors\Models\Store;
use Database\Factories\SubOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubOrder extends Model
{
    /** @use HasFactory<SubOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'store_id',
        'status',
        'delivery_fee',
        'courier_id',
        'pickup_code',
        'delivery_code',
        'delivery_address_snapshot',
        'picked_up_at',
        'delivered_at',
    ];

    protected $appends = ['customer_name', 'customer_phone', 'customer_image', 'items_count', 'total_amount', 'currency_snapshot'];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
            'delivery_address_snapshot' => 'array',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function getCustomerNameAttribute(): ?string
    {
        return $this->order?->customer?->name;
    }

    public function getCustomerPhoneAttribute(): ?string
    {
        return $this->order?->customer?->phone;
    }

    public function getCustomerImageAttribute(): ?string
    {
        return $this->order?->customer?->avatar;
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items->count();
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum('total_price');
    }

    public function getCurrencySnapshotAttribute(): ?array
    {
        if (!isset($this->items) || $this->items->isEmpty()) {
            return null;
        }

        $product = $this->items->first()?->variant?->product;
        $currency = $product?->currency;

        if (!$currency) {
            $currency = \App\Modules\Catalog\Models\Currency::query()->default()->first();
        }

        if (!$currency) {
            return null;
        }

        return [
            'code' => $currency->code,
            'symbol' => $currency->symbol,
            'name' => $currency->name,
        ];
    }

    public static function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function courier()
    {
        return $this->belongsTo(\App\Modules\Delivery\Models\Courier::class);
    }
}
