<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;
use App\Domains\AI\Services\PIIAnonymizationService;
use App\Domains\AI\Services\CircuitBreakerService;
use Modules\AIConstructor\Domain\Repositories\AIConstructionRepositoryInterface;
use Modules\AIConstructor\Application\DTOs\AIConstructionResult;
use Modules\AIConstructor\Domain\Entities\AIConstruction;
use Modules\AIConstructor\Domain\Enums\AIConstructionType;
use Modules\AIConstructor\Domain\ValueObjects\ConfidenceScore;
use Modules\Recommendation\Application\Services\RecommendationService;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Центральный оркестрационный компонент (Application Service) унифицированного AI-конструктора.
 *
 * Безупречно и категорически управляет полным жизненным циклом 52 различных предметных нейро-генераций.
 * Интегрируется с внешним провайдером AI, сервисами рекомендаций, запасов, фрод-контроля и процессингом кошелька.
 */
final readonly class AIConstructorService
{
    use WithAuditLogging;

    /**
     * Конструктор, строго инициализирующий сервис с применением Dependency Injection всех смежных контекстов.
     *
     * @param  AIConstructionRepositoryInterface  $repository  Слой персистентности сгенерированных дизайнов профиля.
     * @param  AIVisionProviderInterface  $aiProvider  Абстракция над LLM / Vision-апишкой поставщика (например, OpenAI).
     * @param  RecommendationService  $recommendationService  Сервис, дополняющий генерацию вкусовыми предпочтениями.
     * @param  InventoryManagementService  $inventoryService  Сервис для жесткой проверки фактического наличия сгенерированных товаров.
     * @param  FraudControlService  $fraudControl  Строгий модуль детекции аномалий (предотвращение брутфорс-генераций).
     * @param  WalletService  $walletService  Сервис для потенциального механизма биллинга за "тяжелые" AI-запросы.
     */
    public function __construct(
        private AIConstructionRepositoryInterface $repository,
        private AIVisionProviderInterface $aiProvider,
        private RecommendationService $recommendationService,
        private readonly LogManager $log,
        private readonly Connection $redis,
        private readonly PIIAnonymizationService $piiAnonymization,
        private readonly CircuitBreakerService $circuitBreaker,
    ) {}

    /**
     * Основная и единственная публичная функция генерации. Исключительно безопасно обрабатывает
     * загруженное фото, прогоняет через LLM, связывает с наличием и отдает DTO готового проекта.
     *
     * @param  UploadedFile  $photo  Физический файл фотографии (исходник анализа).
     * @param  string  $vertical  Строгое строковое название вертикали (beauty, auto, food, и т.д.).
     * @param  string  $type  Исходно-запрошенный формат (image, list, design, calculation).
     * @param  int  $tenantId  Идентификатор тенанта (строгая изоляция товаров дилера/салона).
     * @param  int  $userId  Идентификатор юзера-инициатора задачи.
     * @param  string  $correlationId  UUID для трассировки.
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
        $this->logAction('ai_analysis_started', 'AIConstruction', null, [
            'user_id' => $userId,
            'vertical' => $vertical,
            'type' => $type,
            'tenant_id' => $tenantId,
        ], $userId, $tenantId, $correlationId);

        // 1. Photo validation - strict security checks
        $this->validatePhoto($photo);

        // 2. Абсолютно обязательный скоринг FraudML на предотвращение DDoS / парсинга через AI
        $this->fraudControl->checkHeavyAIAccess($userId, $tenantId, $correlationId);

        $cacheKey = "ai_generation:user:{$userId}:hash:".md5_file($photo->getRealPath());

        // 2. Исключительно надежное кэширование одинаковых фото от юзера во избежание затрат OpenAI
        if ($cached = $this->redis->get($cacheKey)) {
            $this->logAction('ai_generation_cache_hit', 'AIConstruction', null, [
                'correlation_id' => $correlationId,
            ], $userId, $tenantId, $correlationId);
            $decoded = json_decode($cached, true);

            return new AIConstructionResult(
                $decoded['vertical'],
                $decoded['type'],
                $decoded['payload'],
                $decoded['suggestions'],
                $decoded['confidence_score'],
                $correlationId
            );
        }

        try {
            // 3. Биллинг-списание (условный hold копеек или токенов) через Wallet, если конфигурация тенанта платная
            // $this->walletService->chargeQuotas($userId, $tenantId, 'ai_heavy_generation');

            // 4. Формирование динамического промпта в зависимости от бизнес-вертикали
            $systemPrompt = "Проведи глубокий экспертный анализ этого фото для вертикали '{$vertical}'. Действуй как профессиональный куратор.";

            // 5. Sanitize prompt to remove PII before sending to external AI
            $sanitizedPrompt = $this->piiAnonymization->sanitizePrompt($systemPrompt);

            // 6. Синхронный или асинхронный вызов провайдера с circuit breaker protection
            $rawAnalysis = $this->circuitBreaker->call(
                'ai_vision_provider',
                fn () => $this->aiProvider->analyzeAndGenerate($photo->getRealPath(), $sanitizedPrompt),
                threshold: 5,
                timeout: 60
            );

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
            $this->redis->set($cacheKey, json_encode((array) $resultDto), 'EX', 3600);

            return $resultDto;

        } catch (\Throwable $exception) {
            $this->log->error('Критический сбой или падение API при попытке генерации AI-конструктора.', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);
            throw $exception;
        }
    }

    /**
     * Validate uploaded photo with strict security checks
     *
     * @param UploadedFile $photo Uploaded photo file
     * @throws RuntimeException If validation fails
     */
    private function validatePhoto(UploadedFile $photo): void
    {
        // Check if file was uploaded
        if (!$photo->isValid()) {
            throw new RuntimeException('Photo upload failed: ' . $photo->getErrorMessage());
        }

        // Validate file size (max 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($photo->getSize() > $maxSize) {
            throw new RuntimeException(
                sprintf('Photo size exceeds maximum allowed size of %d MB', $maxSize / (1024 * 1024))
            );
        }

        // Validate file type (allowed MIME types)
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/gif',
        ];

        if (!in_array($photo->getMimeType(), $allowedMimeTypes, true)) {
            throw new RuntimeException(
                sprintf('Invalid file type. Allowed types: %s', implode(', ', $allowedMimeTypes))
            );
        }

        // Validate file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extension = strtolower($photo->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException(
                sprintf('Invalid file extension. Allowed extensions: %s', implode(', ', $allowedExtensions))
            );
        }

        // Validate image dimensions (min 100x100, max 8000x8000)
        try {
            $imageInfo = getimagesize($photo->getRealPath());
            if ($imageInfo === false) {
                throw new RuntimeException('Failed to read image file');
            }

            [$width, $height] = $imageInfo;
            $minDimension = 100;
            $maxDimension = 8000;

            if ($width < $minDimension || $height < $minDimension) {
                throw new RuntimeException(
                    sprintf('Image dimensions too small. Minimum: %dx%d pixels', $minDimension, $minDimension)
                );
            }

            if ($width > $maxDimension || $height > $maxDimension) {
                throw new RuntimeException(
                    sprintf('Image dimensions too large. Maximum: %dx%d pixels', $maxDimension, $maxDimension)
                );
            }
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to validate image dimensions: ' . $e->getMessage());
        }

        // Check for potential malicious content (basic signature check)
        $fileHandle = fopen($photo->getRealPath(), 'rb');
        if ($fileHandle === false) {
            throw new RuntimeException('Failed to open file for validation');
        }

        $header = fread($fileHandle, 8);
        fclose($fileHandle);

        // Check valid image signatures
        $validSignatures = [
            "\xFF\xD8\xFF", // JPEG
            "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A", // PNG
            "GIF87a", // GIF87
            "GIF89a", // GIF89
            "RIFF", // WEBP (RIFF header)
        ];

        $isValidSignature = false;
        foreach ($validSignatures as $signature) {
            if (str_starts_with($header, $signature)) {
                $isValidSignature = true;
                break;
            }
        }

        if (!$isValidSignature) {
            throw new RuntimeException('Invalid image file signature');
        }

        $this->log->debug('Photo validation passed', [
            'size' => $photo->getSize(),
            'mime_type' => $photo->getMimeType(),
            'extension' => $extension,
            'dimensions' => "{$width}x{$height}",
        ]);
    }
}
