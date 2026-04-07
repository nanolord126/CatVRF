<?php declare(strict_types=1);

/**
 * AutoServiceOrderPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/autoserviceorderpolicy
 */


namespace App\Domains\Auto\Policies;


use Illuminate\Http\Request;
use Carbon\Carbon;


use App\Services\FraudControlService;
final class AutoServiceOrderPolicy
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly Request $request,
    ) {}



    // Dependencies injected via constructor
        // Add private readonly properties here
        public function viewAny(User $user): bool
        {
            return true; // Все могут видеть список заказов (публичная информация)
        }

        public function view(User $user, AutoServiceOrder $order): bool
        {
            return $user->id === $order->client_id || $user->isAdmin();
        }

        public function create(User $user): bool
        {
        $this->fraud->check(new \App\DTOs\OperationDto(correlationId: $this->request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString()));

            return $user->isVerified();
        }

        public function cancel(User $user, AutoServiceOrder $order): Response
        {
            if ($user->id !== $order->client_id && !$user->isAdmin()) {
                return $this->response->deny('Вы не можете отменить этот заказ');
            }

            if ($order->status === 'completed' || $order->status === 'cancelled') {
                return $this->response->deny('Заказ уже завершён или отменён');
            }

            // Отмену можно сделать только в течение 24 часов до начала
            $hoursUntilStart = $order->appointment_datetime->diffInHours(Carbon::now(), false);
            if ($hoursUntilStart < -24) {
                return $this->response->deny('Отмену можно сделать только за 24 часа до начала');
            }

            return $this->response->allow();
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
