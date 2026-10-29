<?php

namespace App\Support\Enums;

enum CarouselLinkType: string
{
    case External = 'external';
    case Product = 'product';
    case Category = 'category';
}
