<?php

namespace App\Modules\Delivery\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Repositories\EarningsRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    public function __construct(
        protected EarningsRepository $earningsRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $earnings = $this->earningsRepository->getByCourier($request->user()->id);

        return ApiResponse::success($earnings);
    }
}
