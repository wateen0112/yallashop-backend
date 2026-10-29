<?php

namespace App\Modules\Delivery\Models;

use Database\Factories\ZoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    /** @use HasFactory<ZoneFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'polygon',
        'base_delivery_fee',
        'per_km_fee',
    ];

    protected function casts(): array
    {
        return [
            'polygon' => 'array',
            'base_delivery_fee' => 'decimal:2',
            'per_km_fee' => 'decimal:2',
        ];
    }

    public function couriers()
    {
        return $this->belongsToMany(Courier::class, 'courier_zones')
            ->using(CourierZone::class);
    }
}