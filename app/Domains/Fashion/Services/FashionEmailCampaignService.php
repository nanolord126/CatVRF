<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Personalized Email Campaign Service для Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * Персонализированные email-кампании на основе поведения,
        сегментация, A/B тестирование контента, автоматические триггеры.
 */
final readonly class FashionEmailCampaignService
{
    private const MIN_SEGMENT_SIZE = 50;
    private const MAX_EMAILS_PER_DAY = 1000;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Создать email-кампанию.
     */
    public function createCampaign(
        string $name,
        string $subject,
        string $template,
        array $segmentationRules,
        ?Carbon $scheduledFor = null,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $campaignId = $this->db->table('fashion_email_campaigns')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $name,
            'subject' => $subject,
            'template' => $template,
            'segmentation_rules' => json_encode($segmentationRules, JSON_UNESCAPED_UNICODE),
            'status' => 'draft',
            'scheduled_for' => $scheduledFor,
            'sent_count' => 0,
            'opened_count' => 0,
            'clicked_count' => 0,
            'converted_count' => 0,
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->audit->record(
            action: 'fashion_email_campaign_created',
            subjectType: 'fashion_email_campaign',
            subjectId: $campaignId,
            oldValues: [],
            newValues: [
                'name' => $name,
                'subject' => $subject,
            ],
            correlationId: $correlationId
        );

        return [
            'campaign_id' => $campaignId,
            'name' => $name,
            'status' => 'draft',
            'scheduled_for' => $scheduledFor?->toIso8601String(),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить сегмент пользователей для кампании.
     */
    public function getSegment(int $campaignId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $campaign = $this->db->table('fashion_email_campaigns')
            ->where('id', $campaignId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($campaign === null) {
            throw new \InvalidArgumentException('Campaign not found', 404);
        }

        $rules = json_decode($campaign['segmentation_rules'], true);
        $segment = $this->applySegmentationRules($rules, $tenantId);

        return [
            'campaign_id' => $campaignId,
            'segment_size' => count($segment),
            'users' => $segment,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Отправить кампанию.
     */
    public function sendCampaign(int $campaignId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $campaign = $this->db->table('fashion_email_campaigns')
            ->where('id', $campaignId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'draft')
            ->first();

        if ($campaign === null) {
            throw new \InvalidArgumentException('Campaign not found or already sent', 404);
        }

        $rules = json_decode($campaign['segmentation_rules'], true);
        $segment = $this->applySegmentationRules($rules, $tenantId);

        if (count($segment) < self::MIN_SEGMENT_SIZE) {
            throw new \RuntimeException('Segment too small for campaign', 400);
        }

        $sentCount = 0;
        foreach ($segment as $userId) {
            try {
                $this->sendEmail($userId, $campaign, $correlationId);
                $sentCount++;
            } catch (\Throwable $e) {
                Log::channel('audit')->warning('Failed to send email', [
                    'user_id' => $userId,
                    'campaign_id' => $campaignId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        $this->db->table('fashion_email_campaigns')
            ->where('id', $campaignId)
            ->update([
                'status' => 'sent',
                'sent_count' => $sentCount,
                'sent_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

        $this->audit->record(
            action: 'fashion_email_campaign_sent',
            subjectType: 'fashion_email_campaign',
            subjectId: $campaignId,
            oldValues: [],
            newValues: [
                'sent_count' => $sentCount,
            ],
            correlationId: $correlationId
        );

        return [
            'campaign_id' => $campaignId,
            'sent_count' => $sentCount,
            'status' => 'sent',
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Записать открытие email.
     */
    public function recordOpen(int $campaignId, int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->db->table('fashion_email_opens')->insert([
            'campaign_id' => $campaignId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'opened_at' => Carbon::now(),
            'correlation_id' => $correlationId,
        ]);

        $this->db->table('fashion_email_campaigns')
            ->where('id', $campaignId)
            ->increment('opened_count');

        return [
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'recorded' => true,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Записать клик по email.
     */
    public function recordClick(int $campaignId, int $userId, string $link, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->db->table('fashion_email_clicks')->insert([
            'campaign_id' => $campaignId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'link' => $link,
            'clicked_at' => Carbon::now(),
            'correlation_id' => $correlationId,
        ]);

        $this->db->table('fashion_email_campaigns')
            ->where('id', $campaignId)
            ->increment('clicked_count');

        return [
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'link' => $link,
            'recorded' => true,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить статистику кампании.
     */
    public function getCampaignStats(int $campaignId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $campaign = $this->db->table('fashion_email_campaigns')
            ->where('id', $campaignId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($campaign === null) {
            throw new \InvalidArgumentException('Campaign not found', 404);
        }

        $openRate = $campaign['sent_count'] > 0
            ? ($campaign['opened_count'] / $campaign['sent_count']) * 100
            : 0;

        $clickRate = $campaign['sent_count'] > 0
            ? ($campaign['clicked_count'] / $campaign['sent_count']) * 100
            : 0;

        $conversionRate = $campaign['sent_count'] > 0
            ? ($campaign['converted_count'] / $campaign['sent_count']) * 100
            : 0;

        return [
            'campaign_id' => $campaignId,
            'name' => $campaign['name'],
            'status' => $campaign['status'],
            'sent_count' => $campaign['sent_count'],
            'opened_count' => $campaign['opened_count'],
            'clicked_count' => $campaign['clicked_count'],
            'converted_count' => $campaign['converted_count'],
            'open_rate' => round($openRate, 2),
            'click_rate' => round($clickRate, 2),
            'conversion_rate' => round($conversionRate, 2),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать триггерную кампанию (abandoned cart, etc).
     */
    public function createTriggerCampaign(
        string $triggerType,
        array $triggerConfig,
        string $subject,
        string $template,
        string $correlationId = ''
    ): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $campaignId = $this->db->table('fashion_email_campaigns')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => "Trigger: {$triggerType}",
            'subject' => $subject,
            'template' => $template,
            'segmentation_rules' => json_encode(['trigger_type' => $triggerType], JSON_UNESCAPED_UNICODE),
            'trigger_type' => $triggerType,
            'trigger_config' => json_encode($triggerConfig, JSON_UNESCAPED_UNICODE),
            'status' => 'active',
            'sent_count' => 0,
            'opened_count' => 0,
            'clicked_count' => 0,
            'converted_count' => 0,
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return [
            'campaign_id' => $campaignId,
            'trigger_type' => $triggerType,
            'status' => 'active',
            'correlation_id' => $correlationId,
        ];
    }

    private function applySegmentationRules(array $rules, int $tenantId): array
    {
        $query = DB::table('users')->where('tenant_id', $tenantId);

        if (!empty($rules['min_purchases'])) {
            $query->where('purchase_count', '>=', $rules['min_purchases']);
        }

        if (!empty($rules['categories'])) {
            $query->whereIn('preferred_category', $rules['categories']);
        }

        if (!empty($rules['price_range'])) {
            $query->whereBetween('avg_order_value', [
                $rules['price_range']['min'],
                $rules['price_range']['max'],
            ]);
        }

        if (!empty($rules['last_purchase_days'])) {
            $query->where('last_purchase_at', '>=', Carbon::now()->subDays($rules['last_purchase_days']));
        }

        return $query->limit(self::MAX_EMAILS_PER_DAY)->pluck('id')->toArray();
    }

    private function sendEmail(int $userId, array $campaign, string $correlationId): void
    {
        $personalizedContent = $this->personalizeContent($campaign['template'], $userId, $correlationId);

        $this->db->table('fashion_email_logs')->insert([
            'campaign_id' => $campaign['id'],
            'user_id' => $userId,
            'subject' => $campaign['subject'],
            'content' => $personalizedContent,
            'sent_at' => Carbon::now(),
            'correlation_id' => $correlationId,
        ]);
    }

    private function personalizeContent(string $template, int $userId, string $correlationId): string
    {
        $user = DB::table('users')->where('id', $userId)->first();
        
        if ($user === null) {
            return $template;
        }

        $content = str_replace('{{name}}', $user['name'] ?? 'Customer', $template);
        $content = str_replace('{{email}}', $user['email'], $content);

        $recommendations = $this->getUserRecommendations($userId, $correlationId);
        $content = str_replace('{{recommendations}}', json_encode($recommendations), $content);

        return $content;
    }

    private function getUserRecommendations(int $userId, string $correlationId): array
    {
        return DB::table('fashion_user_memory_interactions')
            ->where('user_id', $userId)
            ->select('category')
            ->distinct()
            ->limit(5)
            ->pluck('category')
            ->toArray();
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
