<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Entertainment\Events\TicketSold;
use App\Domains\Entertainment\Models\Booking;
use App\Domains\Entertainment\Models\TicketSale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TicketingService
{
    public function generateTickets(Booking $booking, string $correlationId): void
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'generateTickets'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL generateTickets', ['domain' => __CLASS__]);

        try {
            DB::transaction(function () use ($booking, $correlationId) {
                for ($i = 1; $i <= $booking->number_of_seats; $i++) {
                    $ticket = TicketSale::create([
                        'tenant_id' => $booking->tenant_id,
                        'booking_id' => $booking->id,
                        'ticket_number' => $i,
                        'seat_number' => "Seat-{$i}",
                        'ticket_price' => $booking->eventSchedule->ticket_price,
                        'barcode' => Str::random(16),
                        'status' => 'valid',
                        'correlation_id' => $correlationId,
                    ]);

                    event(new TicketSold($ticket, $correlationId));
                }

                Log::channel('audit')->info('Tickets generated', [
                    'booking_id' => $booking->id,
                    'ticket_count' => $booking->number_of_seats,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to generate tickets', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function refundTickets(Booking $booking, string $correlationId): void
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'refundTickets'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL refundTickets', ['domain' => __CLASS__]);

        try {
            DB::transaction(function () use ($booking, $correlationId) {
                TicketSale::where('booking_id', $booking->id)
                    ->update([
                        'status' => 'refunded',
                        'refunded_at' => now(),
                        'correlation_id' => $correlationId,
                    ]);

                Log::channel('audit')->info('Tickets refunded', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to refund tickets', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
