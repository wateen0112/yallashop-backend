<?php

namespace App\Modules\Payments\Repositories;

use App\Modules\Delivery\Models\Courier;
use App\Modules\Orders\Models\SubOrder;
use App\Modules\Payments\Models\Payout;
use App\Support\Enums\OrderStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PayoutRepository
{
    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = Payout::query();

        if (!empty($filters['payee_type'])) {
            $query->where('payee_type', $filters['payee_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function create(array $data): Payout
    {
        return Payout::create([
            'payee_type' => $data['payee_type'],
            'payee_id' => $data['payee_id'],
            'amount' => $data['amount'],
            'status' => $data['status'] ?? 'pending',
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
        ]);
    }

    public function processBatch(string $payeeType): Collection
    {
        return DB::transaction(function () use ($payeeType) {
            if ($payeeType === Courier::class) {
                $couriers = Courier::where('wallet_balance', '>', 0)->get();
                $payouts = new Collection();

                foreach ($couriers as $courier) {
                    $amount = (float) $courier->wallet_balance;
                    if ($amount <= 0) {
                        continue;
                    }

                    $payout = Payout::create([
                        'payee_type' => $payeeType,
                        'payee_id' => $courier->id,
                        'amount' => $amount,
                        'status' => 'pending',
                    ]);

                    $courier->update(['wallet_balance' => 0]);
                    $payouts->push($payout);
                }

                return $payouts;
            }

            return new Collection();
        });
    }

    public function updateStatus(int $payoutId, string $status): ?Payout
    {
        $payout = Payout::find($payoutId);
        if (!$payout) {
            return null;
        }

        $updateData = ['status' => $status];

        if ($status === 'paid') {
            $updateData['paid_at'] = now();
        }

        $payout->update($updateData);
        return $payout->fresh();
    }
}
