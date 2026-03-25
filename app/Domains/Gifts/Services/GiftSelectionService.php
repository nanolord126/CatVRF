declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Domains\Gifts\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

final readonly /**
 * GiftSelectionService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GiftSelectionService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function __construct(
        private FraudControlService $fraudControlService
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function recommendGift(array $criteria, string $correlationId): array
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
        $this->log->channel('audit')->info("ПОДБОР ПОДАРКА", ["correlation_id" => $correlationId]);
        
        
        return [
            "recommended_ids" => [1, 2, 3]
        ];
    }
}
