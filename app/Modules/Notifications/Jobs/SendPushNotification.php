<?php

namespace App\Modules\Notifications\Jobs;

use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Models\Notification as InAppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendPushNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public array $data = []
    ) {}

    public function handle(): void
    {
        $tokens = FcmToken::where('user_id', $this->userId)->pluck('token')->toArray();

        // Persist an in-app notification so the user's feed works even when FCM is unavailable.
        try {
            InAppNotification::create([
                'user_id' => $this->userId,
                'type' => $this->data['type'] ?? 'push',
                'payload' => [
                    'title' => $this->title,
                    'body' => $this->body,
                    'data' => $this->data,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to persist notification for user {$this->userId}: {$e->getMessage()}");
        }

        if (empty($tokens)) {
            Log::info("No FCM tokens for user {$this->userId}");
            return;
        }

        try {
            $messaging = app('firebase.messaging');
            $notification = Notification::create($this->title, $this->body);
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($this->data);

            $messaging->sendEach($tokens, $message);

            Log::info("Push sent to user {$this->userId}: {$this->title}");
        } catch (\Exception $e) {
            Log::error("Failed to send push to user {$this->userId}: {$e->getMessage()}");
        }
    }
}