<?php

namespace App\Modules\Vendors\Models;

use Database\Factories\StoreStaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreStaff extends Model
{
    /** @use HasFactory<StoreStaffFactory> */
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'role',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Users\Models\User::class);
    }
}