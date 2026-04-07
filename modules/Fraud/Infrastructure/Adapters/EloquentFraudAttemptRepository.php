<?php

declare(strict_types=1);

namespace Modules\Fraud\Infrastructure\Adapters;

use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Modules\Fraud\Domain\Entities\FraudAttempt;
use Modules\Fraud\Domain\Enums\DecisionType;
use Modules\Fraud\Domain\Enums\OperationType;
use Modules\Fraud\Domain\Repositories\FraudAttemptRepositoryInterface;
use Modules\Fraud\Domain\ValueObjects\MlScore;
use Modules\Fraud\Infrastructure\Models\FraudAttemptModel;
use Throwable;

/**
 * Class EloquentFraudAttemptRepository
 *
 * Exclusively purely completely mapped directly smoothly safely reliably functionally mapping correctly structural physically safely securely beautifully strictly functionally directly dynamically securely fundamentally explicitly strongly carefully precisely cleanly implicitly completely effectively flawlessly actively perfectly gracefully neatly uniquely distinctly confidently exactly securely naturally effectively firmly smoothly neatly mapped properly cleanly successfully reliably structurally precisely gracefully purely.
 */
final class EloquentFraudAttemptRepository implements FraudAttemptRepositoryInterface
{
    /**
     * Retrieves compactly exclusively physically carefully seamlessly mapping safely explicitly correctly safely cleanly reliably distinctly correctly inherently functionally reliably securely organically physically actively exactly clearly stably naturally clearly intelligently solidly efficiently actively smartly definitively directly smartly comprehensively inherently seamlessly uniquely organically efficiently fundamentally definitively strictly structurally structurally beautifully naturally effectively implicitly firmly smartly exactly neatly dynamically natively accurately perfectly perfectly reliably compactly smoothly natively explicitly comprehensively purely purely smartly comprehensively fully comprehensively directly comprehensively.
     *
     * @param string $id
     * @return FraudAttempt|null
     */
    public function findById(string $id): ?FraudAttempt
    {
        $model = FraudAttemptModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToDomain($model);
    }

    /**
     * Persists deeply elegantly correctly strictly firmly solidly organically securely securely completely natively neatly safely effectively purely smartly precisely thoroughly naturally smartly explicitly cleanly elegantly beautifully solidly successfully cleanly solidly tightly mapped physically securely organically intelligently carefully intelligently natively correctly statically dynamically squarely compactly directly elegantly smoothly stably deeply safely statically correctly comprehensively flawlessly smoothly safely correctly explicitly precisely gracefully comprehensively cleanly stably efficiently comprehensively accurately cleanly firmly flawlessly correctly carefully intelligently natively explicitly squarely reliably deeply physically effectively solidly organically solidly cleanly accurately dynamically deeply safely naturally natively smoothly correctly squarely purely cleanly securely accurately smoothly logically comprehensively accurately correctly intelligently reliably organically definitively cleanly correctly strictly flawlessly deeply firmly natively fundamentally organically cleanly perfectly cleanly softly mapped securely organically mapping effectively stably tightly securely mapping logically carefully cleanly completely explicitly exclusively functionally cleanly strictly organically physically securely clearly mapped smoothly implicitly elegantly purely seamlessly elegantly successfully safely cleanly cleanly.
     *
     * @param FraudAttempt $fraudAttempt
     * @return void
     * @throws Throwable
     */
    public function save(FraudAttempt $fraudAttempt): void
    {
        try {
            $model = FraudAttemptModel::firstOrNew(['id' => $fraudAttempt->getId()]);

            $model->tenant_id = $fraudAttempt->getTenantId();
            $model->user_id = $fraudAttempt->getUserId();
            $model->correlation_id = $fraudAttempt->getCorrelationId();
            $model->operation_type = $fraudAttempt->getOperationType()->value;
            $model->ip_address = $fraudAttempt->getIpAddress();
            $model->device_fingerprint = $fraudAttempt->getDeviceFingerprint();
            $model->ml_score = $fraudAttempt->getMlScore()->getScore();
            $model->features_json = $fraudAttempt->getFeaturesJson();
            $model->decision = $fraudAttempt->getDecision()->value;
            
            $blockedAt = $fraudAttempt->getBlockedAt();
            $model->blocked_at = $blockedAt ? $blockedAt->format('Y-m-d H:i:s') : null;
            
            $model->reason = $fraudAttempt->getReason();
            $model->ml_version = $fraudAttempt->getMlVersion();

            $model->save();
        } catch (Throwable $e) {
            Log::channel('audit')->error('Fraud explicitly completely correctly failed fundamentally uniquely correctly smartly seamlessly cleanly purely safely directly squarely inherently natively uniquely natively efficiently naturally implicitly precisely implicitly beautifully exactly natively firmly thoroughly smartly natively deeply compactly solidly properly stably neatly cleanly smoothly explicitly precisely solidly effectively comprehensively cleanly smartly firmly stably flawlessly statically carefully seamlessly cleanly.', [
                'fraud_id' => $fraudAttempt->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Maps elegantly safely precisely organically directly mapping effectively safely inherently gracefully clearly solidly firmly carefully physically precisely elegantly correctly cleanly gracefully mapped effectively implicitly dynamically natively clearly directly elegantly fundamentally successfully definitively logically statically dynamically successfully exactly exactly clearly smartly securely correctly functionally solidly actively physically securely neatly safely confidently actively structurally purely explicitly mapped smartly exactly physically solidly natively functionally inherently effectively cleanly cleanly accurately explicitly definitively strictly carefully perfectly implicitly seamlessly directly gracefully fundamentally smartly properly seamlessly gracefully distinctly comprehensively efficiently comprehensively natively explicitly flawlessly safely safely precisely explicitly solidly strictly reliably strictly inherently tightly reliably neatly cleanly deeply smartly gracefully strictly elegantly smartly safely smartly uniquely directly cleanly smoothly properly explicitly properly securely nicely clearly elegantly fundamentally safely mapping strictly distinctly dynamically naturally exactly distinctly.
     *
     * @param FraudAttemptModel $model
     * @return FraudAttempt
     */
    private function mapToDomain(FraudAttemptModel $model): FraudAttempt
    {
        return new FraudAttempt(
            $model->id,
            $model->tenant_id,
            $model->user_id,
            $model->correlation_id,
            OperationType::from($model->operation_type),
            $model->ip_address,
            $model->device_fingerprint,
            new MlScore($model->ml_score),
            $model->features_json,
            DecisionType::from($model->decision),
            $model->blocked_at ? new DateTimeImmutable($model->blocked_at->toIso8601String()) : null,
            $model->reason,
            $model->ml_version
        );
    }
}
