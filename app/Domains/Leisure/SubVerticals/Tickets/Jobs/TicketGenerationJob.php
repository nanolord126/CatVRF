<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Tickets\Jobs;



use Psr\Log\LoggerInterface;
use App\Services\Tickets\TicketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class TicketGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    public array $backoff = [60, 300, 900];
    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly int $ticketSaleId,
        private readonly string $correlationId, private readonly LoggerInterface $logger
    ) {

    }

    public function handle(TicketService $ticketService): void
    {
        $this->logger->$this->logger->info('[TicketGenerationJob] Started', [
            'ticket_sale_id' => $this->ticketSaleId,
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $ticketService->generateTicket($this->ticketSaleId, $this->correlationId);

            $this->logger->$this->logger->info('[TicketGenerationJob] Finished successfully', [
                'ticket_sale_id' => $this->ticketSaleId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('[TicketGenerationJob] Failed', [
                'ticket_sale_id' => $this->ticketSaleId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    public function retryUntil(): \DateTimeInterface
    {
        return CarbonImmutable::now()->addMinutes(5);
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('tickets job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}