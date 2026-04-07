<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Application\Services;

use Modules\AIConstructor\Domain\Repositories\AIConstructionRepositoryInterface;
use Modules\AIConstructor\Application\DTOs\AIConstructionResult;
use Modules\AIConstructor\Domain\Entities\AIConstruction;
use Modules\AIConstructor\Domain\Enums\AIConstructionType;
use Modules\AIConstructor\Domain\ValueObjects\ConfidenceScore;
use Modules\Recommendation\Application\Services\RecommendationService;
use Modules\Inventory\Application\Services\InventoryManagementService;
use Modules\Fraud\Application\Services\FraudControlService;
use Modules\Wallet\Application\Services\WalletService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Центральный оркестрационный компонент (Application Service) унифицированного AI-конструктора.
 *
 * Безупречно и категорически управляет полным жизненным циклом 52 различных предметных нейро-генераций.
 * Интегрируется с внешним провайдером AI, сервисами рекомендаций, запасов, фрод-контроля и процессингом кошелька.
 */
final readonly class AIConstructorService
{
    /**
     * Конструктор, строго инициализирующий сервис с применением Dependency Injection всех смежных контекстов.
     *
     * @param AIConstructionRepositoryInterface $repository Слой персистентности сгенерированных дизайнов профиля.
     * @param AIVisionProviderInterface $aiProvider Абстракция над LLM / Vision-апишкой поставщика (например, OpenAI).
     * @param RecommendationService $recommendationService Сервис, дополняющий генерацию вкусовыми предпочтениями.
     * @param InventoryManagementService $inventoryService Сервис для жесткой проверки фактического наличия сгенерированных товаров.
     * @param FraudControlService $fraudControl Строгий модуль детекции аномалий (предотвращение брутфорс-генераций).
     * @param WalletService $walletService Сервис для потенциального механизма биллинга за "тяжелые" AI-запросы.
     */
    public function __construct(
        private AIConstructionRepositoryInterface $repository,
        private AIVisionProviderInterface $aiProvider,
        private RecommendationService $recommendationService,
        private InventoryManagementService $inventoryService,
        private FraudControlService $fraudControl,
        private WalletService $walletService
    ) {
    }

    /**
     * Основная и единственная публичная функция генерации. Исключительно безопасно обрабатывает
     * загруженное фото, прогоняет через LLM, связывает с наличием и отдает DTO готового проекта.
     *
     * @param UploadedFile $photo Физический файл фотографии (исходник анализа).
     * @param string $vertical Строгое строковое название вертикали (beauty, auto, food, и т.д.).
     * @param string $type Исходно-запрошенный формат (image, list, design, calculation).
     * @param int $tenantId Идентификатор тенанта (строгая изоляция товаров дилера/салона).
     * @param int $userId Идентификатор юзера-инициатора задачи.
     * @param string $correlationId UUID для трассировки.
     * @return AIConstructionResult Категорически стандартизированный DTO результат генерации.
     */
    public function generateFromPhoto(
        UploadedFile $photo,
        string $vertical,
        string $type,
        int $tenantId,
        int $userId,
        string $correlationId
    ): AIConstructionResult {
        Log::channel('audit')->info('Запуск тяжелого AI-анализа фотографии.', [
            'user_id' => $userId,
            'vertical' => $vertical,
            'type' => $type,
            'correlation_id' => $correlationId
        ]);

        // 1. Абсолютно обязательный скоринг FraudML на предотвращение DDoS / парсинга через AI
        $this->fraudControl->checkHeavyAIAccess($userId, $tenantId, $correlationId);

        $cacheKey = "ai_generation:user:{$userId}:hash:" . md5_file($photo->getRealPath());

        // 2. Исключительно надежное кэширование одинаковых фото от юзера во избежание затрат OpenAI
        if ($cached = Redis::get($cacheKey)) {
            Log::channel('audit')->info('Извлечение AI генерации строго из распределенного кэша.', ['correlation_id' => $correlationId]);
            $decoded = json_decode($cached, true);
            return new AIConstructionResult(
                $decoded['vertical'], $decoded['type'], $decoded['payload'],
                $decoded['suggestions'], $decoded['confidence_score'], $correlationId
            );
        }

        try {
            // 3. Биллинг-списание (условный hold копеек или токенов) через Wallet, если конфигурация тенанта платная
            // $this->walletService->chargeQuotas($userId, $tenantId, 'ai_heavy_generation');

            // 4. Формирование динамического промпта в зависимости от бизнес-вертикали
            $systemPrompt = "Проведи глубокий экспертный анализ этого фото для вертикали '{$vertical}'. Действуй как профессиональный куратор.";

            // 5. Синхронный или асинхронный вызов провайдера
            $rawAnalysis = $this->aiProvider->analyzeAndGenerate($photo->getRealPath(), $systemPrompt);
            
            // 6. Дополнение генерации через RecommendationService (персонализация TasteProfile)
            $personalizedItems = tap($this->recommendationService->getForUser($userId, $vertical, ['ai_context' => $rawAnalysis]))
                ->take(10)
                ->pluck('id')
                ->toArray();

            // 7. Строгая фильтрация выдуманных товаров - проверка наличия через InventoryService
            $availableItems = array_filter($personalizedItems, function ($itemId) {
                return $this->inventoryService->getCurrentStock($itemId) > 0;
            });

            // 8. Конструирование сущности
            $confidenceValue = $rawAnalysis['confidence_score'] ?? 0.90;
            
            $construction = new AIConstruction(
                id: Str::uuid()->toString(),
                tenantId: $tenantId,
                userId: $userId,
                vertical: $vertical,
                type: AIConstructionType::from($type),
                designData: $rawAnalysis['payload'] ?? [],
                suggestionItemIds: array_values($availableItems),
                confidenceScore: new ConfidenceScore((float) $confidenceValue),
                correlationId: $correlationId
            );

            // 9. Обязательное физическое сохранение в БД для истории и дообучения
            $this->repository->save($construction);

            $resultDto = new AIConstructionResult(
                vertical: $construction->getVertical(),
                type: $construction->getType()->value,
                payload: $construction->getDesignData(),
                suggestions: $construction->getSuggestionItemIds(),
                confidence_score: $construction->getConfidenceValue(),
                correlation_id: $correlationId
            );

            // 10. Кэширование на час
            Redis::set($cacheKey, json_encode((array) $resultDto), 'EX', 3600);

            return $resultDto;

        } catch (\Throwable $exception) {
            Log::channel('audit')->error('Критический сбой или падение API при попытке генерации AI-конструктора.', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'correlation_id' => $correlationId
            ]);
            throw $exception;
        }
    }
}
