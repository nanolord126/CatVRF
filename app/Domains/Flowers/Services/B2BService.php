<?php declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BFlowerService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControl
        ) {}

        public function createStorefront(array $data, string $correlationId): B2BFlowerStorefront
        {
            return DB::transaction(function () use ($data, $correlationId) {
                $this->fraudControl->check($data, 'b2b_storefront_create');

                $storefront = B2BFlowerStorefront::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                Log::channel('audit')->info('B2B Flower storefront created', [
                    'storefront_id' => $storefront->id,
                    'correlation_id' => $correlationId,
                ]);

                return $storefront;
            });
        }
}
