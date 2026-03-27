<?php

declare(strict_types=1);


namespace App\Domains\Pharmacy\Services;

use App\Domains\Pharmacy\Models\PharmacySubscription;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * SubscriptionService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SubscriptionService
{
    public function __construct(private readonly PaymentService $payment) {}

    public function subscribe(array $data, string $correlationId): PharmacySubscription
    {
        return DB::transaction(function () use ($data, $correlationId) {
            $sub = PharmacySubscription::create(array_merge($data, ['correlation_id' => $correlationId]));
            Log::channel('audit')->info("Subscription created", ['id' => $sub->id, 'correlation_id' => $correlationId]);
            return $sub;
        });
    }
}
