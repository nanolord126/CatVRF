<?php

declare(strict_types=1);

namespace App\Domains\Search\Services;

use App\Domains\Search\Models\SearchCriteria;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * SearchVerticalIntegrationService — сервис интеграции вертикалей с поисковой системой.
 *
 * Layer 3: Services — CatVRF 9-layer architecture.
 *
 * Управляет индексацией и поиском по всем вертикалям с разделением
 * критериев на публичные и вертикально-ограниченные.
 *
 * Публичные критерии (type = 'public'):
 * - Индексируются для всех вертикалей
 * - Пример: цена, рейтинг, расстояние, доступность
 *
 * Вертикально-ограниченные критерии (type = 'vertical_restricted'):
 * - Доступны только для конкретной вертикали
 * - Пример: BMW (только Auto), маникюр (только Beauty)
 * - Жёсткая фильтрация: у маникюра НЕ появится фильтр BMW
 *
 * @package App\Domains\Search\Services
 * @version 2026.1
 */
final readonly class SearchVerticalIntegrationService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Получить критерии для конкретной вертикали.
     *
     * @param string $vertical Вертикаль (hotels, beauty, auto, medical и т.д.)
     * @param int $tenantId ID tenant
     * @param string $correlationId ID корреляции
     *
     * @return array<int, array<string, mixed>> Список критериев
     */
    public function getCriteriaForVertical(string $vertical, int $tenantId, string $correlationId): array
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'search_criteria_get',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $criteria = SearchCriteria::where('tenant_id', $tenantId)
            ->where(function ($query) use ($vertical) {
                $query->where('vertical', $vertical)
                    ->orWhere('type', 'public');
            })
            ->where('is_filterable', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        $this->logger->info('Search criteria retrieved for vertical', [
            'vertical' => $vertical,
            'tenant_id' => $tenantId,
            'criteria_count' => count($criteria),
            'correlation_id' => $correlationId,
        ]);

        return $criteria;
    }

    /**
     * Получить только публичные критерии (для всех вертикалей).
     *
     * @param int $tenantId ID tenant
     * @param string $correlationId ID корреляции
     *
     * @return array<int, array<string, mixed>> Список публичных критериев
     */
    public function getPublicCriteria(int $tenantId, string $correlationId): array
    {
        $criteria = SearchCriteria::where('tenant_id', $tenantId)
            ->where('type', 'public')
            ->where('is_indexed', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        $this->logger->info('Public search criteria retrieved', [
            'tenant_id' => $tenantId,
            'criteria_count' => count($criteria),
            'correlation_id' => $correlationId,
        ]);

        return $criteria;
    }

    /**
     * Получить вертикально-ограниченные критерии для конкретной вертикали.
     *
     * @param string $vertical Вертикаль
     * @param int $tenantId ID tenant
     * @param string $correlationId ID корреляции
     *
     * @return array<int, array<string, mixed>> Список критериев
     */
    public function getVerticalRestrictedCriteria(string $vertical, int $tenantId, string $correlationId): array
    {
        $criteria = SearchCriteria::where('tenant_id', $tenantId)
            ->where('vertical', $vertical)
            ->where('type', 'vertical_restricted')
            ->where('is_indexed', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        $this->logger->info('Vertical restricted search criteria retrieved', [
            'vertical' => $vertical,
            'tenant_id' => $tenantId,
            'criteria_count' => count($criteria),
            'correlation_id' => $correlationId,
        ]);

        return $criteria;
    }

    /**
     * Создать критерий для вертикали.
     *
     * @param array<string, mixed> $data Данные критерия
     * @param string $correlationId ID корреляции
     *
     * @return SearchCriteria Созданный критерий
     *
     * @throws \DomainException
     */
    public function createCriteria(array $data, string $correlationId): SearchCriteria
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'search_criteria_create',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $correlationId) {
            $existingCriteria = SearchCriteria::where('code', $data['code'])
                ->where('tenant_id', $data['tenant_id'])
                ->first();

            if ($existingCriteria !== null) {
                throw new \DomainException('Критерий с таким кодом уже существует');
            }

            $criteria = SearchCriteria::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $data['tenant_id'],
                'vertical' => $data['vertical'] ?? null,
                'code' => $data['code'],
                'name' => $data['name'],
                'name_ru' => $data['name_ru'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'public',
                'data_type' => $data['data_type'] ?? 'string',
                'is_indexed' => $data['is_indexed'] ?? true,
                'is_filterable' => $data['is_filterable'] ?? true,
                'is_required' => $data['is_required'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
                'options' => $data['options'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                action: 'search_criteria_created',
                subjectType: SearchCriteria::class,
                subjectId: $criteria->id,
                oldValues: [],
                newValues: $criteria->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Search criteria created', [
                'criteria_id' => $criteria->id,
                'code' => $criteria->code,
                'vertical' => $criteria->vertical,
                'type' => $criteria->type,
                'correlation_id' => $correlationId,
            ]);

            return $criteria;
        });
    }

    /**
     * Индексировать объект вертикали в поисковой системе.
     *
     * @param string $vertical Вертикаль
     * @param int $objectId ID объекта
     * @param array<string, mixed> $data Данные объекта с критериями
     * @param string $correlationId ID корреляции
     *
     * @return array<string, mixed> Результат индексации
     */
    public function indexObject(string $vertical, int $objectId, array $data, string $correlationId): array
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'search_object_index',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $publicCriteria = $this->getPublicCriteria($data['tenant_id'], $correlationId);
        $verticalCriteria = $this->getVerticalRestrictedCriteria($vertical, $data['tenant_id'], $correlationId);

        $searchDocument = [
            'id' => $objectId,
            'vertical' => $vertical,
            'tenant_id' => $data['tenant_id'],
            // Публичные критерии (индексируются для всех вертикалей)
            'public_criteria' => $this->extractCriteriaValues($data, $publicCriteria),
            // Вертикально-ограниченные критерии (только для этой вертикали)
            'vertical_criteria' => $this->extractCriteriaValues($data, $verticalCriteria),
            // Метаданные
            'indexed_at' => now()->toIso8601String(),
        ];

        $this->logger->info('Object indexed in search system', [
            'vertical' => $vertical,
            'object_id' => $objectId,
            'public_criteria_count' => count($searchDocument['public_criteria']),
            'vertical_criteria_count' => count($searchDocument['vertical_criteria']),
            'correlation_id' => $correlationId,
        ]);

        return [
            'vertical' => $vertical,
            'object_id' => $objectId,
            'indexed' => true,
            'search_document' => $searchDocument,
        ];
    }

    /**
     * Поиск объектов по критериям с вертикальной фильтрацией.
     *
     * @param string $vertical Вертикаль
     * @param array<string, mixed> $filters Фильтры поиска
     * @param int $tenantId ID tenant
     * @param string $correlationId ID корреляции
     *
     * @return array<int, array<string, mixed>> Результаты поиска
     */
    public function search(string $vertical, array $filters, int $tenantId, string $correlationId): array
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'search_execute',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $publicCriteria = $this->getPublicCriteria($tenantId, $correlationId);
        $verticalCriteria = $this->getVerticalRestrictedCriteria($vertical, $tenantId, $correlationId);

        // Фильтруем только валидные критерии для этой вертикали
        $validPublicFilters = $this->filterValidCriteria($filters, $publicCriteria);
        $validVerticalFilters = $this->filterValidCriteria($filters, $verticalCriteria);

        $this->logger->info('Search executed with vertical filtering', [
            'vertical' => $vertical,
            'tenant_id' => $tenantId,
            'public_filters_count' => count($validPublicFilters),
            'vertical_filters_count' => count($validVerticalFilters),
            'correlation_id' => $correlationId,
        ]);

        return [
            'vertical' => $vertical,
            'public_filters' => $validPublicFilters,
            'vertical_filters' => $validVerticalFilters,
            'results' => [], // Здесь будет логика поиска по Elasticsearch/Meilisearch
        ];
    }

    /**
     * Извлечь значения критериев из данных объекта.
     *
     * @param array<string, mixed> $data Данные объекта
     * @param array<int, array<string, mixed>> $criteria Список критериев
     *
     * @return array<string, mixed> Значения критериев
     */
    private function extractCriteriaValues(array $data, array $criteria): array
    {
        $values = [];

        foreach ($criteria as $criterion) {
            $code = $criterion['code'];
            if (isset($data[$code])) {
                $values[$code] = [
                    'value' => $data[$code],
                    'type' => $criterion['data_type'],
                    'is_indexed' => $criterion['is_indexed'],
                ];
            }
        }

        return $values;
    }

    /**
     * Отфильтровать валидные критерии для поиска.
     *
     * @param array<string, mixed> $filters Фильтры из запроса
     * @param array<int, array<string, mixed>> $criteria Доступные критерии
     *
     * @return array<string, mixed> Валидные фильтры
     */
    private function filterValidCriteria(array $filters, array $criteria): array
    {
        $validFilters = [];

        foreach ($filters as $code => $value) {
            $criterionExists = collect($criteria)->firstWhere('code', $code);
            if ($criterionExists !== null) {
                $validFilters[$code] = $value;
            }
        }

        return $validFilters;
    }
}
