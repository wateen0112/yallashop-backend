<?php

namespace App\Modules\Delivery\Repositories;

use App\Modules\Delivery\Models\Courier;
use App\Modules\Orders\Models\SubOrder;
use App\Modules\Payments\Models\Payout;
use App\Support\Enums\OrderStatus;

class EarningsRepository
{
    public function getByCourier(int $userId): array
    {
        $courier = Courier::where('user_id', $userId)->first();
        if (!$courier) {
            return [
                'balance' => 0,
                'daily' => [],
                'weekly' => [],
                'monthly' => [],
                'orders' => [],
            ];
        }

        $deliveredOrders = SubOrder::where('courier_id', $courier->id)
            ->where('status', OrderStatus::Delivered)
            ->orderBy('delivered_at', 'desc')
            ->get(['id', 'delivery_fee', 'delivered_at']);

        $totalEarnings = $deliveredOrders->sum('delivery_fee');

        $daily = $deliveredOrders->groupBy(fn($o) => $o->delivered_at->format('Y-m-d'))
            ->map(fn($items) => [
                'date' => $items->first()->delivered_at->format('Y-m-d'),
                'earnings' => round($items->sum('delivery_fee'), 2),
                'count' => $items->count(),
            ])->values()->toArray();

        $weekly = $deliveredOrders->groupBy(fn($o) => $o->delivered_at->format('o-W'))
            ->map(fn($items) => [
                'week' => $items->first()->delivered_at->format('Y-W'),
                'earnings' => round($items->sum('delivery_fee'), 2),
                'count' => $items->count(),
            ])->values()->toArray();

        $monthly = $deliveredOrders->groupBy(fn($o) => $o->delivered_at->format('Y-m'))
            ->map(fn($items) => [
                'month' => $items->first()->delivered_at->format('Y-m'),
                'earnings' => round($items->sum('delivery_fee'), 2),
                'count' => $items->count(),
            ])->values()->toArray();

        $orders = $deliveredOrders->map(function ($o) {
            return [
                'id' => $o->id,
                'delivery_fee' => $o->delivery_fee,
                'delivered_at' => $o->delivered_at->toDateTimeString(),
            ];
        })->toArray();

        return [
            'balance' => round((float) $courier->wallet_balance, 2),
            'total_earnings' => round($totalEarnings, 2),
            'daily' => $daily,
            'weekly' => $weekly,
            'monthly' => $monthly,
            'orders' => $orders,
        ];
    }

    public function getTotalEarnings(int $courierId): float
    {
        return (float) SubOrder::where('courier_id', $courierId)
            ->where('status', OrderStatus::Delivered)
            ->sum('delivery_fee');
    }

    public function getCompletedDeliveries(int $courierId): int
    {
        return SubOrder::where('courier_id', $courierId)
            ->where('status', OrderStatus::Delivered)
            ->count();
    }

    public function requestPayout(int $userId, float $amount): Payout
    {
        $courier = Courier::where('user_id', $userId)->firstOrFail();

        return Payout::create([
            'payee_type' => get_class($courier),
            'payee_id' => $courier->id,
            'amount' => $amount,
            'status' => 'pending',
        ]);
    }
}
