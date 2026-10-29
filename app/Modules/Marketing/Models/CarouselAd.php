<?php

namespace App\Modules\Marketing\Models;

use App\Support\Enums\CarouselLinkType;
use App\Support\Enums\CarouselMediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CarouselAd extends Model
{
    /** @use HasFactory<\Database\Factories\CarouselAdFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'media_type',
        'media_url',
        'link_type',
        'link_target',
        'duration_ms',
        'sort_order',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'media_type' => CarouselMediaType::class,
            'link_type' => CarouselLinkType::class,
            'duration_ms' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function getMediaUrlAttribute(string $value): string
    {
        if (str_starts_with($value, 'http')) {
            return $value;
        }
        return config('app.url') . $value;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderBy('sort_order');
    }
}
