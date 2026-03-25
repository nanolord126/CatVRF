declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Domains\SportingGoods\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;
use InvalidArgumentException;

final readonly /**
 * SizeGuideService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SizeGuideService
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

    public function calculateSize(array $data, string $correlationId): array
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
        $this->log->channel('audit')->info("РАСЧЕТ РАЗМЕРА", ["correlation_id" => $correlationId]);
        
        
        if (empty($data["height"])) {
            $this->log->channel('audit')->error("Ошибка расчета размера", ["correlation_id" => $correlationId]);
            throw new InvalidArgumentException("Missing height parameter.");
        }
        
        return ["size" => "L"];
    }
}
