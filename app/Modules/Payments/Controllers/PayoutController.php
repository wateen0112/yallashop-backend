<?php

namespace App\Modules\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Repositories\PayoutRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(
        protected PayoutRepository $payoutRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payouts = $this->payoutRepository->getAll();

        return ApiResponse::success($payouts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payee_type' => 'required|string|in:vendor,courier',
            'payee_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $payout = $this->payoutRepository->create($validated);

        return ApiResponse::success($payout, 'Payout created', 201);
    }
}
