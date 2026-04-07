<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Infrastructure\Adapters;

use Modules\AIConstructor\Domain\Repositories\AIConstructionRepositoryInterface;
use Modules\AIConstructor\Domain\Entities\AIConstruction;
use Modules\AIConstructor\Domain\Enums\AIConstructionType;
use Modules\AIConstructor\Domain\ValueObjects\ConfidenceScore;
use Modules\AIConstructor\Infrastructure\Models\AIConstructionModel;
use Illuminate\Support\Collection;
use Carbon\CarbonImmutable;

/**
 * Безупречный инфраструктурный Eloquent-адаптер интерфейса репозитория AI-конструктора.
 *
 * Исключительно отвечает за конвертацию обогащенных доменных сущностей с нейросетевыми результатами 
 * в физические записи базы данных. Строго реализует инверсию зависимостей (IoC).
 */
final readonly class EloquentAIConstructionRepository implements AIConstructionRepositoryInterface
{
    /**
     * Абсолютно надежно сохраняет свежесгенерированный проект нейронной сети в Postgres.
     *
     * @param AIConstruction $construction Полноценная, валидная доменная сущность конструкции AI.
     * @return void
     */
    public function save(AIConstruction $construction): void
    {
        AIConstructionModel::updateOrCreate(
            ['uuid' => $construction->getId()],
            [
                'user_id' => $construction->getUserId(),
                'tenant_id' => $construction->getTenantId(),
                'vertical' => $construction->getVertical(),
                'type' => $construction->getType()->value,
                'design_data' => $construction->getDesignData(),
                'suggestion_item_ids' => $construction->getSuggestionItemIds(),
                'confidence_score' => $construction->getConfidenceValue(),
                'correlation_id' => $construction->getCorrelationId()
            ]
        );
    }

    /**
     * Категорически безвозвратно извлекает список дизайнов пользователя с обязательной тенант-изоляцией.
     *
     * @param int $userId ID пользователя.
     * @param int $tenantId ID тенанта.
     * @param string|null $vertical Сортировка по вертикали.
     * @param int $limit Строгий лимит.
     * @return Collection|AIConstruction[] Коллекция доменных объектов.
     */
    public function getByUserIdAndTenant(int $userId, int $tenantId, ?string $vertical = null, int $limit = 10): Collection
    {
        $query = AIConstructionModel::where('user_id', $userId)->where('tenant_id', $tenantId);

        if ($vertical !== null) {
            $query->where('vertical', $vertical);
        }

        $models = $query->orderByDesc('created_at')->limit($limit)->get();

        return $models->map(fn (AIConstructionModel $model) => $this->mapToDomain($model));
    }

    /**
     * Внутренний хелпер для строгой трансформации ORM-модели обратно в защищенный Aggregate Root.
     */
    private function mapToDomain(AIConstructionModel $model): AIConstruction
    {
        return new AIConstruction(
            id: $model->uuid,
            tenantId: (int) $model->tenant_id,
            userId: (int) $model->user_id,
            vertical: $model->vertical,
            type: AIConstructionType::from($model->type),
            designData: is_array($model->design_data) ? $model->design_data : [],
            suggestionItemIds: is_array($model->suggestion_item_ids) ? $model->suggestion_item_ids : [],
            confidenceScore: new ConfidenceScore((float) $model->confidence_score),
            correlationId: $model->correlation_id ?? '',
            createdAt: $model->created_at ? CarbonImmutable::parse($model->created_at) : null
        );
    }
}
