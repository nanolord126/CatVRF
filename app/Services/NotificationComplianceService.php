<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationCampaign;
use App\Models\InternalAudience;
use App\Models\User;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

final readonly class NotificationComplianceService
{
    use WithAuditLogging;

    // Russian Advertising Law (ФЗ о рекламе) requirements
    private const MAX_PROMOTIONS_PER_WEEK = 3;
    private const MAX_PROMOTIONS_PER_MONTH = 8;
    private const MIN_HOURS_BETWEEN_PROMOTIONS = 24;
    private const REQUIRED_DISCLOSURE_FIELDS = ['advertiser_name', 'advertiser_contact'];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Check campaign compliance before sending
     */
    public function checkCampaignCompliance(NotificationCampaign $campaign, string $correlationId = ''): array
    {
        $checks = [];
        $passed = true;
        $notes = [];

        // 1. Check audience ownership (must be own clients only)
        $audienceCheck = $this->checkAudienceOwnership($campaign);
        $checks['audience_ownership'] = $audienceCheck;
        if (!$audienceCheck['passed']) {
            $passed = false;
            $notes[] = $audienceCheck['message'];
        }

        // 2. Check frequency limits (ФЗ о рекламе)
        $frequencyCheck = $this->checkFrequencyLimits($campaign);
        $checks['frequency_limits'] = $frequencyCheck;
        if (!$frequencyCheck['passed']) {
            $passed = false;
            $notes[] = $frequencyCheck['message'];
        }

        // 3. Check advertising disclosure (ФЗ о рекламе)
        $disclosureCheck = $this->checkAdvertisingDisclosure($campaign);
        $checks['advertising_disclosure'] = $disclosureCheck;
        if (!$disclosureCheck['passed']) {
            $passed = false;
            $notes[] = $disclosureCheck['message'];
        }

        // 4. Check content compliance (no prohibited content)
        $contentCheck = $this->checkContentCompliance($campaign);
        $checks['content_compliance'] = $contentCheck;
        if (!$contentCheck['passed']) {
            $passed = false;
            $notes[] = $contentCheck['message'];
        }

        // 5. Check time restrictions (no night hours for promotional messages)
        $timeCheck = $this->checkTimeRestrictions($campaign);
        $checks['time_restrictions'] = $timeCheck;
        if (!$timeCheck['passed']) {
            $passed = false;
            $notes[] = $timeCheck['message'];
        }

        // 6. Check opt-out requirement
        $optOutCheck = $this->checkOptOutRequirement($campaign);
        $checks['opt_out_requirement'] = $optOutCheck;
        if (!$optOutCheck['passed']) {
            $passed = false;
            $notes[] = $optOutCheck['message'];
        }

        $result = [
            'passed' => $passed,
            'checks' => $checks,
            'notes' => implode('; ', $notes),
            'checked_at' => now()->toIso8601String(),
        ];

        // Update campaign with compliance results
        $campaign->update([
            'compliance_checks' => $result,
            'compliance_passed' => $passed,
            'compliance_notes' => $result['notes'],
        ]);

        // Log compliance check
        $this->logAction(
            'notification_compliance_checked',
            'NotificationCampaign',
            $campaign->id,
            [
                'passed' => $passed,
                'campaign_type' => $campaign->type,
                'tenant_id' => $campaign->tenant_id,
            ],
            $correlationId
        );

        if (!$passed) {
            $this->logger->channel('compliance')->warning('Campaign failed compliance check', [
                'campaign_id' => $campaign->id,
                'tenant_id' => $campaign->tenant_id,
                'reasons' => $notes,
                'correlation_id' => $correlationId,
            ]);
        }

        return $result;
    }

    /**
     * Check audience ownership - must be own clients only
     */
    private function checkAudienceOwnership(NotificationCampaign $campaign): array
    {
        if (!$campaign->audience_id) {
            return [
                'passed' => false,
                'message' => 'Audience must be specified',
                'code' => 'NO_AUDIENCE',
            ];
        }

        $audience = InternalAudience::find($campaign->audience_id);

        if (!$audience || $audience->tenant_id !== $campaign->tenant_id) {
            return [
                'passed' => false,
                'message' => 'Audience does not belong to tenant',
                'code' => 'INVALID_AUDIENCE',
            ];
        }

        // Check if audience is restricted (master or service specific)
        if ($audience->isMasterClients() && !$audience->master_id) {
            return [
                'passed' => false,
                'message' => 'Master-specific audience requires valid master ID',
                'code' => 'INVALID_MASTER_AUDIENCE',
            ];
        }

        if ($audience->isServiceClients() && !$audience->service_id) {
            return [
                'passed' => false,
                'message' => 'Service-specific audience requires valid service ID',
                'code' => 'INVALID_SERVICE_AUDIENCE',
            ];
        }

        return [
            'passed' => true,
            'message' => 'Audience ownership validated',
            'code' => 'VALID',
        ];
    }

    /**
     * Check frequency limits per ФЗ о рекламе
     */
    private function checkFrequencyLimits(NotificationCampaign $campaign): array
    {
        if (!$campaign->isPromotional()) {
            return [
                'passed' => true,
                'message' => 'Frequency limits apply to promotional campaigns only',
                'code' => 'NOT_APPLICABLE',
            ];
        }

        $tenantId = $campaign->tenant_id;
        $now = now();

        // Check weekly limit
        $weeklyCount = NotificationCampaign::where('tenant_id', $tenantId)
            ->where('type', NotificationCampaign::TYPE_PROMOTION)
            ->where('status', NotificationCampaign::STATUS_COMPLETED)
            ->where('sent_at', '>=', $now->subWeek())
            ->count();

        if ($weeklyCount >= self::MAX_PROMOTIONS_PER_WEEK) {
            return [
                'passed' => false,
                'message' => "Weekly promotional limit exceeded ({$weeklyCount}/" . self::MAX_PROMOTIONS_PER_WEEK . ')',
                'code' => 'WEEKLY_LIMIT_EXCEEDED',
            ];
        }

        // Check monthly limit
        $monthlyCount = NotificationCampaign::where('tenant_id', $tenantId)
            ->where('type', NotificationCampaign::TYPE_PROMOTION)
            ->where('status', NotificationCampaign::STATUS_COMPLETED)
            ->where('sent_at', '>=', $now->subMonth())
            ->count();

        if ($monthlyCount >= self::MAX_PROMOTIONS_PER_MONTH) {
            return [
                'passed' => false,
                'message' => "Monthly promotional limit exceeded ({$monthlyCount}/" . self::MAX_PROMOTIONS_PER_MONTH . ')',
                'code' => 'MONTHLY_LIMIT_EXCEEDED',
            ];
        }

        // Check minimum time between promotions
        $lastPromotion = NotificationCampaign::where('tenant_id', $tenantId)
            ->where('type', NotificationCampaign::TYPE_PROMOTION)
            ->where('status', NotificationCampaign::STATUS_COMPLETED)
            ->orderBy('sent_at', 'desc')
            ->first();

        if ($lastPromotion && $lastPromotion->sent_at) {
            $hoursSinceLast = $now->diffInHours($lastPromotion->sent_at);
            if ($hoursSinceLast < self::MIN_HOURS_BETWEEN_PROMOTIONS) {
                return [
                    'passed' => false,
                    'message' => "Minimum time between promotions not met ({$hoursSinceLast}h/" . self::MIN_HOURS_BETWEEN_PROMOTIONS . 'h)',
                    'code' => 'TOO_FREQUENT',
                ];
            }
        }

        return [
            'passed' => true,
            'message' => 'Frequency limits within legal requirements',
            'code' => 'VALID',
        ];
    }

    /**
     * Check advertising disclosure per ФЗ о рекламе
     */
    private function checkAdvertisingDisclosure(NotificationCampaign $campaign): array
    {
        if (!$campaign->isPromotional()) {
            return [
                'passed' => true,
                'message' => 'Disclosure required for promotional campaigns only',
                'code' => 'NOT_APPLICABLE',
            ];
        }

        $message = $campaign->message;
        $metadata = $campaign->metadata ?? [];

        // Check if message contains required disclosure
        $hasAdvertiserName = false;
        $hasAdvertiserContact = false;

        // Check in message
        if (str_contains(strtolower($message), 'реклама') || 
            str_contains(strtolower($message), 'advertisement')) {
            $hasAdvertiserName = true;
        }

        // Check in metadata
        if (isset($metadata['advertiser_name'])) {
            $hasAdvertiserName = true;
        }

        if (isset($metadata['advertiser_contact'])) {
            $hasAdvertiserContact = true;
        }

        if (!$hasAdvertiserName || !$hasAdvertiserContact) {
            return [
                'passed' => false,
                'message' => 'Missing required advertising disclosure (advertiser name and contact)',
                'code' => 'MISSING_DISCLOSURE',
            ];
        }

        return [
            'passed' => true,
            'message' => 'Advertising disclosure present',
            'code' => 'VALID',
        ];
    }

    /**
     * Check content for prohibited content
     */
    private function checkContentCompliance(NotificationCampaign $campaign): array
    {
        $message = strtolower($campaign->message);

        // List of prohibited content (extend as needed)
        $prohibited = [
            'illegal',
            'fraud',
            'scam',
            'pyramid',
            'casino',
            'gambling',
        ];

        foreach ($prohibited as $term) {
            if (str_contains($message, $term)) {
                return [
                    'passed' => false,
                    'message' => "Message contains prohibited content: {$term}",
                    'code' => 'PROHIBITED_CONTENT',
                ];
            }
        }

        return [
            'passed' => true,
            'message' => 'Content compliance check passed',
            'code' => 'VALID',
        ];
    }

    /**
     * Check time restrictions (no night hours for promotional messages)
     */
    private function checkTimeRestrictions(NotificationCampaign $campaign): array
    {
        if (!$campaign->isPromotional()) {
            return [
                'passed' => true,
                'message' => 'Time restrictions apply to promotional campaigns only',
                'code' => 'NOT_APPLICABLE',
            ];
        }

        $scheduledTime = $campaign->scheduled_at ?? now();
        $hour = $scheduledTime->hour;

        // No promotional messages between 22:00 and 9:00
        if ($hour >= 22 || $hour < 9) {
            return [
                'passed' => false,
                'message' => 'Promotional messages cannot be sent between 22:00 and 9:00',
                'code' => 'NIGHT_HOUR_RESTRICTION',
            ];
        }

        return [
            'passed' => true,
            'message' => 'Time within allowed hours',
            'code' => 'VALID',
        ];
    }

    /**
     * Check opt-out requirement
     */
    private function checkOptOutRequirement(NotificationCampaign $campaign): array
    {
        if (!$campaign->isPromotional()) {
            return [
                'passed' => true,
                'message' => 'Opt-out required for promotional campaigns only',
                'code' => 'NOT_APPLICABLE',
            ];
        }

        $message = $campaign->message;
        $metadata = $campaign->metadata ?? [];

        // Check if message contains opt-out option
        $hasOptOut = str_contains(strtolower($message), 'отписаться') ||
                     str_contains(strtolower($message), 'unsubscribe') ||
                     isset($metadata['opt_out_link']) ||
                     isset($metadata['opt_out_text']);

        if (!$hasOptOut) {
            return [
                'passed' => false,
                'message' => 'Promotional messages must include opt-out option',
                'code' => 'MISSING_OPT_OUT',
            ];
        }

        return [
            'passed' => true,
            'message' => 'Opt-out option present',
            'code' => 'VALID',
        ];
    }

    /**
     * Get compliance statistics for tenant
     */
    public function getComplianceStatistics(int $tenantId): array
    {
        $cacheKey = "compliance:statistics:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addHours(6), function () use ($tenantId) {
            $total = NotificationCampaign::where('tenant_id', $tenantId)->count();
            $passed = NotificationCampaign::where('tenant_id', $tenantId)
                ->where('compliance_passed', true)
                ->count();
            $failed = $total - $passed;

            return [
                'total_checks' => $total,
                'passed' => $passed,
                'failed' => $failed,
                'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
                'common_failures' => $this->getCommonFailureReasons($tenantId),
            ];
        });
    }

    /**
     * Get common failure reasons
     */
    private function getCommonFailureReasons(int $tenantId): array
    {
        $failedCampaigns = NotificationCampaign::where('tenant_id', $tenantId)
            ->where('compliance_passed', false)
            ->get();

        $reasons = [];
        foreach ($failedCampaigns as $campaign) {
            $checks = $campaign->compliance_checks['checks'] ?? [];
            foreach ($checks as $checkName => $check) {
                if (!$check['passed']) {
                    $code = $check['code'] ?? 'unknown';
                    $reasons[$code] = ($reasons[$code] ?? 0) + 1;
                }
            }
        }

        arsort($reasons);
        return $reasons;
    }
}
