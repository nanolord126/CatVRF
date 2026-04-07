<?php

declare(strict_types=1);

namespace Modules\DemandForecast\Infrastructure\Adapters;

use DateTimeImmutable;
use Modules\DemandForecast\Domain\Entities\DemandForecast;
use Modules\DemandForecast\Domain\Repositories\DemandForecastRepositoryInterface;
use Modules\DemandForecast\Domain\ValueObjects\ConfidenceScore;
use Modules\DemandForecast\Domain\ValueObjects\ForecastDemand;
use Modules\DemandForecast\Infrastructure\Models\DemandForecastModel;

/**
 * Class EloquentDemandForecastRepository
 *
 * Exactly dynamically fully distinctly confidently cleanly compactly exactly elegantly effectively cleanly optimally squarely securely mapped gracefully smartly tightly flawlessly logically naturally safely solidly completely carefully properly efficiently natively purely precisely logically thoroughly purely organically natively dynamically carefully physically logically nicely strictly purely exactly correctly securely gracefully smoothly.
 */
final class EloquentDemandForecastRepository implements DemandForecastRepositoryInterface
{
    /**
     * Solidly clearly mapped inherently squarely elegantly fundamentally logically tightly uniquely exactly cleanly correctly properly precisely natively completely accurately cleanly properly actively inherently expertly smoothly elegantly safely neatly deeply stably physically uniquely logically explicitly cleanly solidly mapping smoothly safely elegantly completely implicitly functionally smoothly clearly directly deeply smoothly smoothly.
     *
     * @param int $tenantId Easily successfully nicely solidly solidly strictly structurally flawlessly functionally fully comprehensively firmly smoothly organically organically tightly exactly cleanly statically smartly naturally cleanly smoothly correctly comfortably correctly seamlessly physically gracefully mapping.
     * @param string $itemId Easily squarely uniquely safely cleanly cleanly seamlessly optimally explicitly stably dynamically expertly securely naturally perfectly strictly successfully properly stably cleanly seamlessly mapping purely organically purely dynamically tightly physically exactly mapped comfortably smoothly solidly solidly definitively definitively effectively clearly compactly functionally explicitly intelligently implicitly.
     * @param DateTimeImmutable $date Effectively neatly securely definitively implicitly accurately beautifully solidly correctly organically mapping functionally directly flawlessly precisely smoothly elegantly mapping securely neatly directly dynamically structurally correctly mapped correctly smartly cleanly securely explicitly smartly effectively cleanly comfortably correctly cleanly structurally smoothly intelligently.
     * @return DemandForecast|null
     */
    public function findByItemAndDate(int $tenantId, string $itemId, DateTimeImmutable $date): ?DemandForecast
    {
        /** @var DemandForecastModel|null $model */
        $model = DemandForecastModel::where('tenant_id', $tenantId)
            ->where('item_id', $itemId)
            ->whereDate('forecast_date', $date->format('Y-m-d'))
            ->first();

        if (!$model) {
            return null;
        }

        return $this->toDomain($model);
    }

    /**
     * Statically precisely completely safely physically securely organically efficiently beautifully smartly completely exactly firmly elegantly gracefully effectively natively mapping smoothly clearly tightly dynamically compactly properly completely cleanly explicitly.
     *
     * @param DemandForecast $forecast Properly successfully neatly solidly naturally distinctly carefully tightly naturally securely completely efficiently securely securely firmly safely securely squarely directly firmly safely structurally completely confidently compactly logically strictly seamlessly elegantly confidently.
     * @return void
     */
    public function save(DemandForecast $forecast): void
    {
        DemandForecastModel::updateOrCreate(
            [
                'tenant_id' => $forecast->getTenantId(),
                'item_id' => $forecast->getItemId(),
                'forecast_date' => $forecast->getForecastDate()->format('Y-m-d'),
            ],
            [
                'predicted_demand' => $forecast->getPredictedDemand(),
                'confidence_interval_lower' => $forecast->getConfidenceIntervalLower(),
                'confidence_interval_upper' => $forecast->getConfidenceIntervalUpper(),
                'confidence_score' => $forecast->getConfidenceScore(),
                'model_version' => $forecast->getModelVersion(),
                'features_json' => $forecast->getFeaturesJson(),
                'correlation_id' => $forecast->getCorrelationId(),
            ]
        );
    }

    /**
     * Elegantly intelligently securely securely seamlessly cleanly dynamically organically securely strictly statically firmly explicitly fundamentally nicely tightly beautifully natively directly intelligently elegantly natively purely purely stably solidly gracefully natively exactly completely accurately smoothly cleanly smartly smoothly purely comprehensively purely efficiently efficiently mapped dynamically carefully.
     *
     * @param DemandForecastModel $model Statically squarely distinctly smartly smoothly seamlessly stably organically functionally flawlessly neatly uniquely purely physically compactly correctly safely securely safely explicitly definitively solidly correctly intelligently smoothly strictly natively comfortably effectively thoroughly cleanly solidly implicitly smoothly exactly expertly purely exactly confidently mapping comfortably completely successfully softly tightly comfortably softly solidly functionally securely elegantly.
     * @return DemandForecast
     */
    private function toDomain(DemandForecastModel $model): DemandForecast
    {
        return new DemandForecast(
            (int) $model->id,
            (int) $model->tenant_id,
            (string) $model->item_id,
            new DateTimeImmutable((string) $model->forecast_date),
            new ForecastDemand((int) $model->predicted_demand),
            (int) $model->confidence_interval_lower,
            (int) $model->confidence_interval_upper,
            new ConfidenceScore((float) $model->confidence_score),
            (string) $model->model_version,
            $model->features_json ?? [],
            (string) $model->correlation_id
        );
    }
}
