<?php declare(strict_types=1);

namespace App\Jobs;



use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * AnnualAnonymizationJob — ежегодная анонимизация персональных данных.
 *
 * Правила канона (GDPR / ФЗ-152):
 *  - Удаление/анонимизация raw персональных данных старше 365 дней.
 *  - Никогда не удаляем агрегаты и аналитику (только raw PII).
 *  - Поля users: email → anon hash, phone → null, full_name → 'Аноним'.
 *  - delivery_tracks: lat/lon → округление до 2 знаков (город, не улица).
 *  - raw behavior events старше 365 дней: user_id → null.
 *  - Все операции логируются в audit-канал с correlation_id.
 *  - Запускается yearly() в 01:00 UTC 1 января.
 *
 * Что НЕ трогаем:
 *  - Финансовые записи (wallets, balance_transactions, payment_transactions).
 *  - Агрегированные ML-данные (user_taste_profiles).
 *  - Аудит-логи (audit_logs — хранятся 7 лет по ФЗ-402).
 */
final class AnnualAnonymizationJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    public int $tries   = 1;
    public int $timeout = 1800;  // 30 минут

    private string $correlationId;

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    )
    {
        $this->correlationId = Str::uuid()->toString();
    }

    public function handle(AuditService $audit): void
    {
        $cutoff = now()->subDays(365)->toDateTimeString();

        $this->logger->channel('audit')->info('AnnualAnonymizationJob started', [
            'cutoff'         => $cutoff,
            'correlation_id' => $this->correlationId,
        ]);

        $stats = [
            'users_anonymized'          => 0,
            'delivery_tracks_blurred'   => 0,
            'behavior_events_cleared'   => 0,
            'fraud_attempts_anonymized' => 0,
            'newsletter_emails_cleared' => 0,
        ];

        // 1. Пользователи — анонимизируем неактивных более 365 дней
        $stats['users_anonymized'] = $this->anonymizeInactiveUsers($cutoff);

        // 2. delivery_tracks — размытие координат (округление до 2 знаков ≈ ±1 км)
        $stats['delivery_tracks_blurred'] = $this->blurDeliveryTracks($cutoff);

        // 3. Raw события поведения — обнуляем user_id (деперсонификация)
        $stats['behavior_events_cleared'] = $this->clearBehaviorEvents($cutoff);

        // 4. fraud_attempts — удаляем ip_address и device_fingerprint старых записей
        $stats['fraud_attempts_anonymized'] = $this->anonymizeFraudAttempts($cutoff);

        // 5. newsletter_* — удаляем email-адреса из opens/clicks
        $stats['newsletter_emails_cleared'] = $this->clearNewsletterEmails($cutoff);

        $audit->record('annual_anonymization_completed', 'system', null, [], $stats, $this->correlationId);

        $this->logger->channel('audit')->info('AnnualAnonymizationJob completed', array_merge(
            $stats,
            ['correlation_id' => $this->correlationId]
        ));
    }

    // ── шаги анонимизации ────────────────────────────────────────────────────

    /**
     * Пользователи без активности более 365 дней:
     * email → hash@anon.local, phone → null, full_name → 'Аноним'
     * Аккаунт не удаляется — только обезличивается.
     */
    private function anonymizeInactiveUsers(string $cutoff): int
    {
        if (!$this->tableExists('users')) {
            return 0;
        }

        $count = 0;

        $this->db->table('users')
            ->where('updated_at', '<', $cutoff)
            ->whereNull('deleted_at')
            ->where('is_anonymized', false)
            ->orderBy('id')
            ->chunk(500, function ($users) use (&$count): void {
                foreach ($users as $user) {
                    $anonEmail = 'anon_' . substr(hash('sha256', $user->id . $this->config->get('app.anonymization_salt', 'default')), 0, 16) . '@anon.local';

                    $this->db->table('users')
                        ->where('id', $user->id)
                        ->update([
                            'email'          => $anonEmail,
                            'phone'          => null,
                            'full_name'      => 'Аноним',
                            'is_anonymized'  => true,
                            'anonymized_at'  => now()->toDateTimeString(),
                            'updated_at'     => now()->toDateTimeString(),
                        ]);

                    $count++;
                }
            });

        $this->logger->channel('audit')->info('Users anonymized', [
            'count'          => $count,
            'correlation_id' => $this->correlationId,
        ]);

        return $count;
    }

    /**
     * delivery_tracks старше 365 дней:
     * lat/lon округляем до 2 знаков после запятой (~1.1 км точность).
     */
    private function blurDeliveryTracks(string $cutoff): int
    {
        if (!$this->tableExists('delivery_tracks')) {
            return 0;
        }

        $affected = $this->db->table('delivery_tracks')
            ->where('tracked_at', '<', $cutoff)
            ->update([
                'lat' => $this->db->raw('ROUND(lat::numeric, 2)'),
                'lon' => $this->db->raw('ROUND(lon::numeric, 2)'),
            ]);

        $this->logger->channel('audit')->info('Delivery tracks blurred', [
            'affected'       => $affected,
            'correlation_id' => $this->correlationId,
        ]);

        return $affected;
    }

    /**
     * user_behavior_events / anonymized_behavior старше 365 дней:
     * обнуляем user_id (если он ещё остался в raw-хранилище).
     */
    private function clearBehaviorEvents(string $cutoff): int
    {
        $total = 0;

        foreach (['user_behavior_events', 'behavior_events'] as $table) {
            if (!$this->tableExists($table)) {
                continue;
            }

            $affected = $this->db->table($table)
                ->where('created_at', '<', $cutoff)
                ->whereNotNull('user_id')
                ->update(['user_id' => null]);

            $total += $affected;
        }

        $this->logger->channel('audit')->info('Behavior events user_id cleared', [
            'affected'       => $total,
            'correlation_id' => $this->correlationId,
        ]);

        return $total;
    }

    /**
     * fraud_attempts старше 365 дней: удаляем ip_address и device_fingerprint.
     */
    private function anonymizeFraudAttempts(string $cutoff): int
    {
        if (!$this->tableExists('fraud_attempts')) {
            return 0;
        }

        $affected = $this->db->table('fraud_attempts')
            ->where('created_at', '<', $cutoff)
            ->update([
                'ip_address'         => null,
                'device_fingerprint' => null,
            ]);

        $this->logger->channel('audit')->info('Fraud attempts anonymized', [
            'affected'       => $affected,
            'correlation_id' => $this->correlationId,
        ]);

        return $affected;
    }

    /**
     * newsletter_opens / newsletter_clicks старше 365 дней:
     * удаляем email (если хранится отдельно в логах).
     */
    private function clearNewsletterEmails(string $cutoff): int
    {
        $total = 0;

        foreach (['newsletter_opens', 'newsletter_clicks'] as $table) {
            if (!$this->tableExists($table)) {
                continue;
            }

            $affected = $this->db->table($table)
                ->where('created_at', '<', $cutoff)
                ->whereNotNull('email')
                ->update(['email' => null]);

            $total += $affected;
        }

        return $total;
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function tableExists(string $table): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable($table);
    }
}

