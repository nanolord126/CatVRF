<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TicketValidationService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private FraudControlService $fraudControl,
            private string $correlationId = ''
        ) {
        }

        private function getCorrelationId(): string
        {
            return $this->correlationId ?: (string) Str::uuid();
        }

        /**
         * Валидация (проверка) билета на входе
         */
        public function validateTicket(string $ticketNumber): array
        {
            $correlationId = $this->getCorrelationId();

            Log::channel('audit')->info('Attempting ticket validation', [
                'ticket_number' => $ticketNumber,
                'correlation_id' => $correlationId,
            ]);

            // 1. Поиск билета
            $ticket = Ticket::where('ticket_number', $ticketNumber)->first();

            if (!$ticket) {
                Log::channel('audit')->error('Ticket not found', [
                    'ticket_number' => $ticketNumber,
                    'correlation_id' => $correlationId,
                ]);
                return ['success' => false, 'message' => 'Ticket not found'];
            }

            // 2. Fraud Check (предотвращение двойного прохода или краденых билетов)
            $this->fraudControl->check([
                'operation' => 'ticket_validation',
                'ticket_id' => $ticket->id,
                'correlation_id' => $correlationId,
            ]);

            // 3. Проверка статуса
            if ($ticket->isValidated()) {
                Log::channel('audit')->warning('Ticket already validated', [
                    'ticket_number' => $ticketNumber,
                    'validated_at' => $ticket->validated_at,
                    'correlation_id' => $correlationId,
                ]);
                return ['success' => false, 'message' => 'Ticket already used at ' . (string)$ticket->validated_at];
            }

            // 4. Метка прохода
            return DB::transaction(function () use ($ticket, $correlationId) {
                $lockingTicket = Ticket::where('id', $ticket->id)->lockForUpdate()->first();

                $lockingTicket->validate();

                Log::channel('audit')->info('Ticket successfully validated', [
                    'ticket_uuid' => $lockingTicket->uuid,
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Welcome! Ticket validated.',
                    'seat' => $lockingTicket->seat_label,
                ];
            });
        }
}
