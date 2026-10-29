<?php

namespace App\Support\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Courier = 'courier';
    case Admin = 'admin';
}