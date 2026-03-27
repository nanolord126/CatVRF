<?php

declare(strict_types=1);

namespace App\Domains\Tickets\Services;

use App\Domains\Tickets\Models\Ticket;
use App\Domains\Tickets\Models\Event;
use App\Domains\Tickets\Models\TicketType;
use App\Domains\Tickets\Models\CheckInLog;
use App\Domains\Tickets\DTO\BuyTicketDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * КАНОН 2026: Основной сервис работы с билетами.
 * Слой 3: Сервисы.
 */
final readonly class TicketService
{
    /**
     * Конструктор с зависимостями.
     */
    public function __construct(
        private readonly \App\Domains\Tickets\Services\TicketFraudService $ticketFraud,
        private readonly \App\Services\FraudControlService $fraud,
        private readonly \App\Services\WalletService $wallet,
        private readonly \App\Services\PromoCampaignService $promo
    ) {}

    /**
     * Покупка билета через Wallet и транзакции.
     */
    public function buyTickets(BuyTicketDto $dto): array
    {
        Log::channel('audit')->info('Ticket purchase initiated', $dto->toArray());

        // 1. Предварительная проверка фрода (Общая + Специфичная для Тикетов Слой 6)
        $this->ticketFraud->check(
            $dto->userId,
            $dto->eventId,
            $dto->ticketTypeId,
            $dto->quantity,
            $dto->correlation_id
        );

        return DB::transaction(function () use ($dto) {
            // 2. Блокировка (lock) на тип билета для атомарной продажи
            $ticketType = TicketType::where('id', $dto->ticketTypeId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$ticketType->canBuy($dto->quantity)) {
                throw new \Exception('Недостаточно билетов в наличии или превышен лимит');
            }

            // 3. Проверка лимитов эвента на количество билетов у пользователя
            $event = Event::findOrFail($dto->eventId);
            $userTicketCount = Ticket::where('event_id', $dto->eventId)
                ->where('user_id', $dto->userId)
                ->whereIn('status', ['active', 'used'])
                ->count();

            if (($userTicketCount + $dto->quantity) > $event->max_tickets_per_user) {
                throw new \Exception("Превышен лимит билетов на одного пользователя ({$event->max_tickets_per_user})");
            }

            // 4. Списание средств через WalletService (Wallet Канон 2026)
            $totalPrice = $ticketType->price * $dto->quantity;
            $this->wallet->debit($dto->userId, $totalPrice, 'ticket_purchase', [
                'event_id' => $dto->eventId,
                'correlation_id' => $dto->correlation_id
            ]);

            // 5. Создание билетов
            $createdTickets = [];
            for ($i = 0; $i < $dto->quantity; $i++) {
                $createdTickets[] = Ticket::create([
                    'event_id' => $dto->eventId,
                    'ticket_type_id' => $dto->ticketTypeId,
                    'user_id' => $dto->userId,
                    'price' => $ticketType->price,
                    'status' => 'active',
                    'sector' => $dto->sector,
                    'row' => $dto->row,
                    'number' => $dto ? $dto->number + $i : null, // Итерация по местам если указано
                    'expires_at' => $event->end_at->addHours(24),
                    'correlation_id' => $dto->correlation_id,
                    'metadata' => $dto->metadata
                ]);
            }

            // 6. Обновление счетчика продаж
            $ticketType->incrementSold($dto->quantity);

            Log::channel('audit')->info('Ticket purchase completed', [
                'count' => count($createdTickets),
                'total_price' => $totalPrice,
                'correlation_id' => $dto->correlation_id
            ]);

            return $createdTickets;
        });
    }

    /**
     * Валидация билета и Чекин (Check-in).
     */
    public function checkIn(string $qrCode, int $checkerUserId, array $requestData = []): array
    {
        $correlationId = $requestData['correlation_id'] ?? (string) Str::uuid();

        // 1. Поиск билета
        $ticket = Ticket::where('qr_code', $qrCode)->first();

        if (!$ticket) {
            $this->logCheckInAttempt(null, $checkerUserId, false, 'Ticket not found', $requestData);
            return ['success' => false, 'message' => 'Билет не найден'];
        }

        // 2. Валидация статуса
        if (!$ticket->isValid()) {
            $reason = $ticket->status === 'used' ? 'Билет уже использован' : 'Билет недействителен';
            $this->logCheckInAttempt($ticket->id, $checkerUserId, false, $reason, $requestData);
            return ['success' => false, 'message' => $reason];
        }

        // 3. Проверка на фрод (частое сканирование и т.д.)
        $this->fraud->check('ticket_checkin', [
            'ticket_id' => $ticket->id,
            'checker_user_id' => $checkerUserId,
            'correlation_id' => $correlationId
        ]);

        return DB::transaction(function () use ($ticket, $checkerUserId, $requestData, $correlationId) {
            // 4. Помечаем как использованный
            $ticket->markAsUsed();

            // 5. Логируем успешный проход
            $this->logCheckInAttempt($ticket->id, $checkerUserId, true, null, $requestData);

            Log::channel('audit')->info('Ticket check-in success', [
                'ticket_id' => $ticket->id,
                'qr' => $ticket->qr_code,
                'correlation_id' => $correlationId
            ]);

            return [
                'success' => true,
                'message' => 'Проход разрешен',
                'ticket' => $ticket->load(['event', 'ticketType'])
            ];
        });
    }

    /**
     * Внутренний метод логирования попыток прохода.
     */
    private function logCheckInAttempt(?int $ticketId, int $checkerId, bool $isSuccess, ?string $reason, array $meta): void
    {
        CheckInLog::create([
            'ticket_id' => $ticketId,
            'checker_user_id' => $checkerId,
            'is_success' => $isSuccess,
            'error_reason' => $reason,
            'ip_address' => $meta['ip'] ?? request()->ip(),
            'device_info' => $meta['device'] ?? ['agent' => request()->userAgent()],
            'correlation_id' => $meta['correlation_id'] ?? (string) Str::uuid(),
            'location' => $meta['location'] ?? null
        ]);
    }

    /**
     * Аннулирование билета (например при возврате).
     */
    public function cancelTicket(int $ticketId, string $reason = 'manual'): void
    {
        DB::transaction(function () use ($ticketId, $reason) {
            $ticket = Ticket::lockForUpdate()->findOrFail($ticketId);

            if ($ticket->status === 'used') {
                throw new \Exception('Нельзя отменить уже использованный билет');
            }

            $ticket->update(['status' => 'cancelled']);
            $ticket->ticketType->decrementSold(1);

            Log::channel('audit')->info('Ticket cancelled', [
                'id' => $ticketId,
                'reason' => $reason,
                'correlation_id' => $ticket->correlation_id
            ]);
        });
    }
}
