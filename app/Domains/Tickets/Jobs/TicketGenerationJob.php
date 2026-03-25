<?php declare(strict_types=1);

namespace App\Domains\Tickets\Jobs;

use App\Domains\Tickets\Models\TicketSale;
use App\Domains\Tickets\Services\TicketGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class TicketGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?int $ticketSaleId;
    private ?string $correlationId;

    public function __construct(int $ticketSaleId = null, string $correlationId = '')
    {
        $this->ticketSaleId = $ticketSaleId;
        $this->correlationId = $correlationId;
        $this->onQueue('tickets');

    }

    public function handle(TicketGenerationService $service): void
    {
        try {
            $this->log->channel('audit')->info('Starting ticket generation job', [
                'sale_id' => $this->ticketSaleId,
                'correlation_id' => $this->correlationId,
            ]);

            $sale = TicketSale::findOrFail($this->ticketSaleId);

            $service->generateTickets(
                $sale->event_id,
                $sale->event->ticketTypes()->first()->id,
                $sale->quantity,
                $sale->buyer_id,
                $this->correlationId
            );

            $this->log->channel('audit')->info('Ticket generation completed', [
                'sale_id' => $this->ticketSaleId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Ticket generation failed', [
                'sale_id' => $this->ticketSaleId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            $this->fail($e);
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(1);
    }
}



