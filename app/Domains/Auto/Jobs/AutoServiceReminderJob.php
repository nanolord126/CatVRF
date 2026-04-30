<?php

declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use DateTime;

final class AutoServiceReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly string $correlationId;

    private readonly string $type; // '24h' or '2h'

    public function __construct(
        private readonly FraudControlService $fraud,
        string $type,
        private readonly LoggerInterface $logger
    ) {
        $this->correlationId = Str::uuid()->toString();
        $this->type = $type;
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $this->logger->info('Auto service reminder job started', [
            'correlation_id' => $this->correlationId,
            'type' => $this->type,
        ]);

        try {
            $targetTime = $this->type === '24h'
                ? (new Carbon())->addHours(24)
                : (new Carbon())->addHours(2);

            $orders = AutoServiceOrder::where('status', 'confirmed')
                ->whereBetween('appointment_datetime', [
                    $targetTime->subMinutes(30),
                    $targetTime->addMinutes(30),
                ])
                ->whereDoesntHave('reminders', function ($query) {
                    $query->where('type', $this->type)
                        ->where('sent_at', '>=', (new Carbon())->subHours(1));
                })
                ->with('client')
                ->get();

            foreach ($orders as $order) {
                $order->client->notify(new ServiceOrderReminderNotification($order, $this->type));
            }

            $this->logger->info('Auto service reminders sent', [
                'correlation_id' => $this->correlationId,
                'type' => $this->type,
                'sent_count' => $orders->count(),
            ]);
        } catch (Exception $e) {
            $this->logger->error('Auto service reminder job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function tags(): array
    {
        return ['auto', 'service', 'reminder', $this->type, $this->correlationId];
    }

    public function failed(Exception $exception): void
    {
        $this->logger->error('auto job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
