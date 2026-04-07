<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Controllers;

use App\Domains\Wallet\DTOs\CreateTopUpDto;
use App\Domains\Wallet\Resources\WalletResource;
use App\Domains\Wallet\Services\WalletService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

/**
 * REST-контроллер кошелька.
 *
 * CANON 2026: final class, constructor DI (NO facades),
 * correlation_id в каждом ответе, FraudControlService::check(), AuditService::record().
 */
final class WalletController
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly ResponseFactory $response,
    ) {}

    /** GET /api/wallets/{id} — Получить кошелёк. */
    public function show(int $id, Request $request): JsonResponse
    {
        $correlationId = $this->extractCorrelationId($request);

        $wallet = $this->walletService->findById($id);

        $this->logger->info('Wallet shown', [
            'wallet_id' => $id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'data' => new WalletResource($wallet),
            'correlation_id' => $correlationId,
        ]);
    }

    /** POST /api/wallets — Создать кошелёк. */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $this->extractCorrelationId($request);

        $this->fraud->check([
            'action' => 'wallet_store',
            'tenant_id' => (int) $request->input('tenant_id'),
            'correlation_id' => $correlationId,
        ]);

        $wallet = $this->walletService->create(
            tenantId: (int) $request->input('tenant_id'),
            businessGroupId: $request->filled('business_group_id') ? (int) $request->input('business_group_id') : null,
            correlationId: $correlationId,
        );

        $this->logger->info('Wallet stored via controller', [
            'wallet_id' => $wallet->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'data' => new WalletResource($wallet),
            'correlation_id' => $correlationId,
        ], 201);
    }

    /** DELETE /api/wallets/{id} — Деактивировать кошелёк (soft). */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $correlationId = $this->extractCorrelationId($request);

        $this->fraud->check([
            'action' => 'wallet_destroy',
            'wallet_id' => $id,
            'correlation_id' => $correlationId,
        ]);

        $this->db->transaction(function () use ($id, $correlationId): void {
            $wallet = $this->walletService->findById($id);

            $wallet->is_active = false;
            $wallet->save();

            $this->audit->record(
                action: 'wallet_deactivated',
                subjectType: $wallet::class,
                subjectId: $wallet->id,
                correlationId: $correlationId,
                oldValues: ['is_active' => true],
                newValues: ['is_active' => false],
            );

            $this->logger->info('Wallet deactivated', [
                'wallet_id' => $id,
                'correlation_id' => $correlationId,
            ]);
        });

        return $this->response->json([
            'success' => true,
            'message' => 'Wallet deactivated',
            'correlation_id' => $correlationId,
        ]);
    }

    /** Извлечение correlation_id из заголовка. */
    private function extractCorrelationId(Request $request): string
    {
        return $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());
    }
}
