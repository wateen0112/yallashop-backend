<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Users\Models\ProfileUpdate;
use App\Modules\Users\Models\StoreWorkingHour;
use App\Modules\Vendors\Models\Store;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'email'        => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'phone'        => 'sometimes|string|unique:users,phone,' . $request->user()->id,
            'avatar'       => 'sometimes|nullable|string|max:500',
            'cover_photo'  => 'sometimes|nullable|string|max:500',
            'working_hours' => 'sometimes|array',
            'working_hours.*.day'      => 'required|string',
            'working_hours.*.open_time' => 'nullable|string',
            'working_hours.*.close_time' => 'nullable|string',
            'working_hours.*.is_closed' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        // Courier name change requires admin approval
        if ($user->role === 'courier' && isset($validated['name']) && $validated['name'] !== $user->name) {
            ProfileUpdate::create([
                'user_id'   => $user->id,
                'field'     => 'name',
                'old_value' => $user->name,
                'new_value' => $validated['name'],
                'status'    => 'pending',
            ]);
            unset($validated['name']);
        }

        // Handle vendor-specific fields
        $workingHours = $validated['working_hours'] ?? null;
        $coverPhoto = $validated['cover_photo'] ?? null;
        unset($validated['working_hours'], $validated['cover_photo']);

        // Update user fields
        if (!empty($validated)) {
            $user->update($validated);
        }

        // Update store cover photo and working hours for vendors
        if ($user->role === 'vendor') {
            $store = Store::where('owner_user_id', $user->id)->first();

            if ($store) {
                if ($coverPhoto !== null) {
                    $store->update(['cover_photo' => $coverPhoto]);
                }

                if ($workingHours !== null) {
                    foreach ($workingHours as $hour) {
                        StoreWorkingHour::updateOrCreate(
                            ['store_id' => $store->id, 'day' => $hour['day']],
                            [
                                'open_time'  => $hour['open_time'] ?? null,
                                'close_time' => $hour['close_time'] ?? null,
                                'is_closed'  => $hour['is_closed'] ?? false,
                            ]
                        );
                    }
                }
            }
        }

        // Reload user with store and working hours data
        $user->load(['addresses', 'store.workingHours']);

        return ApiResponse::success($user, 'Profile updated successfully');
    }
}
