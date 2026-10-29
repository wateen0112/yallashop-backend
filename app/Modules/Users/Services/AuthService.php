<?php

namespace App\Modules\Users\Services;

use App\Modules\Users\Repositories\UserRepository;
use App\Support\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    public function register(array $data): array
    {
        $role = $data['role'] ?? UserRole::Customer->value;
        $user = $this->userRepository->create($data);
        $user->assignRole($role);
        $token = $user->createToken('api-token')->plainTextToken;
        $user->load('customerProfile');
        return ['user' => $user, 'token' => $token];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $token = $user->createToken('api-token')->plainTextToken;
        $user->load('customerProfile');
        return ['user' => $user, 'token' => $token];
    }

    public function logout($user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function requestOtp(string $phone): array
    {
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('otp_' . $phone, $otp, now()->addMinutes(5));
        return ['otp' => str_repeat('*', 6), 'expires_in' => 300];
    }

    public function verifyOtp(string $phone, string $otp): bool
    {
        $cached = Cache::get('otp_' . $phone);
        if (!$cached || $cached !== $otp) {
            return false;
        }
        Cache::forget('otp_' . $phone);
        return true;
    }
}
