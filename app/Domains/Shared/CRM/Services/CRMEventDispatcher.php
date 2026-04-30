<?php

declare(strict_types=1);

namespace App\Domains\Shared\CRM\Services;

use App\Domains\Shared\CRM\Jobs\CRMSyncJob;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * CRMEventDispatcher - Диспетчер событий для синхронизации с CRM.
 *
 * Маршрутизирует события из вертикалей в соответствующие эндпоинты CRM.
 * Все отправки выполняются асинхронно через очереди для отказоустойчивости.
 */
final readonly class CRMEventDispatcher
{
    private LoggerInterface $logger;

    private const ENDPOINTS = [
        'order.created' => '/orders/create',
        'order.status_changed' => '/orders/status',
        'order.cancelled' => '/orders/cancel',
        'order.delivered' => '/orders/delivery',
        'return.created' => '/returns/create',
        'return.approved' => '/returns/approve',
        'return.rejected' => '/returns/reject',
        'cashback.accrued' => '/loyalty/cashback',
        'customer.updated' => '/customers/update',
        'b2b.lead.created' => '/b2b/leads',
        'b2b.company.registered' => '/b2b/companies',
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Диспетчеризировать событие в CRM.
     *
     * @param string $eventName Имя события
     * @param array $data Данные события
     * @param int|null $delaySeconds Задержка перед отправкой (секунды)
     * @return bool
     */
    public function dispatch(string $eventName, array $data, ?int $delaySeconds = 3): bool
    {
        $endpoint = self::ENDPOINTS[$eventName] ?? null;

        if (!$endpoint) {
            $this->logger->warning('CRM event not mapped to endpoint', [
                'event' => $eventName,
                'data' => $data,
            ]);
            return false;
        }

        $correlationId = $data['correlation_id'] ?? $this->generateCorrelationId();

        $this->logger->info('CRM event dispatched', [
            'correlation_id' => $correlationId,
            'event' => $eventName,
            'endpoint' => $endpoint,
            'order_id' => $data['order_id'] ?? null,
        ]);

        try {
            $job = new CRMSyncJob(
                endpoint: $endpoint,
                payload: array_merge($data, [
                    'event' => $eventName,
                    'correlation_id' => $correlationId,
                    'dispatched_at' => now()->toIso8601String(),
                ]),
                eventName: $eventName
            );

            if ($delaySeconds && $delaySeconds > 0) {
                $job->delay($delaySeconds);
            }

            dispatch($job)->onQueue('crm-sync');

            return true;
        } catch (\Exception $e) {
            $this->logger->error('CRM event dispatch failed', [
                'correlation_id' => $correlationId,
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Диспетчеризировать создание заказа.
     */
    public function dispatchOrderCreated(array $orderData): bool
    {
        return $this->dispatch('order.created', $orderData);
    }

    /**
     * Диспетчеризировать изменение статуса заказа.
     */
    public function dispatchOrderStatusChanged(array $orderData): bool
    {
        return $this->dispatch('order.status_changed', $orderData);
    }

    /**
     * Диспетчеризировать отмену заказа.
     */
    public function dispatchOrderCancelled(array $orderData): bool
    {
        return $this->dispatch('order.cancelled', $orderData);
    }

    /**
     * Диспетчеризировать доставку заказа.
     */
    public function dispatchOrderDelivered(array $orderData): bool
    {
        return $this->dispatch('order.delivered', $orderData);
    }

    /**
     * Диспетчеризировать создание возврата.
     */
    public function dispatchReturnCreated(array $returnData): bool
    {
        return $this->dispatch('return.created', $returnData);
    }

    /**
     * Диспетчеризировать одобрение возврата.
     */
    public function dispatchReturnApproved(array $returnData): bool
    {
        return $this->dispatch('return.approved', $returnData);
    }

    /**
     * Диспетчеризировать отклонение возврата.
     */
    public function dispatchReturnRejected(array $returnData): bool
    {
        return $this->dispatch('return.rejected', $returnData);
    }

    /**
     * Диспетчеризировать начисление кэшбэка.
     */
    public function dispatchCashbackAccrued(array $cashbackData): bool
    {
        return $this->dispatch('cashback.accrued', $cashbackData);
    }

    /**
     * Диспетчеризировать обновление клиента.
     */
    public function dispatchCustomerUpdated(array $customerData): bool
    {
        return $this->dispatch('customer.updated', $customerData);
    }

    /**
     * Диспетчеризировать создание B2B лида.
     */
    public function dispatchB2BLeadCreated(array $leadData): bool
    {
        return $this->dispatch('b2b.lead.created', $leadData, 1);
    }

    /**
     * Диспетчеризировать регистрацию B2B компании.
     */
    public function dispatchB2BCompanyRegistered(array $companyData): bool
    {
        return $this->dispatch('b2b.company.registered', $companyData, 1);
    }

    /**
     * Сгенерировать correlation ID.
     */
    private function generateCorrelationId(): string
    {
        return 'crm_' . uniqid() . '_' . bin2hex(random_bytes(4));
    }
}
