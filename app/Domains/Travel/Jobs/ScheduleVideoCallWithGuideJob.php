<?php declare(strict_types=1);

namespace App\Domains\Travel\Jobs;

use App\Domains\Travel\Models\TourBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

/**
 * Schedule Video Call With Guide Job
 * 
 * Schedules an instant video call with a tour guide.
 * Integration with video conferencing service (Zoom, Jitsi, or custom).
 */
final readonly class ScheduleVideoCallWithGuideJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly int $bookingId,
        public readonly string $correlationId,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(): void
    {
        $booking = TourBooking::with('tour')->findOrFail($this->bookingId);

        $scheduledTime = $booking->video_call_time ?? now()->addHours(24);

        $response = Http::timeout(10)->post(config('services.video_call.endpoint'), [
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'tour_id' => $booking->tour_id,
            'scheduled_time' => $scheduledTime->toIso8601String(),
            'duration_minutes' => 30,
            'callback_url' => route('tourism.video-call.callback', ['booking_uuid' => $booking->uuid]),
            'correlation_id' => $this->correlationId,
        ]);

        if (!$response->successful()) {
            $this->logger->error('Video call scheduling failed', [
                'booking_id' => $this->bookingId,
                'status' => $response->status(),
                'body' => $response->body(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->release(30);
        }

        $meetingData = $response->json();

        $booking->update([
            'video_call_meeting_id' => $meetingData['meeting_id'] ?? null,
            'video_call_join_url' => $meetingData['join_url'] ?? null,
        ]);

        $this->logger->info('Video call scheduled successfully', [
            'booking_id' => $this->bookingId,
            'meeting_id' => $meetingData['meeting_id'] ?? null,
            'scheduled_time' => $scheduledTime->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('Video call scheduling job failed', [
            'booking_id' => $this->bookingId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
