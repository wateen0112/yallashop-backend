<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Users\Repositories\AddressRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(
        protected AddressRepository $addressRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $addresses = $this->addressRepository->getByUser($request->user()->id);
        return ApiResponse::success($addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label'        => 'required|string|max:100',
            'lat'          => 'nullable|numeric',
            'lng'          => 'nullable|numeric',
            'governorate'  => 'required|string|max:100',
            'city'         => 'required|string|max:100',
            'area'         => 'required|string|max:100',
            'full_address' => 'required|string|max:500',
            'is_default'   => 'sometimes|boolean',
        ]);

        $address = $this->addressRepository->create($request->user()->id, $validated);

        return ApiResponse::success($address, 'Address added successfully', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'label'        => 'sometimes|string|max:100',
            'lat'          => 'nullable|numeric',
            'lng'          => 'nullable|numeric',
            'governorate'  => 'sometimes|string|max:100',
            'city'         => 'sometimes|string|max:100',
            'area'         => 'sometimes|string|max:100',
            'full_address' => 'sometimes|string|max:500',
            'is_default'   => 'sometimes|boolean',
        ]);

        $address = $this->addressRepository->update($id, $validated);

        if (!$address) {
            return ApiResponse::error('Address not found', 404);
        }

        return ApiResponse::success($address, 'Address updated successfully');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $this->addressRepository->delete($id);

        if (!$deleted) {
            return ApiResponse::error('Address not found', 404);
        }

        return ApiResponse::success(null, 'Address deleted successfully');
    }

    public function setDefault(Request $request, int $id): JsonResponse
    {
        $this->addressRepository->setDefault($request->user()->id, $id);
        return ApiResponse::success(null, 'Default address updated');
    }
}
