<?php

namespace App\Modules\Payments\Jobs;

use App\Modules\Payments\Repositories\PayoutRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPayoutBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $payoutRepo = app(PayoutRepository::class);
        $payouts = $payoutRepo->processBatch('App\Modules\Delivery\Models\Courier');

        foreach ($payouts as $payout) {
            Log::info("Payout {$payout->id} processed for {$payout->payee_type} {$payout->payee_id}: {$payout->amount}");
        }

        Log::info("Payout batch completed: {$payouts->count()} payouts processed");
    }
}
