<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\CurrencyRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(
        protected CurrencyRepository $currencyRepository
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success($this->currencyRepository->getAll());
    }

    public function default(): JsonResponse
    {
        $currency = $this->currencyRepository->getDefault();
        if (!$currency) {
            return ApiResponse::error('No default currency set', 404);
        }
        return ApiResponse::success($currency);
    }

    public function adminIndex(): JsonResponse
    {
        return ApiResponse::success($this->currencyRepository->getAll());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:currencies,code',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'sometimes|numeric|min:0',
            'is_default' => 'sometimes|boolean',
        ]);

        $validated['exchange_rate'] = $validated['exchange_rate'] ?? 1.0;
        $validated['is_default'] = $validated['is_default'] ?? false;

        return ApiResponse::success(
            $this->currencyRepository->create($validated),
            'Currency created',
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:10|unique:currencies,code,' . $id,
            'name' => 'sometimes|string|max:255',
            'symbol' => 'sometimes|string|max:10',
            'exchange_rate' => 'sometimes|numeric|min:0',
            'is_default' => 'sometimes|boolean',
        ]);

        $currency = $this->currencyRepository->update($id, $validated);
        if (!$currency) {
            return ApiResponse::error('Currency not found', 404);
        }
        return ApiResponse::success($currency, 'Currency updated');
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->currencyRepository->delete($id)) {
            return ApiResponse::error('Currency not found or is the default currency', 400);
        }
        return ApiResponse::success(null, 'Currency deleted');
    }

    public function setDefault(int $id): JsonResponse
    {
        if (!$this->currencyRepository->setDefault($id)) {
            return ApiResponse::error('Currency not found', 404);
        }
        return ApiResponse::success(
            $this->currencyRepository->find($id),
            'Default currency updated'
        );
    }
}
