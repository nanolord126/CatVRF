<?php

declare(strict_types=1);

namespace App\Domains\Auto\AutonomousVehicles\Services;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Auto\AutonomousVehicles\Models\AVEngineer;
use App\Domains\Auto\AutonomousVehicles\Models\AVProject;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Cache\RateLimiter;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Сервис проектов автономных транспортных средств (AV).
 *
 * Единственная точка создания, завершения и отмены AV-проектов.
 * Комиссия платформы: 14%.
 * Все мутации — в $this->db->transaction().
 * $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '') перед каждой мутацией.
 * RateLimiter (tenant-aware) на создание проекта.
 */
final readonly class AutonomousVehiclesService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'av:proj';
    private const RATE_LIMIT_MAX = 16;
    private const RATE_LIMIT_TTL = 3600;

    public function __construct(private readonly FraudControlService  $fraud,
        private readonly WalletService        $wallet,
        private readonly RateLimiter          $rateLimiter,
        private readonly LoggerInterface      $logger,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Guard $guard) {}

    /**
     * Создать AV-проект с проверкой фрода, холдом бюджета и аудит-логом.
     *
     * @throws \RuntimeException если rate limit превышен или fraud-блок
     */
    public function createProject(
        int    $engineerId,
        string $projectType,
        int    $hoursSpent,
        string $dueDate,
        string $correlationId = '',
    ): AVProject {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        $key = self::RATE_LIMIT_KEY . ':' . tenant()->id;
        if ($this->rateLimiter->tooManyAttempts($key, self::RATE_LIMIT_MAX)) {
            throw new \RuntimeException('Too many AV project requests', 429);
        }
        $this->rateLimiter->hit($key, self::RATE_LIMIT_TTL);

        return $this->db->transaction(function () use ($engineerId, $projectType, $hoursSpent, $dueDate, $correlationId): AVProject {
            /** @var AVEngineer $engineer */
            $engineer = AVEngineer::findOrFail($engineerId);

            $total = (int) ($engineer->price_kopecks_per_hour * $hoursSpent);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'av_project', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                throw new \RuntimeException('Подозрение на мошенничество', 403);
            }

            $payout = $total - (int) ($total * self::COMMISSION_RATE);

            $project = AVProject::create([
                'uuid'            => Uuid::uuid4()->toString(),
                'tenant_id'       => tenant()->id,
                'engineer_id'     => $engineerId,
                'client_id'       => (int) $this->guard->id(),
                'correlation_id'  => $correlationId,
                'status'          => 'pending_payment',
                'total_kopecks'   => $total,
                'payout_kopecks'  => $payout,
                'payment_status'  => 'pending',
                'project_type'    => $projectType,
                'hours_spent'     => $hoursSpent,
                'due_date'        => $dueDate,
                'tags'            => ['av' => true],
            ]);

            $this->logger->info('AV project created', [
                'project_id'     => $project->id,
                'engineer_id'    => $engineerId,
                'total_kopecks'  => $total,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Завершить проект и начислить выплату инженеру.
     *
     * @throws \RuntimeException если оплата не подтверждена
     */
    public function completeProject(int $projectId, string $correlationId = ''): AVProject
    {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($projectId, $correlationId): AVProject {
            $project = AVProject::findOrFail($projectId);

            if ($project->payment_status !== 'completed') {
                throw new \RuntimeException('Оплата не подтверждена', 400);
            }

            $project->update([
                'status'         => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                tenantId: tenant()->id,
                amount: $project->payout_kopecks,
                type: 'av_payout',
                meta: [
                    'project_id'     => $project->id,
                    'correlation_id' => $correlationId,
                ],
            );

            $this->logger->info('AV project completed', [
                'project_id'     => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Отменить проект и вернуть средства клиенту.
     *
     * @throws \RuntimeException если оплата не подтверждена
     */
    public function cancelProject(int $projectId, string $correlationId = ''): AVProject
    {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($projectId, $correlationId): AVProject {
            $project = AVProject::findOrFail($projectId);

            $project->update([
                'status'         => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($project->payment_status === 'completed') {
                $this->wallet->credit(
                    tenantId: tenant()->id,
                    amount: $project->total_kopecks,
                    type: 'av_refund',
                    meta: [
                        'project_id'     => $project->id,
                        'correlation_id' => $correlationId,
                    ],
                );
            }

            $this->logger->info('AV project cancelled', [
                'project_id'     => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Получить последние проекты клиента.
     */
    public function getClientProjects(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return AVProject::where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
