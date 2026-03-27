<?php declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Hotels\Models\Hotel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

final class PayoutScheduleService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,)
    {
    }

    /**
     * Расчитать выплату для отеля (4 дня после выселения)
     */
    public function scheduleHotelPayout(int $bookingId, int $amount, string $correlationId): bool
    {


        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            DB::transaction(function () use ($bookingId, $amount, $correlationId) {
                $payoutDate = Carbon::now()->addDays(4);

                DB::table('hotel_payouts')->insert([
                    'booking_id' => $bookingId,
                    'amount' => $amount,
                    'scheduled_at' => $payoutDate,
                    'status' => 'scheduled',
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::channel('audit')->info('Hotel payout scheduled', [
                    'booking_id' => $bookingId,
                    'amount' => $amount,
                    'payout_date' => $payoutDate,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Hotel payout scheduling failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Обработать запланированные выплаты
     */
    public function processScheduledPayouts(string $correlationId): int
    {


        $processed = 0;

        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            DB::transaction(function () use (&$processed, $correlationId) {
                $payouts = DB::table('hotel_payouts')
                    ->where('status', 'scheduled')
                    ->where('scheduled_at', '<=', now())
                    ->lockForUpdate()
                    ->get();

                foreach ($payouts as $payout) {
                    DB::table('hotel_payouts')
                        ->where('id', $payout->id)
                        ->update(['status' => 'paid', 'paid_at' => now()]);

                    $processed++;

                    Log::channel('audit')->info('Hotel payout processed', [
                        'payout_id' => $payout->id,
                        'booking_id' => $payout->booking_id,
                        'amount' => $payout->amount,
                        'correlation_id' => $correlationId,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Hotel payout processing failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $processed;
    }
}
