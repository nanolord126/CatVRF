<?php declare(strict_types=1);

namespace App\Services\Marketing;


use Illuminate\Http\Request;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\ML\AnonymizationService;
use App\Services\ML\UserBehaviorAnalyzerService;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * NewsletterService — рассылки (Email, Push, SMS, In-app).
 *
 * Правила канона:
 *  - Сегментация: new / returning / taste_profile / vertical
 *  - Все рассылки с correlation_id, FraudControlService::check(), rate-limit
 *  - A/B-тестирование тем и шаблонов
 *  - Открытия / клики трекаются в newsletter_opens и newsletter_clicks
 *  - Персональные данные не попадают в ClickHouse (только anonymized)
 *  - Каналы: email (Mailgun/SendGrid), Push (Firebase), SMS (SMS.ru), In-app
 */
final readonly class NewsletterService
{
    public function __construct(
        private readonly Request $request,
        private FraudControlService         $fraud,
        private UserBehaviorAnalyzerService  $behaviorAnalyzer,
        private AnonymizationService         $anonymizer,
        private AuditService                 $audit,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Создать и запустить рассылку по сегменту.
     *
     * @param array{
     *   name: string,
     *   subject: string,
     *   template_id: int,
     *   channel: string,
     *   segment: string,
     *   vertical?: string,
     *   ab_variant?: string,
     *   correlation_id?: string,
     * } $dto
     */
    public function createAndSend(array $dto, int $tenantId, int $senderId): array
    {
        $correlationId = $dto['correlation_id'] ?? Str::uuid()->toString();

        $this->fraud->check($senderId, 'newsletter_send', 0, (string) $this->request->ip(), null, $correlationId);

        return $this->db->transaction(function () use ($dto, $tenantId, $senderId, $correlationId): array {
            // 1. Определить получателей по сегменту
            $recipients = $this->resolveRecipients(
                tenantId: $tenantId,
                segment:  $dto['segment'],     // 'new', 'returning', 'churn_risk', 'vip', 'vertical:{name}'
                vertical: $dto['vertical'] ?? null,
            );

            // 2. Сохранить кампанию рассылки
            $newsletterId = $this->db->table('newsletter_campaigns')->insertGetId([
                'tenant_id'      => $tenantId,
                'name'           => $dto['name'],
                'subject'        => $dto['subject'],
                'template_id'    => $dto['template_id'],
                'channel'        => $dto['channel'],
                'segment'        => $dto['segment'],
                'ab_variant'     => $dto['ab_variant'] ?? 'A',
                'recipients_count' => count($recipients),
                'status'         => 'sending',
                'correlation_id' => $correlationId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // 3. Поставить в очередь отправки (batch по 100)
            collect($recipients)->chunk(100)->each(function ($batch) use ($newsletterId, $dto, $correlationId): void {
                \Illuminate\Support\Facades\Bus::dispatchToQueue(
                    job: new \App\Jobs\NewsletterBatchJob($newsletterId, $batch->toArray(), $dto['channel'], $correlationId),
                    queue: 'newsletters',
                );
            });

            $this->audit->record('newsletter_created', 'newsletter_campaigns', $newsletterId, [], $dto, $correlationId);

            $this->logger->channel('audit')->info('Newsletter campaign created', [
                'newsletter_id'   => $newsletterId,
                'channel'         => $dto['channel'],
                'segment'         => $dto['segment'],
                'recipients'      => count($recipients),
                'correlation_id'  => $correlationId,
            ]);

            return [
                'newsletter_id'  => $newsletterId,
                'recipients'     => count($recipients),
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Трекинг открытия письма.
     */
    public function trackOpen(int $newsletterId, int $userId, string $correlationId): void
    {
        $anonId = $this->anonymizer->anonymizeUserId($userId);

        $this->db->table('newsletter_opens')->insert([
            'newsletter_id'      => $newsletterId,
            'anonymized_user_id' => $anonId,  // НЕ raw user_id!
            'correlation_id'     => $correlationId,
            'opened_at'          => now(),
        ]);
    }

    /**
     * Трекинг клика по ссылке в письме.
     */
    public function trackClick(int $newsletterId, int $userId, string $linkUrl, string $correlationId): void
    {
        $anonId = $this->anonymizer->anonymizeUserId($userId);

        $this->db->table('newsletter_clicks')->insert([
            'newsletter_id'      => $newsletterId,
            'anonymized_user_id' => $anonId,  // НЕ raw user_id!
            'link_url'           => $linkUrl,
            'correlation_id'     => $correlationId,
            'clicked_at'         => now(),
        ]);
    }

    // ─── Сегментация получателей ─────────────────────────────────────────────

    /**
     * Разрешить список user_id по сегменту.
     * ТОЛЬКО для Queue job — сами id не идут в ClickHouse.
     */
    private function resolveRecipients(int $tenantId, string $segment, ?string $vertical): array
    {
        $query = $this->db->table('users')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotNull('email');

        return match ($segment) {
            'returning'  => $query->where('created_at', '<', now()->subDays(7))->pluck('id')->toArray(),
            'churn_risk' => $this->resolveChurnRiskUsers($tenantId),
            'vip'        => $this->resolveVipUsers($tenantId),
            default      => str_starts_with($segment, 'vertical:')
                ? $this->resolveVerticalUsers($tenantId, substr($segment, 9))
                : $query->pluck('id')->toArray(),
        };
    }

    private function resolveChurnRiskUsers(int $tenantId): array
    {
        return $this->db->table('users')
            ->where('tenant_id', $tenantId)
            ->whereExists(fn ($q) => $q->from('orders')
                ->whereColumn('orders.user_id', 'users.id')
                ->where('orders.created_at', '<', now()->subDays(14)))
            ->whereNotExists(fn ($q) => $q->from('orders')
                ->whereColumn('orders.user_id', 'users.id')
                ->where('orders.created_at', '>=', now()->subDays(14)))
            ->pluck('id')
            ->toArray();
    }

    private function resolveVipUsers(int $tenantId): array
    {
        return $this->db->table('users')
            ->where('users.tenant_id', $tenantId)
            ->join('orders', 'orders.user_id', '=', 'users.id')
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->groupBy('users.id')
            ->havingRaw('SUM(orders.total_amount) >= 100000')
            ->pluck('users.id')
            ->toArray();
    }

    private function resolveVerticalUsers(int $tenantId, string $vertical): array
    {
        return $this->db->table('users')
            ->where('users.tenant_id', $tenantId)
            ->join('orders', 'orders.user_id', '=', 'users.id')
            ->where('orders.vertical', $vertical)
            ->distinct()
            ->pluck('users.id')
            ->toArray();
    }
}
