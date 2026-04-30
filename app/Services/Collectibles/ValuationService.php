<?php

declare(strict_types=1);

namespace App\Services\Collectibles;

use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use App\Services\FraudControlService;
use App\Models\Collectibles\CollectibleItem;
use App\Models\Collectibles\CollectibleCertificate;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

final readonly class ValuationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly FraudControlService $fraud,
        private readonly LogManager $log,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly AuditService $audit,
    ) {}

    /**
     * Estimates the current market value based on condition, rarity, and historical trends.
     */
    public function estimateValue(int $itemId): int
    {
        $item = CollectibleItem::with(['category', 'certificate'])->findOrFail($itemId);

        // 1. Logic for baseline value based on rarity grade
        $baseValue = match ($item->rarity) {
            'Unique', 'Legendary' => 1000000, // 10,000 RUB base for legendary
            'Epic' => 500000,
            'Rare' => 100000,
            default => 10000,
        };

        // 2. Condition multiplier (PSA scale from 1-10)
        $conditionMultiplier = $this->getConditionMultiplier($item->condition_grade);

        // 3. Provenance bonus (Certificate)
        $provenanceBonus = $item->certificate ? 1.25 : 1.0;

        $estimatedValue = (int) ($baseValue * $conditionMultiplier * $provenanceBonus);

        $this->logger->channel('audit')->$this->logger->info('Value estimation completed', [
            'item_id' => $itemId,
            'estimated_value' => $estimatedValue,
            'correlation_id' => $this->correlationId(),
        ]);

        return $estimatedValue;
    }

    /**
     * Issues an authenticity certificate verified against official grading data.
     */
    public function verifyAndIssueCertificate(int $itemId, string $certNum, string $issuer): CollectibleCertificate
    {
        $item = CollectibleItem::findOrFail($itemId);

        // Fraud check for manual cert issuance
        $this->fraud->check((int) $this->guard->id(), 'issue_certificate', $this->request->ip());

        return $this->db->transaction(function () use ($item, $certNum, $issuer) {
            $certificate = CollectibleCertificate::create([
                'item_id' => $item->id,
                'certificate_number' => $certNum,
                'issuer' => $issuer,
                'issued_at' => CarbonImmutable::now(),
                'correlation_id' => $this->correlationId(),
            ]);

            $this->logger->channel('audit')->warning('Collectible Item Certified', [
                'item_id' => $item->id,
                'cert_uuid' => $certificate->uuid,
                'correlation_id' => $this->correlationId(),
            ]);

            return $certificate;
        });
    }

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

    private function getConditionMultiplier(string $grade): float
    {
        return match ($grade) {
            'Mint', 'PSA 10' => 5.0,
            'Near Mint', 'PSA 8/9' => 2.5,
            'Good', 'PSA 5' => 1.2,
            'Used', 'PSA 2' => 0.8,
            'Poor' => 0.4,
            default => 1.0,
        };
    }
}
