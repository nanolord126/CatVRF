<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SubscriptionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
