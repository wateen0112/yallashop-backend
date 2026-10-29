<?php

namespace App\Modules\Delivery\Repositories;

use App\Modules\Delivery\Models\Courier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CourierRepository
{
    public function create(int $userId, array $data): Courier
    {
        $courier = Courier::create([
            'user_id' => $userId,
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'status' => $data['status'] ?? 'offline',
            'current_lat' => $data['current_lat'] ?? null,
            'current_lng' => $data['current_lng'] ?? null,
            'wallet_balance' => 0,
        ]);

        if (!empty($data['zone_ids'])) {
            $courier->zones()->sync($data['zone_ids']);
        }

        return $courier->load('zones');
    }

    public function findByUser(int $userId): ?Courier
    {
        return Courier::with('zones')->where('user_id', $userId)->first();
    }

    public function updateStatus(int $userId, string $status): void
    {
        Courier::where('user_id', $userId)->update(['status' => $status]);
    }

    public function getAll(): LengthAwarePaginator
    {
        return Courier::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function getAvailableInZone(int $zoneId): Collection
    {
        return Courier::where('status', 'online')
            ->whereHas('zones', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            })
            ->with('user')
            ->get();
    }
}
