<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Users\Controllers\AuthController as ModuleAuthController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected ModuleAuthController $moduleController;

    public function __construct(ModuleAuthController $moduleController)
    {
        $this->moduleController = $moduleController;
    }

    public function register(Request $request): JsonResponse
    {
        return $this->moduleController->register($request);
    }

    public function login(Request $request): JsonResponse
    {
        return $this->moduleController->login($request);
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->moduleController->logout($request);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->moduleController->me($request);
    }

    public function requestOtp(Request $request): JsonResponse
    {
        return $this->moduleController->requestOtp($request);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        return $this->moduleController->verifyOtp($request);
    }
}
