<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\Currency;
use Illuminate\Support\Collection;

class CurrencyRepository
{
    public function getAll(): Collection
    {
        return Currency::orderBy('is_default', 'desc')->orderBy('name')->get();
    }

    public function find(int $id): ?Currency
    {
        return Currency::find($id);
    }

    public function getDefault(): ?Currency
    {
        return Currency::where('is_default', true)->first();
    }

    public function create(array $data): Currency
    {
        if (!empty($data['is_default']) && $data['is_default']) {
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        return Currency::create($data);
    }

    public function update(int $id, array $data): ?Currency
    {
        $currency = Currency::find($id);
        if (!$currency) {
            return null;
        }

        if (!empty($data['is_default']) && $data['is_default']) {
            Currency::where('is_default', true)->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $currency->update($data);
        return $currency;
    }

    public function delete(int $id): bool
    {
        $currency = Currency::find($id);
        if (!$currency) {
            return false;
        }

        if ($currency->is_default) {
            return false;
        }

        return $currency->delete();
    }

    public function setDefault(int $id): bool
    {
        $currency = Currency::find($id);
        if (!$currency) {
            return false;
        }

        Currency::where('is_default', true)->update(['is_default' => false]);
        $currency->update(['is_default' => true]);

        return true;
    }
}
