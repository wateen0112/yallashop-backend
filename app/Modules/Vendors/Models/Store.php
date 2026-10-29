<?php

namespace App\Modules\Vendors\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Review;
use App\Modules\Users\Models\User;
use App\Support\Enums\StoreStatus;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'logo',
        'banner',
        'cover_photo',
        'description',
        'status',
        'commission_rate',
        'rating_avg',
        'governorate',
        'city',
        'area',
    ];

    protected function casts(): array
    {
        return [
            'status' => StoreStatus::class,
            'commission_rate' => 'decimal:2',
            'rating_avg' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function staff()
    {
        return $this->hasMany(StoreStaff::class);
    }

    public function workingHours()
    {
        return $this->hasMany(\App\Modules\Users\Models\StoreWorkingHour::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
}