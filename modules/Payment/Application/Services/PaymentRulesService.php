<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Payment\Domain\Entities\PaymentRule;
use Modules\Payment\Domain\Repositories\PaymentRuleRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;

/**
 * Payment Rules Service for ФЗ-161 compliance.
 * 
 * Manages versioned payment system rules as required by federal law.
 * Provides rule validation, versioning, and export capabilities for regulatory audits.
 */
final readonly class PaymentRulesService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_TAG = 'payment_rules';

    public function __construct(
        private PaymentRuleRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Create a new payment rule.
     */
    public function createRule(
        string $code,
        string $name,
        string $description,
        string $category,
        array $ruleData,
        string $createdBy,
    ): PaymentRule {
        $rule = PaymentRule::create(
            code: $code,
            name: $name,
            description: $description,
            category: $category,
            ruleData: $ruleData,
            createdBy: $createdBy,
        );

        $this->repository->save($rule);
        $this->invalidateCache();

        $this->logger->info('Payment rule created', [
            'uuid' => $rule->uuid,
            'code' => $code,
            'version' => $rule->version,
            'created_by' => $createdBy,
        ]);

        return $rule;
    }

    /**
     * Update an existing rule by creating a new version.
     */
    public function updateRule(
        string $ruleUuid,
        string $name,
        string $description,
        array $ruleData,
        string $updatedBy,
    ): PaymentRule {
        $currentRule = $this->repository->findByUuid($ruleUuid);
        
        if (! $currentRule) {
            throw new \InvalidArgumentException("Rule not found: {$ruleUuid}");
        }

        // Deactivate current version
        $deactivatedRule = $currentRule->deactivate();
        $this->repository->save($deactivatedRule);

        // Create new version
        $newRule = PaymentRule::create(
            code: $currentRule->code,
            name: $name,
            description: $description,
            category: $currentRule->category,
            ruleData: $ruleData,
            createdBy: $updatedBy,
            previousVersionUuid: $currentRule->uuid,
        );

        $this->repository->save($newRule);
        $this->invalidateCache();

        $this->logger->info('Payment rule updated (new version)', [
            'new_uuid' => $newRule->uuid,
            'code' => $newRule->code,
            'new_version' => $newRule->version,
            'previous_version' => $currentRule->version,
            'updated_by' => $updatedBy,
        ]);

        return $newRule;
    }

    /**
     * Get active rule by code.
     */
    public function getActiveRule(string $code): ?PaymentRule
    {
        $cacheKey = "payment_rule:active:{$code}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($code) {
            $rules = $this->repository->findByCode($code);
            
            foreach ($rules as $rule) {
                if ($rule->isEffectiveAt(CarbonImmutable::now())) {
                    return $rule;
                }
            }

            return null;
        });
    }

    /**
     * Get all active rules by category.
     */
    public function getActiveRulesByCategory(string $category): array
    {
        $cacheKey = "payment_rules:category:{$category}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($category) {
            $rules = $this->repository->findByCategory($category);
            $now = CarbonImmutable::now();

            return array_filter($rules, fn($rule) => $rule->isEffectiveAt($now));
        });
    }

    /**
     * Validate payment against ФЗ-161 rules.
     */
    public function validateUnder161(array $paymentData): array
    {
        $violations = [];
        $warnings = [];

        // Check transaction limits
        $limitRule = $this->getActiveRule('transaction_limits');
        if ($limitRule) {
            $maxAmount = $limitRule->getParameter('max_amount_kopecks', 15_000_000_00); // 15M RUB default
            $amount = $paymentData['amount_kopecks'] ?? 0;

            if ($amount > $maxAmount) {
                $violations[] = [
                    'rule_code' => 'transaction_limits',
                    'message' => "Transaction amount exceeds maximum limit",
                    'max_allowed' => $maxAmount,
                    'actual' => $amount,
                ];
            }
        }

        // Check fraud detection requirements
        $fraudRule = $this->getActiveRule('fraud_detection');
        if ($fraudRule) {
            $requiresRealTimeCheck = $fraudRule->getParameter('real_time_check', true);
            
            if ($requiresRealTimeCheck && !isset($paymentData['fraud_check_passed'])) {
                $warnings[] = [
                    'rule_code' => 'fraud_detection',
                    'message' => "Real-time fraud check required before payment",
                ];
            }
        }

        // Check settlement rules (ФЗ-161: electronic payment means only to bank accounts)
        $settlementRule = $this->getActiveRule('settlement');
        if ($settlementRule) {
            $requiresBankAccount = $settlementRule->getParameter('bank_account_only', true);
            
            if ($requiresBankAccount && isset($paymentData['settlement_method'])) {
                if ($paymentData['settlement_method'] !== 'bank_account') {
                    $violations[] = [
                        'rule_code' => 'settlement',
                        'message' => "Settlement must be to bank account only (ФЗ-161 requirement)",
                        'required' => 'bank_account',
                        'actual' => $paymentData['settlement_method'],
                    ];
                }
            }
        }

        return [
            'is_compliant' => empty($violations),
            'violations' => $violations,
            'warnings' => $warnings,
        ];
    }

    /**
     * Export rules for regulatory audit (ФЗ-161 requirement).
     */
    public function exportRulesForAudit(\DateTime $from, \DateTime $to): array
    {
        $rules = $this->repository->findEffectiveBetween($from, $to);

        $export = [
            'exported_at' => CarbonImmutable::now()->toDateTimeString(),
            'period' => [
                'from' => $from->format('Y-m-d H:i:s'),
                'to' => $to->format('Y-m-d H:i:s'),
            ],
            'total_rules' => count($rules),
            'rules' => array_map(fn($rule) => $rule->toArray(), $rules),
        ];

        $this->logger->info('Payment rules exported for audit', [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'total_rules' => count($rules),
        ]);

        return $export;
    }

    /**
     * Get rule history by code.
     */
    public function getRuleHistory(string $code): array
    {
        $rules = $this->repository->findByCode($code);

        return array_map(fn($rule) => [
            'uuid' => $rule->uuid,
            'version' => $rule->version,
            'name' => $rule->name,
            'is_active' => $rule->isActive,
            'effective_from' => $rule->effectiveFrom->toDateTimeString(),
            'effective_to' => $rule->effectiveTo?->toDateTimeString(),
            'created_at' => $rule->createdAt->toDateTimeString(),
        ], $rules);
    }

    private function invalidateCache(): void
    {
        Cache::tags([self::CACHE_TAG])->flush();
    }
}
