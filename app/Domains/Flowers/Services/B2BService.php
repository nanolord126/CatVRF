<?php

declare(strict_types=1);


namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\B2BFlowerStorefront;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * B2BFlowerService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BFlowerService
{
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
