<?php

namespace App\Modules\Marketing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Models\CarouselAd;
use App\Services\CloudinaryService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarouselAdController extends Controller
{
    public function __construct(
        protected CloudinaryService $cloudinary
    ) {}

    public function index(): JsonResponse
    {
        $ads = CarouselAd::orderBy('sort_order')->get();
        return ApiResponse::success($ads);
    }

    public function show(int $id): JsonResponse
    {
        $ad = CarouselAd::find($id);
        if (! $ad) {
            return ApiResponse::error('Carousel ad not found', 404);
        }
        return ApiResponse::success($ad);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'media_file'  => 'nullable|file|max:51200|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg',
            'media_url'   => 'nullable|string',
            'link_type'   => 'required|string|in:external,product,category',
            'link_target' => 'required|string',
            'duration_ms' => 'sometimes|integer|min:1000',
            'sort_order'  => 'sometimes|integer',
            'is_active'   => 'sometimes|boolean',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after:start_date',
        ]);

        if (isset($validated['media_file'])) {
            $result = $this->cloudinary->upload($validated['media_file'], 'carousel');
            $validated['media_url'] = $result['url'];
            $validated['media_type'] = $result['media_type'];
            unset($validated['media_file']);
        } else {
            $validated['media_type'] = 'image';
            unset($validated['media_file']);
        }

        $ad = CarouselAd::create($validated);
        return ApiResponse::success($ad, 'Carousel ad created successfully', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $ad = CarouselAd::find($id);
        if (! $ad) {
            return ApiResponse::error('Carousel ad not found', 404);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'media_file'  => 'nullable|file|max:51200|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg',
            'media_url'   => 'nullable|string',
            'link_type'   => 'sometimes|string|in:external,product,category',
            'link_target' => 'sometimes|string',
            'duration_ms' => 'sometimes|integer|min:1000',
            'sort_order'  => 'sometimes|integer',
            'is_active'   => 'sometimes|boolean',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after:start_date',
        ]);

        if (isset($validated['media_file'])) {
            $result = $this->cloudinary->upload($validated['media_file'], 'carousel');
            $validated['media_url'] = $result['url'];
            $validated['media_type'] = $result['media_type'];
            unset($validated['media_file']);
        } else {
            unset($validated['media_file']);
        }

        $ad->update($validated);
        return ApiResponse::success($ad, 'Carousel ad updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $ad = CarouselAd::find($id);
        if (! $ad) {
            return ApiResponse::error('Carousel ad not found', 404);
        }
        $ad->delete();
        return ApiResponse::success(null, 'Carousel ad deleted successfully');
    }

    public function getActive(): JsonResponse
    {
        $ads = CarouselAd::active()->get();
        return ApiResponse::success($ads);
    }
}
