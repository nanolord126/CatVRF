<?php declare(strict_types=1);

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Str;
use Modules\Core\Models\Tenant;
use Modules\Inventory\Models\InventoryItem;
use Modules\Notifications\Jobs\SendNotificationJob;
use Illuminate\Log\LogManager;

/**
 * Low Stock Notification Job
 * CANON 2026 - Production Ready
 *
 * Ежедневная проверка остатков и отправка уведомлений о низком запасе.
 * Запускается каждый день в 08:00 UTC.
 */
final class LowStockNotificationJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 1800; // 30 минут
    public int $tries = 2;

    private readonly string $correlationId;

    public function __construct(
        private readonly LogManager $logger,
    )
    {
        $this->correlationId = (string) Str::uuid()->toString();
    }

    public function handle(): void
    {
        try {
            $this->logger->channel('audit')->info('Low stock notification check started', [
                'correlation_id' => $this->correlationId,
                'timestamp' => now()->toIso8601String(),
            ]);

            // 1. Найти все tenants с включённым уведомлением
            $tenants = Tenant::query()
                ->where('low_stock_alerts_enabled', true)
                ->get();

            if ($tenants->isEmpty()) {
                $this->logger->info('No tenants with low stock alerts enabled');
                return;
            }

            // 2. Для каждого tenant найти товары с низким остатком
            foreach ($tenants as $tenant) {
                $this->checkTenantInventory($tenant);
            }

            $this->logger->channel('audit')->info('Low stock notification check completed', [
                'correlation_id' => $this->correlationId,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);

            $this->logger->channel('audit')->error('Low stock notification job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Проверить инвентарь для конкретного tenant
     */
    private function checkTenantInventory(Tenant $tenant): void
    {
        $lowStockItems = InventoryItem::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereRaw('current_stock <= min_stock_threshold')
            ->get();

        if ($lowStockItems->isEmpty()) {
            return;
        }

        $this->logger->info('Low stock items found', [
            'tenant_id' => $tenant->id,
            'count' => $lowStockItems->count(),
        ]);

        // Отправить уведомление владельцу
        $this->sendNotificationToOwner($tenant, $lowStockItems);

        // Отправить email менеджеру склада (если есть)
        $this->sendEmailToManager($tenant, $lowStockItems);
    }

    /**
     * Отправить push-уведомление владельцу через сокет
     */
    private function sendNotificationToOwner(Tenant $tenant, $lowStockItems): void
    {
        $owner = $tenant->owner();

        if (!$owner) {
            return;
        }

        $itemCount = $lowStockItems->count();
        $criticalItems = $lowStockItems->filter(fn($item) => $item->current_stock === 0)->count();

        $message = "⚠️ У вас {$itemCount} товаров с низким остатком";

        if ($criticalItems > 0) {
            $message .= " ({$criticalItems} критических)";
        }

        // Создать уведомление в БД
        $owner->notifications()->create([
            'type' => 'inventory.low_stock',
            'title' => 'Низкий остаток товаров',
            'message' => $message,
            'data' => [
                'item_count' => $itemCount,
                'critical_count' => $criticalItems,
                'items' => $lowStockItems
                    ->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'current_stock' => $item->current_stock,
                        'min_threshold' => $item->min_stock_threshold,
                    ])
                    ->toArray(),
            ],
            'correlation_id' => $this->correlationId,
        ]);

        $this->logger->info('Low stock notification sent to owner', [
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'item_count' => $itemCount,
        ]);
    }

    /**
     * Отправить email менеджеру склада
     */
    private function sendEmailToManager(Tenant $tenant, $lowStockItems): void
    {
        // Найти менеджера склада
        $manager = $tenant->users()
            ->whereHas('roles', fn($q) => $q->where('name', 'warehouse_manager'))
            ->first();

        if (!$manager) {
            return;
        }

        $itemsSummary = $lowStockItems
            ->map(fn($item) => sprintf(
                "%s: %d шт. (минимум: %d)",
                $item->name,
                $item->current_stock,
                $item->min_stock_threshold
            ))
            ->implode("\n");

        SendNotificationJob::dispatch(
            email: $manager->email,
            subject: 'Уведомление: низкий остаток товаров',
            template: 'emails.low-stock-notification',
            data: [
                'manager_name' => $manager->name,
                'tenant_name' => $tenant->name,
                'items_count' => $lowStockItems->count(),
                'items_summary' => $itemsSummary,
                'correlation_id' => $this->correlationId,
            ]
        )->onQueue('emails');
    }

    public function failed(\Exception $exception): void
    {
        $this->logger->channel('audit')->error('LowStockNotificationJob failed permanently', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
