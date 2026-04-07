<?php

declare(strict_types=1);

namespace App\Domains\Finances\Infrastructure\Jobs;

use App\Domains\Finances\Application\UseCases\ProcessMonthlyPayoutsUseCase;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Job: ежемесячный расчёт заработка и инициация выплат для всех тенантов.
 *
 * Запускается каждый 1-й день месяца в 03:00 через Scheduler.
 * Итерирует по всем активным тенантам и вызывает
 * ProcessMonthlyPayoutsUseCase для каждого.
 *
 * В конструкторе нельзя инжектить Request или LoggerInterface —
 * они не сериализуются. Сервисы инжектятся через handle().
 *
 * @package App\Domains\Finances\Infrastructure\Jobs
 */
final class CalculateAllTenantsEarningsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Количество попыток.
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения (секунды).
     */
    public int $timeout = 3600;

    /**
     * @param string $correlationId Уникальный идентификатор запуска
     */
    public function __construct(
        public readonly string $correlationId = '',
    ) {
        $this->correlationId = $this->correlationId ?: Str::uuid()->toString();
        $this->onQueue('finance');
    }

    /**
     * Выполнить расчёт для всех тенантов.
     *
     * Зависимости инжектятся через handle() — это канонический
     * способ DI в Laravel Jobs.
     */
    public function handle(
        ProcessMonthlyPayoutsUseCase $useCase,
        LoggerInterface $logger,
    ): void {
        $logger->info('CalculateAllTenantsEarningsJob started', [
            'correlation_id' => $this->correlationId,
        ]);

        $processed = 0;
        $failed    = 0;

        Tenant::query()->each(function (Tenant $tenant) use ($useCase, $logger, &$processed, &$failed): void {
            try {
                $useCase->execute(
                    tenantId: $tenant->id,
                    correlationId: $this->correlationId,
                );
                $processed++;
            } catch (\Throwable $e) {
                $failed++;
                $logger->error('Failed to process monthly payout for tenant', [
                    'tenant_id'      => $tenant->id,
                    'error'          => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
            }
        });

        $logger->info('CalculateAllTenantsEarningsJob completed', [
            'processed'      => $processed,
            'failed'         => $failed,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Обработка ошибки после исчерпания попыток.
     */
    public function failed(\Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'CalculateAllTenantsEarningsJob failed permanently [correlation_id=%s]: %s',
                $this->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }
}
