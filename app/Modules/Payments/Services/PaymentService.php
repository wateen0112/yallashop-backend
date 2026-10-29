<?php

namespace App\Modules\Payments\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Repositories\PaymentRepository;
use App\Modules\Payments\Repositories\PayoutRepository;
use App\Support\Enums\PaymentMethod;
use App\Support\Enums\PaymentStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentService
{
    public function __construct(
        protected PaymentRepository $paymentRepository,
        protected PayoutRepository $payoutRepository
    ) {}

    public function processPayment(int $orderId, string $method, array $data = []): Payment
    {
        $order = Order::with('payment')->findOrFail($orderId);
        $amount = (float) $order->grand_total;

        if ($method === PaymentMethod::CashOnDelivery->value) {
            return $this->paymentRepository->createForOrder($orderId, 'cod', $amount);
        }

        $payment = $this->paymentRepository->createForOrder($orderId, $method, $amount);

        $gatewayResponse = $this->callPaymentGateway($payment, $data);

        if ($gatewayResponse['success'] ?? false) {
            $this->paymentRepository->updateStatus(
                $payment->id,
                PaymentStatus::Paid->value,
                $gatewayResponse['transaction_ref'] ?? null
            );
        }

        return $payment->fresh();
    }

    protected function callPaymentGateway(Payment $payment, array $data): array
    {
        return [
            'success' => true,
            'transaction_ref' => 'TXN-' . strtoupper(bin2hex(random_bytes(8))),
        ];
    }

    public function handleGatewayWebhook(array $payload): void
    {
        // In production: validate signature, update payment status, dispatch events
    }

    public function getPayouts(array $filters = []): LengthAwarePaginator
    {
        return $this->payoutRepository->getAll($filters);
    }

    public function createPayout(array $data): \App\Modules\Payments\Models\Payout
    {
        return $this->payoutRepository->create($data);
    }
}
