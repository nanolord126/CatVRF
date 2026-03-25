<?php declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use App\Domains\Auto\Models\AutoServiceOrder;
use App\Notifications\Auto\ServiceOrderReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AutoServiceReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private readonly string $correlationId;
    private readonly string $type; // '24h' or '2h'

    public function __construct(string $type = '24h')
    {
        $this->correlationId = Str::uuid()->toString();
        $this->type = $type;
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $this->log->channel('audit')->info('Auto service reminder job started', [
            'correlation_id' => $this->correlationId,
            'type' => $this->type,
        ]);

        try {
            $targetTime = $this->type === '24h' 
                ? now()->addHours(24)
                : now()->addHours(2);

            $orders = AutoServiceOrder::where('status', 'confirmed')
                ->whereBetween('appointment_datetime', [
                    $targetTime->subMinutes(30),
                    $targetTime->addMinutes(30),
                ])
                ->whereDoesntHave('reminders', function ($query) {
                    $query->where('type', $this->type)
                        ->where('sent_at', '>=', now()->subHours(1));
                })
                ->with('client')
                ->get();

            foreach ($orders as $order) {
                $order->client->notify(new ServiceOrderReminderNotification($order, $this->type));
            }

            $this->log->channel('audit')->info('Auto service reminders sent', [
                'correlation_id' => $this->correlationId,
                'type' => $this->type,
                'sent_count' => $orders->count(),
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Auto service reminder job failed', [
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
}
