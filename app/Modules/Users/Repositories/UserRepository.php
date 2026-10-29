<?php

namespace App\Modules\Users\Repositories;

use App\Modules\Orders\Models\Cart;
use App\Modules\Users\Models\User;
use App\Support\Enums\UserRole;

class UserRepository
{
    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => UserRole::tryFrom($data['role'] ?? 'customer') ?? UserRole::Customer,
            'status' => $data['status'] ?? 'active',
        ]);

        $role = $data['role'] ?? 'customer';
        $user->assignRole($role);

        Cart::create(['user_id' => $user->id]);

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByIdWithStore(int $id): ?User
    {
        return User::with('store')->find($id);
    }
}
