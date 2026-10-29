<?php

namespace App\Modules\Delivery\Jobs;

use App\Modules\Delivery\Models\Courier;
use App\Modules\Delivery\Repositories\CourierRepository;
use App\Modules\Orders\Models\SubOrder;
use App\Modules\Vendors\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MatchOrderToCourier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $subOrderId
    ) {}

    public function handle(): void
    {
        $subOrder = SubOrder::with('store')->find($this->subOrderId);
        if (!$subOrder) {
            Log::warning("Sub-order {$this->subOrderId} not found for courier matching");
            return;
        }

        $store = $subOrder->store;
        if (!$store) {
            Log::warning("Store not found for sub-order {$this->subOrderId}");
            return;
        }

        $courierRepo = app(CourierRepository::class);
        $zoneId = 1;
        $couriers = $courierRepo->getAvailableInZone($zoneId);

        if ($couriers->isEmpty()) {
            Log::info("No available couriers found for sub-order {$this->subOrderId}");
            return;
        }

        $nearestCourier = $couriers->sortBy(function ($courier) use ($store) {
            if (!$courier->current_lat || !$courier->current_lng || !$store->city) {
                return PHP_FLOAT_MAX;
            }
            return 0;
        })->first();

        if ($nearestCourier) {
            $subOrder->update([
                'courier_id' => $nearestCourier->id,
                'status' => 'courier_assigned',
            ]);

            Log::info("Courier {$nearestCourier->id} matched to sub-order {$this->subOrderId}");
        }
    }
}
