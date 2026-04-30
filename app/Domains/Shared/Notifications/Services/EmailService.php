<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UserEmailPreference;
use App\Mail\OrderCreatedMail;
use App\Mail\OrderConfirmedMail;
use App\Mail\OrderInDeliveryMail;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderCancelledMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final readonly class EmailService
{
    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $preferences = UserEmailPreference::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->orderNotifications()
            ->get();

        foreach ($preferences as $preference) {
            $recipientType = $preference->user_id === $buyerId ? 'buyer' : 'seller';
            
            try {
                $this->sendOrderEmail(
                    $preference->email,
                    $orderId,
                    $eventType,
                    $recipientType,
                    $data
                );
                $preference->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Failed to send order email', [
                    'email' => $preference->email,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function sendOrderEmail(
        string $email,
        int $orderId,
        string $eventType,
        string $recipientType,
        array $data = []
    ): void {
        $mailable = match($eventType) {
            'created' => new OrderCreatedMail($orderId, $recipientType, $data),
            'confirmed' => new OrderConfirmedMail($orderId, $recipientType, $data),
            'ready_for_delivery' => new OrderConfirmedMail($orderId, $recipientType, $data),
            'in_delivery' => new OrderInDeliveryMail($orderId, $recipientType, $data),
            'delivered' => new OrderDeliveredMail($orderId, $recipientType, $data),
            'cancelled' => new OrderCancelledMail($orderId, $recipientType, $data),
            default => null,
        };

        if ($mailable) {
            Mail::to($email)->send($mailable);
        }
    }

    public function sendNotificationToUser(int $userId, string $subject, string $htmlContent): bool
    {
        $preference = UserEmailPreference::where('user_id', $userId)
            ->active()
            ->verified()
            ->first();

        if (!$preference) {
            return false;
        }

        try {
            Mail::to($preference->email)->send(new class($subject, $htmlContent) {
                public function __construct(
                    public string $subject,
                    public string $htmlContent
                ) {}

                public function build()
                {
                    return $this
                        ->subject($this->subject)
                        ->html($this->htmlContent);
                }
            });

            $preference->markAsNotified();
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send notification email', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function generateVerificationLink(int $userId): string
    {
        $preference = UserEmailPreference::firstOrCreate(
            ['user_id' => $userId],
            ['email' => '', 'is_active' => false]
        );

        $token = $preference->generateVerificationToken();
        return url("/email/verify/{$token}");
    }

    public function verifyEmail(int $userId, string $email, string $token): ?UserEmailPreference
    {
        $preference = UserEmailPreference::where('user_id', $userId)
            ->where('verification_token', $token)
            ->first();

        if (!$preference) {
            return null;
        }

        $preference->update(['email' => $email]);
        $preference->verify();

        return $preference;
    }

    public function unsubscribe(string $email): bool
    {
        $preference = UserEmailPreference::where('email', $email)->first();

        if (!$preference) {
            return false;
        }

        $preference->update(['is_active' => false]);
        return true;
    }
}
