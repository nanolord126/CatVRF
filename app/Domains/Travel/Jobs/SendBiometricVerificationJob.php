<?php declare(strict_types=1);

namespace App\Domains\Travel\Jobs;

use App\Domains\Travel\Models\TourBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Psr\Log\LoggerInterface;

/**
 * Send Biometric Verification Job
 * 
 * Sends biometric verification request to external biometric service.
 * User must verify identity via face recognition or fingerprint before booking confirmation.
 */
final class SendBiometricVerificationJob implements ShouldQueue
{

    public array $backoff = [60, 300, 900];
    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly int $bookingId,
        public readonly string $biometricToken,
        public readonly string $correlationId,
        private readonly LoggerInterface $logger,
    ) {}

    public function tags(): array
    {
        return ['travel', 'job'];
    }

    public function handle(HttpClientFactory $http): void
    {
        $booking = TourBooking::findOrFail($this->bookingId);

        $response = $http->timeout(10)->post(config('services.biometric.endpoint'), [
            'token' => $this->biometricToken,
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'verification_type' => 'face_recognition',
            'callback_url' => route('tourism.biometric.callback', ['token' => $this->biometricToken]),
            'expires_at' => $booking->hold_expires_at->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ]);

        if (!$response->successful()) {
            $this->logger->error('Biometric verification request failed', [
                'booking_id' => $this->bookingId,
                'status' => $response->status(),
                'body' => $response->body(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->release(30);
        }

        $this->logger->info('Biometric verification request sent', [
            'booking_id' => $this->bookingId,
            'biometric_token' => $this->biometricToken,
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('Biometric verification job failed', [
            'booking_id' => $this->bookingId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}