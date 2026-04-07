<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class TicketValidationService
{

    private readonly string $correlationId;


    public function __construct(private FraudControlService $fraud,
            string $correlationId = '',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

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

            $this->logger->info('Attempting ticket validation', [
                'ticket_number' => $ticketNumber,
                'correlation_id' => $correlationId,
            ]);

            // 1. Поиск билета
            $ticket = Ticket::where('ticket_number', $ticketNumber)->first();

            if (!$ticket) {
                $this->logger->error('Ticket not found', [
                    'ticket_number' => $ticketNumber,
                    'correlation_id' => $correlationId,
                ]);
                return ['success' => false, 'message' => 'Ticket not found'];
            }

            // 2. Fraud Check (предотвращение двойного прохода или краденых билетов)
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ticket_validation', amount: 0, correlationId: $correlationId ?? '');

            // 3. Проверка статуса
            if ($ticket->isValidated()) {
                $this->logger->warning('Ticket already validated', [
                    'ticket_number' => $ticketNumber,
                    'validated_at' => $ticket->validated_at,
                    'correlation_id' => $correlationId,
                ]);
                return ['success' => false, 'message' => 'Ticket already used at ' . (string)$ticket->validated_at];
            }

            // 4. Метка прохода
            return $this->db->transaction(function () use ($ticket, $correlationId) {
                $lockingTicket = Ticket::where('id', $ticket->id)->lockForUpdate()->first();

                $lockingTicket->validate();

                $this->logger->info('Ticket successfully validated', [
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
