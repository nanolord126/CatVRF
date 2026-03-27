<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Events\Services;

namespace App\Domains\EventPlanning\Events\Services;

use App\Domains\EventPlanning\Events\Models\Event;
use App\Domains\EventPlanning\Events\Models\Ticket;
use App\Domains\EventPlanning\Events\Models\TicketOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Сервис управления мероприятиями и билетами - КАНОН 2026.
 * QR-билеты, чекины, страховки, 14% комиссия.
 */
final class EventService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Создание заказа на билеты (Эскроу до начала мероприятия).
     */
    public function buyTickets(int $userId, int $eventId, array $data, string $correlationId = ""): TicketOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $event = Event::findOrFail($eventId);

        if (RateLimiter::tooManyAttempts("events:buy:".$userId, 5)) {
            throw new \RuntimeException("Ticket purchase limit reached.", 429);
        }
        RateLimiter::hit("events:buy:".$userId, 3600);

        return DB::transaction(function () use ($userId, $eventId, $event, $data, $correlationId) {
            $this->fraud->check([
                "user_id" => $userId,
                "operation_type" => "event_ticket_purchase",
                "correlation_id" => $correlationId
            ]);

            $totalAmount = $data["total_amount"];
            $fee = (int) ($totalAmount * 0.14);
            $payout = $totalAmount - $fee;

            // Холдирование средств (Эскроу)
            $this->wallet->hold(
                $userId,
                $totalAmount,
                "event_purchase",
                "Event: {$event->title}",
                $correlationId
            );

            $order = TicketOrder::create([
                "uuid" => (string) Str::uuid(),
                "tenant_id" => auth()->user()->tenant_id ?? 1,
                "user_id" => $userId,
                "event_id" => $eventId,
                "total_price" => $totalAmount,
                "platform_fee" => $fee,
                "status" => "confirmed",
                "correlation_id" => $correlationId,
                "tags" => ["vertical:events", "type:ticket_order"]
            ]);

            // Генерация билетов (упрощенно)
            foreach ($data["tickets"] as $tData) {
                Ticket::create([
                    "uuid" => (string) Str::uuid(),
                    "order_id" => $order->id,
                    "event_id" => $eventId,
                    "ticket_type_id" => $tData["type_id"],
                    "qr_code" => (string) Str::uuid(), // В реальности - зашифрованный JWT
                    "status" => "valid"
                ]);
            }

            Log::channel("audit")->info("Events: tickets ordered", ["order_uuid" => $order->uuid, "fee" => $fee]);

            return $order;
        });
    }

    /**
     * Регистрация гостя на входе (Check-in).
     */
    public function checkIn(string $qrCode, int $operatorId, string $correlationId = ""): array
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $ticket = Ticket::where("qr_code", $qrCode)->firstOrFail();

        if ($ticket->status !== "valid") {
            throw new \RuntimeException("Ticket already used or invalid.", 403);
        }

        return DB::transaction(function () use ($ticket, $operatorId, $correlationId) {
            $ticket->update([
                "status" => "used",
                "checked_in_at" => now(),
                "checked_in_by" => $operatorId
            ]);

            Log::channel("audit")->info("Events: guest check-in successful", [
                "ticket_id" => $ticket->id,
                "operator" => $operatorId
            ]);

            return [
                "success" => true,
                "ticket_id" => $ticket->id,
                "event_title" => $ticket->event->title ?? "Event"
            ];
        });
    }

    /**
     * Финализация выплат организатору после завершения события.
     */
    public function settleEventPayouts(int $eventId, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $event = Event::findOrFail($eventId);

        if ($event->end_at > now()) {
            throw new \RuntimeException("Event still in progress. Payout locked.");
        }

        $orders = TicketOrder::where("event_id", $eventId)->where("status", "confirmed")->get();

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, $event, $correlationId) {
                $payout = $order->total_price - $order->platform_fee;

                $this->wallet->releaseHold($order->user_id, $order->total_price, $correlationId);
                $this->wallet->credit($event->organizer_id, $payout, "event_organizer_payout", "Event payout #{$event->id}", $correlationId);

                $order->update(["status" => "finalized_payout"]);
            });
        }

        Log::channel("audit")->info("Events: event payouts settled", ["event_id" => $eventId]);
    }
}
