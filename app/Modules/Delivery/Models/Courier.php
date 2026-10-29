<?php

namespace App\Modules\Delivery\Models;

use App\Modules\Users\Models\User;
use Database\Factories\CourierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Courier extends Model
{
    /** @use HasFactory<CourierFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'status',
        'current_lat',
        'current_lng',
        'rating_avg',
        'wallet_balance',
    ];

    protected function casts(): array
    {
        return [
            'current_lat' => 'decimal:7',
            'current_lng' => 'decimal:7',
            'rating_avg' => 'decimal:2',
            'wallet_balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zones()
    {
        return $this->belongsToMany(Zone::class, 'courier_zones')
            ->using(CourierZone::class);
    }
}