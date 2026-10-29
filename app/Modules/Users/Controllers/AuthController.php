<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Repositories\CourierRepository;
use App\Modules\Users\Repositories\UserRepository;
use App\Modules\Vendors\Repositories\StoreRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
        protected StoreRepository $storeRepository,
        protected CourierRepository $courierRepository
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'sometimes|string|in:customer,vendor,courier',
            'vehicle_type' => 'sometimes|string|max:50',
            'governorate' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'area' => 'required|string|max:255',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = $this->userRepository->create($validated);

        $role = $validated['role'] ?? 'customer';

        // Auto-create store for vendor users
        if ($role === 'vendor') {
            $this->storeRepository->create([
                'owner_user_id' => $user->id,
                'name' => $validated['name'] . "'s Store",
                'governorate' => $validated['governorate'],
                'city' => $validated['city'],
                'area' => $validated['area'],
            ]);
        } elseif ($role === 'courier') {
            // Auto-create courier profile so couriers can go online and receive tasks
            $this->courierRepository->create($user->id, [
                'vehicle_type' => $validated['vehicle_type'] ?? 'bike',
                'status' => 'offline',
            ]);
        } else {
            // Create default address for customer users
            $user->addresses()->create([
                'label' => 'Home',
                'governorate' => $validated['governorate'],
                'city' => $validated['city'],
                'area' => $validated['area'],
                'full_address' => $validated['area'] . ', ' . $validated['city'] . ', ' . $validated['governorate'],
                'is_default' => true,
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $user->load('store');

        return ApiResponse::success([
            'user' => $user,
            'token' => $token,
        ], 'Registration successful', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = $this->userRepository->findByEmail($validated['email']);

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $user->load('store');

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role->value,
            'status' => $user->status,
        ];

        // Include store for vendor users
        if ($user->role->value === 'vendor' && $user->store) {
            $userData['store_id'] = $user->store->id;
            $userData['store'] = $user->store;
        }

        return ApiResponse::success([
            'user' => $userData,
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['addresses', 'store.workingHours']);

        return ApiResponse::success($user);
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
        ]);

        // Stub — repository would handle OTP generation/sending
        return ApiResponse::success(['otp' => '******'], 'OTP sent successfully');
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string',
        ]);

        // Stub — repository would verify OTP
        return ApiResponse::success(null, 'OTP verified successfully');
    }
}
