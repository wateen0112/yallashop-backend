<?php

namespace App\Modules\Users\Repositories;

use App\Modules\Users\Models\Address;
use Illuminate\Database\Eloquent\Collection;

class AddressRepository
{
    public function getByUser(int $userId): Collection
    {
        return Address::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(int $userId, array $data): Address
    {
        $hasExisting = Address::where('user_id', $userId)->exists();

        $address = Address::create([
            'user_id' => $userId,
            'label' => $data['label'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'governorate' => $data['governorate'] ?? null,
            'city' => $data['city'] ?? null,
            'area' => $data['area'] ?? null,
            'full_address' => $data['full_address'],
            'is_default' => $data['is_default'] ?? !$hasExisting,
        ]);

        if (!empty($data['is_default'])) {
            $this->setDefault($userId, $address->id);
        }

        return $address->fresh();
    }

    public function update(int $id, array $data): ?Address
    {
        $address = Address::find($id);
        if (!$address) {
            return null;
        }

        $address->update([
            'label' => $data['label'] ?? $address->label,
            'lat' => $data['lat'] ?? $address->lat,
            'lng' => $data['lng'] ?? $address->lng,
            'governorate' => $data['governorate'] ?? $address->governorate,
            'city' => $data['city'] ?? $address->city,
            'area' => $data['area'] ?? $address->area,
            'full_address' => $data['full_address'] ?? $address->full_address,
        ]);

        if (isset($data['is_default'])) {
            $this->setDefault($address->user_id, $address->id);
        }

        return $address->fresh();
    }

    public function delete(int $id): bool
    {
        $address = Address::find($id);
        if (!$address) {
            return false;
        }

        return $address->delete();
    }

    public function setDefault(int $userId, int $addressId): void
    {
        Address::where('user_id', $userId)->update(['is_default' => false]);
        Address::where('id', $addressId)->where('user_id', $userId)->update(['is_default' => true]);
    }
}
