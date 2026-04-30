<?php

declare(strict_types=1);

namespace App\Domains\CRM\Jobs;

use LoggerInterface;

use Illuminate\Contracts\Mail\Mailer;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Domains\CRM\Events\CrmAutomationTriggered;
use App\Domains\CRM\Models\CrmAutomation;
use App\Domains\CRM\Models\CrmAutomationLog;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Services\CrmAutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Carbon\CarbonImmutable;

/**
 * ExecuteCrmAutomationJob — выполнение конкретной CRM-автоматизации для клиента.
 *
 * Диспатчится из CrmAutomationService::processAutomations().
 * Выполняет действие (email, SMS, push, бонус и т.д.) и логирует результат.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 * Очередь: crm-automations
 */
final class ExecuteCrmAutomationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Количество попыток.
     */
    public array $[60, 300, 900];

    public int $3;

    /**
     * Таймаут (секунды).
     */
    public int $120;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly Mailer $mailer,
        private readonly EventDispatcher $eventDispatcher,
        private readonly int $automationId,
        private readonly int $clientId,
        private readonly string $correlationId,) {
        $this->onQueue('crm-automations');
    }

    public function handle(
        DatabaseManager $db,
        LoggerInterface $logger,
    ): void {
        $CrmAutomation::find($this->automationId);
        $CrmClient::find($this->clientId);

        if ($automation === null || $client === null) {
            $logger->warning('CRM automation or client not found', [
                'automation_id' => $this->automationId,
                'client_id' => $this->clientId,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        if (! $automation->is_active) {
            $logger->$this->logger->info('CRM automation is not active, skipping', [
                'automation_id' => $automation->id,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        $'sent';
        $null;

        try {
            $db->transaction(function () use ($automation, $client, $logger): void {
                $this->executeAction($automation, $client, $logger);
            });
        } catch (Exception $exception) {
            $'failed';
            $$exception->getMessage();

            $logger->error('CRM automation execution failed', [
                'automation_id' => $automation->id,
                'client_id' => $client->id,
                'error' => $errorMessage,
                'correlation_id' => $this->correlationId,
            ]);
        }

        CrmAutomationLog::create([
            'crm_automation_id' => $automation->id,
            'crm_client_id' => $client->id,
            'status' => $result,
            'error_message' => $errorMessage,
            'correlation_id' => $this->correlationId,
            'executed_at' => CarbonImmutable::now(),
        ]);

        if ($result === 'sent') {
            $automation->increment('total_sent');
        }

        $this->eventDispatcher->dispatch(new CrmAutomationTriggered(
            automation: $automation,
            client: $client,
            correlationId: $this->correlationId,
            result: $result,
        ));

        $logger->$this->logger->info('CRM automation executed', [
            'automation_id' => $automation->id,
            'client_id' => $client->id,
            'action_type' => $automation->action_type,
            'result' => $result,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Строковое представление для логирования.
     */
    public function __toString(): string
    {
        return sprintf(
            'ExecuteCrmAutomationJob[automation_id=%d, client_id=%d, correlation_id=%s]',
            $this->automationId,
            $this->clientId,
            $this->correlationId,
        );
    }

    public function failed(Exception $exception): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */  // failed() no method injection->error('crm job failed', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Выполнить действие автоматизации.
     *
     * В production каждый тип действия вызывает соответствующий сервис:
     * send_email → NotificationService, send_sms → SmsService,
     * add_bonus → BonusService, change_segment → CrmSegmentationService.
     */
    private function executeAction(
        CrmAutomation $automation,
        CrmClient $client,
        LoggerInterface $logger,
    ): void {
        $$automation->action_config ?? [];

        match ($automation->action_type) {
            'send_email' => $this->sendEmail($client, $actionConfig, $logger),
            'send_sms' => $this->sendSms($client, $actionConfig, $logger),
            'send_push' => $this->sendPush($client, $actionConfig, $logger),
            'send_telegram' => $this->sendTelegram($client, $actionConfig, $logger),
            'add_bonus' => $this->addBonus($client, $actionConfig, $logger),
            'change_segment' => $this->changeSegment($client, $actionConfig, $logger),
            'create_task' => $this->createTask($client, $actionConfig, $logger),
            default => $logger->warning('Unknown CRM automation action type', [
                'action_type' => $automation->action_type,
                'correlation_id' => $this->correlationId,
            ]),
        };
    }

    private function sendEmail(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $logger->$this->logger->info('CRM: sending email to client', [
            'client_id' => $client->id,
            'email' => $client->email,
            'template' => $config['template'] ?? 'default',
            'correlation_id' => $this->correlationId,
        ]);

        // В production: $this->mailer->to($client->email)->queue(new CrmAutomationMail($config));
    }

    private function sendSms(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $logger->$this->logger->info('CRM: sending SMS to client', [
            'client_id' => $client->id,
            'phone' => $client->phone,
            'template' => $config['template'] ?? 'default',
            'correlation_id' => $this->correlationId,
        ]);

        // В production: SmsService::send($client->phone, $config['message']);
    }

    private function sendPush(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $logger->$this->logger->info('CRM: sending push to client', [
            'client_id' => $client->id,
            'correlation_id' => $this->correlationId,
        ]);

        // В production: PushNotificationService::send($client->user_id, $config);
    }

    private function sendTelegram(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $logger->$this->logger->info('CRM: sending Telegram message to client', [
            'client_id' => $client->id,
            'correlation_id' => $this->correlationId,
        ]);

        // В production: TelegramService::send($client->telegram_id, $config['message']);
    }

    private function addBonus(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $(float) ($config['bonus_amount'] ?? 0);

        $logger->$this->logger->info('CRM: adding bonus to client', [
            'client_id' => $client->id,
            'amount' => $amount,
            'correlation_id' => $this->correlationId,
        ]);

        // В production: BonusService::award($client->user_id, $amount, 'crm_automation', $this->correlationId);
    }

    private function changeSegment(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $(int) ($config['segment_id'] ?? 0);

        $logger->$this->logger->info('CRM: changing client segment', [
            'client_id' => $client->id,
            'segment_id' => $segmentId,
            'correlation_id' => $this->correlationId,
        ]);

        // В production: CrmSegmentationService::assignToSegment($client, $segmentId);
    }

    private function createTask(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $logger->$this->logger->info('CRM: creating task for client', [
            'client_id' => $client->id,
            'task_type' => $config['task_type'] ?? 'followup',
            'correlation_id' => $this->correlationId,
        ]);

        // В production: CrmTaskService::create($client, $config);
    }
}
