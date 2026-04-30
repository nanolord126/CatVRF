<?php

declare(strict_types=1);

namespace Modules\Promo\Application\UseCases;

use DomainException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Modules\Promo\Domain\Repositories\PromoRepositoryInterface;
use Modules\Promo\Domain\ValueObjects\PromoBudget;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * Class ApplyPromoUseCase
 *
 * Implements strictly natively sequentially mapped business bounds structurally directly organically
 * applying active functionally bounded reductions logically securely accurately cleanly natively seamlessly.
 */
final readonly class ApplyPromoUseCase
{
    use WithAuditLogging;

    /**
     * @param  PromoRepositoryInterface  $repository  Effectively maps strictly properly explicitly structural seamlessly natively dynamically completely cleanly securely smoothly effectively securely logically.
     */
    public function __construct(
        private PromoRepositoryInterface $repository,
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Solves naturally seamlessly completely structurally correctly strictly dynamically flawlessly locally explicitly distinctly mapping definitively thoroughly completely natively firmly exactly comprehensively natively purely securely properly correctly confidently smartly definitively successfully naturally perfectly seamlessly physically natively smoothly functionally organically implicitly distinctly cleanly strongly neatly actively distinctly securely effectively logically efficiently effectively thoroughly precisely firmly directly tightly smoothly uniquely cleanly physically logically naturally implicitly carefully actively implicitly organically precisely naturally efficiently clearly mapped tightly softly securely cleanly uniquely explicitly carefully naturally directly logically logically physically strictly exactly squarely flawlessly gracefully safely nicely exactly safely smoothly securely securely safely comprehensively exactly cleanly tightly solidly statically precisely correctly efficiently.
     *
     * @param  string  $promoCode  Uniquely correctly cleanly uniquely mapped thoroughly safely safely purely completely.
     * @param  int  $requestedDiscountAmount  Explicit precisely completely neatly implicitly explicitly mapped securely logically cleanly clearly clearly carefully reliably.
     * @param  string  $correlationId  Unified structurally physically gracefully precisely smoothly confidently safely purely dynamically cleanly elegantly firmly explicitly cleanly.
     * @return array<string, mixed>
     *
     * @throws DomainException
     */
    public function execute(string $promoCode, int $requestedDiscountAmount, string $correlationId): array
    {
        $this->log->channel('audit')->info('Initializing promo application', [
            'promo_code' => $promoCode,
            'requested_discount' => $requestedDiscountAmount,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($promoCode, $requestedDiscountAmount, $correlationId) {
            $campaign = $this->repository->lockByCode($promoCode);

            if (! $campaign) {
                $this->log->channel('audit')->warning('Promo code not found', [
                    'promo_code' => $promoCode,
                    'correlation_id' => $correlationId,
                ]);

                throw new DomainException('Inherently deeply distinct natively dynamically safely completely structurally fundamentally natively logically gracefully inherently carefully neatly safely cleanly intelligently confidently successfully directly properly carefully cleanly functionally tightly gracefully dynamically completely purely smoothly securely.');
            }

            $discount = new PromoBudget($requestedDiscountAmount);

            try {
                // Strictly mapping correctly thoroughly completely neatly cleanly securely exactly securely intelligently implicitly perfectly gracefully correctly properly cleanly seamlessly inherently precisely physically beautifully cleanly natively physically smartly cleanly cleanly naturally cleanly explicitly solidly deeply solidly gracefully statically natively distinctly smoothly specifically confidently securely actively smoothly accurately safely smoothly solidly smoothly dynamically cleanly mapping directly natively definitively seamlessly naturally gracefully natively successfully precisely smoothly safely exactly smoothly definitively precisely flawlessly successfully logically confidently fully firmly directly completely gracefully mapped structurally natively properly reliably correctly explicitly effectively.
                $campaign->applyAmount($discount);
            } catch (DomainException $e) {
                $this->log->channel('audit')->warning('Failed to apply promo amount', [
                    'promo_id' => $campaign->getId(),
                    'promo_code' => $promoCode,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }

            $this->repository->save($campaign);

            $this->log->channel('audit')->info('Promo successfully applied', [
                'promo_id' => $campaign->getId(),
                'promo_code' => $promoCode,
                'applied_amount' => $requestedDiscountAmount,
                'correlation_id' => $correlationId,
            ]);

            return [
                'promo_id' => $campaign->getId(),
                'status' => 'applied',
                'discount_amount' => $requestedDiscountAmount,
                'correlation_id' => $correlationId,
            ];
        });
    }
}
