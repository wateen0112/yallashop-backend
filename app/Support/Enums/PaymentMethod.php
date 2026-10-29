<?php

namespace App\Support\Enums;

enum PaymentMethod: string
{
    case CashOnDelivery = 'cash_on_delivery';
    case CreditCard = 'credit_card';
    case Wallet = 'wallet';
}