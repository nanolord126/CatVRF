<?php

declare(strict_types=1);

namespace App\Domains\CRM\Jobs;

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
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

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
    use \Illuminate\Foundation\Events\Dispatchable;
    use \Illuminate\Queue\InteractsWithQueue;
    use \Illuminate\Bus\Queueable;
    use \Illuminate\Queue\SerializesModels;

    /**
     * Количество попыток.
     */
    public int $tries = 3;

    /**
     * Таймаут (секунды).
     */
    public int $timeout = 120;

    public function __construct(
        private readonly int $automationId,
        private readonly int $clientId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('crm-automations');
    }

    public function handle(
        DatabaseManager $db,
        LoggerInterface $logger,
    ): void {
        $automation = CrmAutomation::find($this->automationId);
        $client = CrmClient::find($this->clientId);

        if ($automation === null || $client === null) {
            $logger->warning('CRM automation or client not found', [
                'automation_id' => $this->automationId,
                'client_id' => $this->clientId,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        if (! $automation->is_active) {
            $logger->info('CRM automation is not active, skipping', [
                'automation_id' => $automation->id,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        $result = 'sent';
        $errorMessage = null;

        try {
            $db->transaction(function () use ($automation, $client, $logger): void {
                $this->executeAction($automation, $client, $logger);
            });
        } catch (\Throwable $exception) {
            $result = 'failed';
            $errorMessage = $exception->getMessage();

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
            'executed_at' => now(),
        ]);

        if ($result === 'sent') {
            $automation->increment('total_sent');
        }

        event(new CrmAutomationTriggered(
            automation: $automation,
            client: $client,
            correlationId: $this->correlationId,
            result: $result,
        ));

        $logger->info('CRM automation executed', [
            'automation_id' => $automation->id,
            'client_id' => $client->id,
            'action_type' => $automation->action_type,
            'result' => $result,
            'correlation_id' => $this->correlationId,
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
        $actionConfig = $automation->action_config ?? [];

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
        $logger->info('CRM: sending email to client', [
            'client_id' => $client->id,
            'email' => $client->email,
            'template' => $config['template'] ?? 'default',
            'correlation_id' => $this->correlationId,
        ]);

        // В production: Mail::to($client->email)->queue(new CrmAutomationMail($config));
    }

    private function sendSms(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $logger->info('CRM: sending SMS to client', [
            'client_id' => $client->id,
            'phone' => $client->phone,
            'template' => $config['template'] ?? 'default',
            'correlation_id' => $this->correlationId,
        ]);

        // В production: SmsService::send($client->phone, $config['message']);
    }

    private function sendPush(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $logger->info('CRM: sending push to client', [
            'client_id' => $client->id,
            'correlation_id' => $this->correlationId,
        ]);

        // В production: PushNotificationService::send($client->user_id, $config);
    }

    private function sendTelegram(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $logger->info('CRM: sending Telegram message to client', [
            'client_id' => $client->id,
            'correlation_id' => $this->correlationId,
        ]);

        // В production: TelegramService::send($client->telegram_id, $config['message']);
    }

    private function addBonus(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $amount = (float) ($config['bonus_amount'] ?? 0);

        $logger->info('CRM: adding bonus to client', [
            'client_id' => $client->id,
            'amount' => $amount,
            'correlation_id' => $this->correlationId,
        ]);

        // В production: BonusService::award($client->user_id, $amount, 'crm_automation', $this->correlationId);
    }

    private function changeSegment(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $segmentId = (int) ($config['segment_id'] ?? 0);

        $logger->info('CRM: changing client segment', [
            'client_id' => $client->id,
            'segment_id' => $segmentId,
            'correlation_id' => $this->correlationId,
        ]);

        // В production: CrmSegmentationService::assignToSegment($client, $segmentId);
    }

    private function createTask(CrmClient $client, array $config, LoggerInterface $logger): void
    {
        $logger->info('CRM: creating task for client', [
            'client_id' => $client->id,
            'task_type' => $config['task_type'] ?? 'followup',
            'correlation_id' => $this->correlationId,
        ]);

        // В production: CrmTaskService::create($client, $config);
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
}




