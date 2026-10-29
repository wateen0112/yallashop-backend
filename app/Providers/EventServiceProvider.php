<?php

namespace App\Providers;

use App\Modules\Delivery\Jobs\MatchOrderToCourier;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Events\OrderStatusUpdated;
use App\Modules\Orders\Listeners\NotifyVendorOnOrder;
use App\Modules\Orders\Listeners\ReserveStock;
use App\Modules\Orders\Listeners\SendOrderConfirmation;
use App\Modules\Orders\Listeners\SendOrderStatusNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            SendOrderConfirmation::class,
            NotifyVendorOnOrder::class,
            ReserveStock::class,
        ],
        OrderStatusUpdated::class => [
            SendOrderStatusNotification::class,
        ],
    ];

    protected $observers = [];

    public function boot(): void
    {
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}