<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use Modules\Supermarket\Domain\Models\Document;
use Modules\Supermarket\Domain\Models\DocumentDistribution;
use Modules\Supermarket\Domain\Models\DocumentHistory;
use Modules\Supermarket\Domain\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Traits\WithAuditLogging;

/**
 * DocumentDistributionService — Сервис автоматической рассылки документов B2B клиентам
 * 
 * Отвечает за:
 * - Автоматическую рассылку документов B2B клиентам
 * - Отправку по расписанию
 * - Отслеживание статусов доставки
 * - Retry логику для неудачных отправок
 */
final class DocumentDistributionService
{
    use WithAuditLogging;

    public function __construct(
        private readonly \App\Services\AuditService $auditService
    ) {}

    /**
     * Автоматически распределить документ B2B клиентам
     */
    public function autoDistribute(Document $document, ?int $userId = null): array
    {
        if (!$document->shouldAutoDistribute()) {
            return ['success' => false, 'message' => 'Document should not be auto-distributed'];
        }

        $recipients = $this->getB2BRecipients($document);
        $results = [];

        foreach ($recipients as $recipient) {
            $results[] = $this->distributeToClient($document, $recipient, $userId);
        }

        // Записать в историю
        DocumentHistory::createLog(
            $document->id,
            DocumentHistory::ACTION_DISTRIBUTED,
            $userId,
            DocumentHistory::USER_TYPE_SYSTEM,
            null,
            ['recipients_count' => count($recipients)],
            "Auto-distributed to " . count($recipients) . " B2B clients"
        );

        $this->logAction(
            'document_auto_distributed',
            [
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'recipients_count' => count($recipients),
            ],
            $userId
        );

        return [
            'success' => true,
            'distributed' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Распределить документ конкретному B2B клиенту
     */
    public function distributeToClient(Document $document, Customer $client, ?int $userId = null, float $quantity = 0): array
    {
        return DB::transaction(function () use ($document, $client, $userId, $quantity) {
            // Проверить не отправляли ли уже
            $existing = DocumentDistribution::where('document_id', $document->id)
                ->where('customer_id', $client->id)
                ->first();

            if ($existing && $existing->delivery_status === DocumentDistribution::STATUS_DELIVERED) {
                return ['success' => false, 'message' => 'Already distributed', 'distribution_id' => $existing->id];
            }

            // Если это сертификат с batch tracking, проверить количество
            if ($document->batch_weight !== null && $quantity > 0) {
                if (!$document->canAddQuantity($quantity)) {
                    return [
                        'success' => false,
                        'message' => 'Insufficient batch quantity',
                        'batch_weight' => $document->batch_weight,
                        'delivered_quantity' => $document->delivered_quantity,
                        'requested_quantity' => $quantity,
                    ];
                }

                $document->addDeliveredQuantity($quantity);
            }

            // Создать или обновить запись распределения
            $distribution = $existing ?? new DocumentDistribution();
            $distribution->uuid = (string) \Illuminate\Support\Str::uuid();
            $distribution->document_id = $document->id;
            $distribution->seller_id = $document->seller_id;
            $distribution->customer_id = $client->id;
            $distribution->tenant_id = $document->tenant_id;
            $distribution->delivery_method = $this->determineDeliveryMethod($client);
            $distribution->delivery_status = DocumentDistribution::STATUS_PENDING;
            $distribution->sent_at = now();
            $distribution->save();

            // Отправить уведомление
            $this->sendNotification($distribution);

            // Обновить статус
            $distribution->delivery_status = DocumentDistribution::STATUS_SENT;
            $distribution->save();

            // Записать в историю
            DocumentHistory::createLog(
                $document->id,
                DocumentHistory::ACTION_DISTRIBUTED,
                $userId,
                DocumentHistory::USER_TYPE_SUPPLIER,
                null,
                [
                    'client_id' => $client->id,
                    'distribution_id' => $distribution->id,
                    'quantity' => $quantity,
                ],
                "Distributed to client {$client->id}"
            );

            return [
                'success' => true,
                'distribution_id' => $distribution->id,
                'delivery_method' => $distribution->delivery_method,
            ];
        });
    }

    /**
     * Получить B2B получателей для документа
     */
    private function getB2BRecipients(Document $document): array
    {
        $query = Customer::where('tenant_id', $document->tenant_id)
            ->where('is_b2b', true);

        // Фильтрация по сегментам
        if ($document->target_segments) {
            // TODO: Implement segment filtering
        }

        // Фильтрация по tier
        if ($document->target_tiers) {
            // TODO: Implement tier filtering
        }

        return $query->get()->all();
    }

    /**
     * Определить метод доставки для клиента
     */
    private function determineDeliveryMethod(Customer $client): string
    {
        // Приоритет: email > push > in_app
        if ($client->email && filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
            return DocumentDistribution::METHOD_EMAIL;
        }

        if ($client->push_notification_enabled) {
            return DocumentDistribution::METHOD_PUSH;
        }

        return DocumentDistribution::METHOD_IN_APP;
    }

    /**
     * Отправить уведомление о документе
     */
    private function sendNotification(DocumentDistribution $distribution): void
    {
        try {
            $client = $distribution->customer;
            $document = $distribution->document;

            match ($distribution->delivery_method) {
                DocumentDistribution::METHOD_EMAIL => $this->sendEmailNotification($distribution),
                DocumentDistribution::METHOD_PUSH => $this->sendPushNotification($distribution),
                DocumentDistribution::METHOD_IN_APP => $this->createInAppNotification($distribution),
                default => Log::warning("Unknown delivery method: {$distribution->delivery_method}"),
            };
        } catch (\Exception $e) {
            Log::error("Failed to send notification", [
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage(),
            ]);

            $distribution->delivery_status = DocumentDistribution::STATUS_FAILED;
            $distribution->error_message = $e->getMessage();
            $distribution->save();
        }
    }

    /**
     * Отправить email уведомление
     */
    private function sendEmailNotification(DocumentDistribution $distribution): void
    {
        // TODO: Create and send email notification
        // Mail::to($distribution->customer->email)->send(new DocumentAvailableMail($distribution));
        
        $distribution->delivery_status = DocumentDistribution::STATUS_DELIVERED;
        $distribution->delivered_at = now();
        $distribution->notification_sent = true;
        $distribution->save();
    }

    /**
     * Отправить push уведомление
     */
    private function sendPushNotification(DocumentDistribution $distribution): void
    {
        // TODO: Implement push notification
        $distribution->delivery_status = DocumentDistribution::STATUS_SENT;
        $distribution->save();
    }

    /**
     * Создать in-app уведомление
     */
    private function createInAppNotification(DocumentDistribution $distribution): void
    {
        // TODO: Create in-app notification
        $distribution->delivery_status = DocumentDistribution::STATUS_SENT;
        $distribution->save();
    }

    /**
     * Retry неудачные отправки
     */
    public function retryFailedDistributions(int $maxRetries = 3): array
    {
        $failedDistributions = DocumentDistribution::where('delivery_status', DocumentDistribution::STATUS_FAILED)
            ->where('retry_count', '<', $maxRetries)
            ->get();

        $results = [];

        foreach ($failedDistributions as $distribution) {
            $distribution->retry_count++;
            $distribution->delivery_status = DocumentDistribution::STATUS_PENDING;
            $distribution->error_message = null;
            $distribution->save();

            $this->sendNotification($distribution);
            $results[] = [
                'distribution_id' => $distribution->id,
                'success' => $distribution->delivery_status !== DocumentDistribution::STATUS_FAILED,
            ];
        }

        return [
            'retried' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Отметить документ как просмотренный
     */
    public function markAsViewed(int $distributionId, int $userId): bool
    {
        $distribution = DocumentDistribution::findOrFail($distributionId);
        
        if ($distribution->customer_id !== $userId) {
            return false;
        }

        return $distribution->markAsViewed();
    }

    /**
     * Отметить документ как скачанный
     */
    public function markAsDownloaded(int $distributionId, int $userId): bool
    {
        $distribution = DocumentDistribution::findOrFail($distributionId);
        
        if ($distribution->customer_id !== $userId) {
            return false;
        }

        return $distribution->markAsDownloaded();
    }
}
