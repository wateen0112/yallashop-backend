<?php

namespace App\Support\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
