<?php

namespace App\Modules\Payments\Repositories;

use App\Modules\Payments\Models\Payment;
use App\Support\Enums\PaymentStatus;

class PaymentRepository
{
    public function createForOrder(int $orderId, string $gateway, float $amount): Payment
    {
        return Payment::create([
            'order_id' => $orderId,
            'gateway' => $gateway,
            'status' => PaymentStatus::Pending,
            'amount' => $amount,
        ]);
    }

    public function updateStatus(int $paymentId, string $status, ?string $transactionRef): ?Payment
    {
        $payment = Payment::find($paymentId);
        if (!$payment) {
            return null;
        }

        $updateData = [
            'status' => $status,
        ];

        if ($transactionRef !== null) {
            $updateData['transaction_ref'] = $transactionRef;
        }

        if ($status === PaymentStatus::Paid->value) {
            $updateData['paid_at'] = now();
        }

        $payment->update($updateData);
        return $payment->fresh();
    }

    public function getByOrder(int $orderId): ?Payment
    {
        return Payment::where('order_id', $orderId)->first();
    }
}
