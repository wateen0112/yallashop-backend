<?php

namespace App\Modules\Vendors\Repositories;

use App\Modules\Vendors\Models\Store;
use App\Support\Enums\StoreStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StoreRepository
{
    public function create(array $data): Store
    {
        return Store::create([
            'owner_user_id' => $data['owner_user_id'],
            'name' => $data['name'],
            'slug' => $data['slug'] ?? str()->slug($data['name']),
            'logo' => $data['logo'] ?? null,
            'banner' => $data['banner'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => StoreStatus::Pending,
            'commission_rate' => $data['commission_rate'] ?? 0,
            'governorate' => $data['governorate'] ?? null,
            'city' => $data['city'] ?? null,
            'area' => $data['area'] ?? null,
        ]);
    }

    public function getByOwner(int $userId): ?Store
    {
        return Store::where('owner_user_id', $userId)->first();
    }

    public function getAll(): LengthAwarePaginator
    {
        return Store::with('owner')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function approve(int $storeId): ?Store
    {
        $store = Store::find($storeId);
        if (!$store) {
            return null;
        }

        $store->update(['status' => StoreStatus::Approved]);
        return $store->fresh();
    }

    public function suspend(int $storeId): ?Store
    {
        $store = Store::find($storeId);
        if (!$store) {
            return null;
        }

        $store->update(['status' => StoreStatus::Suspended]);
        return $store->fresh();
    }

    public function update(int $storeId, array $data): ?Store
    {
        $store = Store::find($storeId);
        if (!$store) {
            return null;
        }

        $store->update([
            'name' => $data['name'] ?? $store->name,
            'slug' => $data['slug'] ?? $store->slug,
            'logo' => $data['logo'] ?? $store->logo,
            'banner' => $data['banner'] ?? $store->banner,
            'description' => $data['description'] ?? $store->description,
            'commission_rate' => $data['commission_rate'] ?? $store->commission_rate,
            'governorate' => $data['governorate'] ?? $store->governorate,
            'city' => $data['city'] ?? $store->city,
            'area' => $data['area'] ?? $store->area,
        ]);

        return $store->fresh();
    }

    public function findWithOwner(int $storeId): ?Store
    {
        return Store::with('owner')->find($storeId);
    }

    public function delete(int $storeId): bool
    {
        $store = Store::find($storeId);
        if (!$store) return false;

        return $store->delete();
    }
}
