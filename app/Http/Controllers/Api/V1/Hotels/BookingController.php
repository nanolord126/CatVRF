declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Hotels;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Hotels\CreateBookingRequest;
use App\Models\Hotels\Booking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Hotels Booking API Controller.
 * Workflow: Create → Deposit hold (30%) → Check-in (capture) → Stay → Checkout (settle).
 *
 * Commission: 14% standard, 12% migration discount.
 * Deposit: 30% of total.
 * Cancellation: <7 days = no refund, >=7 days = full refund.
 * Payout: 4 days after checkout.
 */
final class BookingController extends BaseApiController
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly WalletService $walletService,
    ) {}

    /**
     * POST /api/v1/hotels/bookings
     * Создать бронирование отеля.
     *
     * @return JsonResponse
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        $tenantId = $request->getTenantId();

        try {
            return $this->db->transaction(function () use ($request, $correlationId, $tenantId) {
                // 1. Рассчитать сумму бронирования
                $nights = $request->integer('nights');
                $pricePerNight = $request->integer('price_per_night');
                $totalAmount = $nights * $pricePerNight;
                $depositAmount = intdiv((int) ($totalAmount * 30 / 100), 1); // 30% deposit

                // 2. Fraud check на высокие суммы
                $fraudResult = $this->fraudService->scoreOperation([
                    'type' => 'hotel_booking',
                    'amount' => $totalAmount,
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                if ($fraudResult['decision'] === 'block') {
                    $this->log->channel('fraud_alert')->warning('Hotel booking blocked', [
                        'correlation_id' => $correlationId,
                        'amount' => $totalAmount,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Booking blocked by fraud check',
                        'correlation_id' => $correlationId,
                    ], 403)->send();
                }

                // 3. Создать бронирование
                $booking = Booking::create([
                    'tenant_id' => $tenantId,
                    'hotel_id' => $request->integer('hotel_id'),
                    'room_type_id' => $request->integer('room_type_id'),
                    'user_id' => auth()->id(),
                    'check_in_date' => $request->input('check_in_date'),
                    'check_out_date' => $request->input('check_out_date'),
                    'nights' => $nights,
                    'price_per_night' => $pricePerNight,
                    'total_price' => $totalAmount,
                    'deposit_amount' => $depositAmount,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);

                // 4. Hold депозита (30% от суммы)
                $this->walletService->holdAmount(
                    wallet_id: auth()->user()->wallet_id ?? 1,
                    amount: $depositAmount,
                    reason: 'Hotel deposit for booking ' . $booking->id,
                    correlation_id: $correlationId,
                );

                // 5. Логирование
                $this->log->channel('audit')->info('Hotel booking created', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id,
                    'user_id' => auth()->id(),
                    'total' => $totalAmount,
                    'deposit' => $depositAmount,
                    'nights' => $nights,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Booking created successfully',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'id' => $booking->id,
                        'uuid' => $booking->uuid,
                        'total' => $booking->total_price,
                        'deposit' => $booking->deposit_amount,
                        'check_in' => $booking->check_in_date,
                        'check_out' => $booking->check_out_date,
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Hotel booking creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Booking creation failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /api/v1/hotels/bookings/{id}/check-in
     * Check-in (захватить полную сумму).
     */
    public function checkIn(Booking $booking, CreateBookingRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();

        try {
            return $this->db->transaction(function () use ($booking, $correlationId) {
                $booking->update([
                    'status' => 'checked_in',
                    'checked_in_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                // Захватить оставшуюся сумму (100% - 30% депозит)
                $remainingAmount = $booking->total_price - $booking->deposit_amount;
                $this->walletService->holdAmount(
                    wallet_id: auth()->user()->wallet_id ?? 1,
                    amount: $remainingAmount,
                    reason: 'Hotel final charge for booking ' . $booking->id,
                    correlation_id: $correlationId,
                );

                $this->log->channel('audit')->info('Hotel check-in completed', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Check-in successful',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Check-in failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Check-in failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /api/v1/hotels/bookings/{id}/checkout
     * Check-out (завершить бронирование, обработать возвраты).
     */
    public function checkOut(Booking $booking, CreateBookingRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();

        try {
            return $this->db->transaction(function () use ($booking, $correlationId) {
                $booking->update([
                    'status' => 'completed',
                    'checked_out_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                // Обработать ранний check-out (если раньше check_out_date)
                if ($request->has('early_checkout') && $request->boolean('early_checkout')) {
                    $daysUsed = now()->diffInDays($booking->checked_in_at);
                    $daysRefund = $booking->nights - $daysUsed;
                    $refundAmount = $daysRefund * $booking->price_per_night;

                    // Вернуть деньги за неиспользованные дни
                    $this->walletService->credit(
                        wallet_id: auth()->user()->wallet_id ?? 1,
                        amount: $refundAmount,
                        reason: 'Early checkout refund for booking ' . $booking->id,
                        correlation_id: $correlationId,
                    );

                    $this->log->channel('audit')->info('Early checkout processed', [
                        'correlation_id' => $correlationId,
                        'booking_id' => $booking->id,
                        'refund_amount' => $refundAmount,
                    ]);
                }

                // Расписать выплату отелю на 4 дня
                // В реальном приложении - создать scheduled payout
                $this->log->channel('audit')->info('Hotel checkout completed', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id,
                    'payout_scheduled_for' => now()->addDays(4)->format('Y-m-d'),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Check-out successful',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'id' => $booking->id,
                        'status' => 'completed',
                        'payout_scheduled' => now()->addDays(4)->format('Y-m-d'),
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Check-out failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Check-out failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /api/v1/hotels/bookings/{id}/cancel
     * Отменить бронирование (с политикой возврата).
     */
    public function cancel(Booking $booking, CreateBookingRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();

        try {
            return $this->db->transaction(function () use ($booking, $correlationId) {
                // Проверить время до check-in
                $daysUntilCheckIn = now()->diffInDays($booking->check_in_date);

                // Политика: <7 дней = нет возврата, >=7 дней = полный возврат
                if ($daysUntilCheckIn >= 7) {
                    // Полный возврат
                    $refundAmount = $booking->total_price;
                    $this->walletService->credit(
                        wallet_id: auth()->user()->wallet_id ?? 1,
                        amount: $refundAmount,
                        reason: 'Hotel booking cancellation refund',
                        correlation_id: $correlationId,
                    );
                } else {
                    // Возврат только депозита
                    $refundAmount = 0;
                }

                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Hotel booking cancelled', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id,
                    'days_until_checkin' => $daysUntilCheckIn,
                    'refund_amount' => $refundAmount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Booking cancelled',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'id' => $booking->id,
                        'refund' => $refundAmount,
                        'policy' => $daysUntilCheckIn >= 7 ? 'Full refund' : 'No refund',
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Booking cancellation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cancellation failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
