<?php

namespace App\Modules\Delivery\Models;

use Database\Factories\CourierZoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CourierZone extends Pivot
{
    /** @use HasFactory<CourierZoneFactory> */
    use HasFactory;

    protected $fillable = [
        'courier_id',
        'zone_id',
    ];
}