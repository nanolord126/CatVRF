<?php declare(strict_types=1);

namespace App\Domains\Tickets\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TicketGenerationJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                Log::channel('audit')->info('Starting ticket generation job', [
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

                Log::channel('audit')->info('Ticket generation completed', [
                    'sale_id' => $this->ticketSaleId,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Ticket generation failed', [
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
