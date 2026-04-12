<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Payout Controller — вывод средств (на карту, СБП, банковский счёт).
 */
final class PayoutController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * POST /payouts — создание заявки на вывод.
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($request, $correlationId): JsonResponse {
                $fraudResult = $this->fraudService->scoreOperation([
                    'type' => 'payout',
                    'user_id' => auth()->id(),
                    'amount' => $request->integer('amount'),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                if (($fraudResult['decision'] ?? '') === 'block') {
                    $this->logger->channel('audit')->warning('Payout blocked by fraud', [
                        'correlation_id' => $correlationId,
                        'user_id' => auth()->id(),
                        'amount' => $request->integer('amount'),
                    ]);

                    return $this->response->json([
                        'success' => false,
                        'message' => 'Payout blocked by security check',
                        'correlation_id' => $correlationId,
                    ], 403);
                }

                $amount = $request->integer('amount');
                $userId = auth()->id();
                $tenantId = (int) $request->header('X-Tenant-ID', '0');

                $wallet = $this->db->table('wallets')
                    ->where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->first();

                if ($wallet === null) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Wallet not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $availableBalance = (int) $wallet->current_balance - (int) $wallet->hold_amount;
                if ($availableBalance < $amount) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Insufficient balance for payout',
                        'correlation_id' => $correlationId,
                        'data' => ['available_balance' => $availableBalance, 'requested' => $amount],
                    ], 422);
                }

                $payoutId = $this->db->table('payout_requests')->insertGetId([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'uuid' => Str::uuid()->toString(),
                    'amount' => $amount,
                    'destination' => $request->input('destination', 'card'),
                    'destination_id' => $request->input('destination_id', ''),
                    'status' => 'pending',
                    'idempotency_key' => $request->input('idempotency_key', Str::uuid()->toString()),
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->db->table('wallets')
                    ->where('id', $wallet->id)
                    ->update([
                        'hold_amount' => $this->db->raw("hold_amount + {$amount}"),
                        'updated_at' => now(),
                    ]);

                $this->logger->channel('audit')->info('Payout request created', [
                    'correlation_id' => $correlationId,
                    'payout_id' => $payoutId,
                    'user_id' => $userId,
                    'amount' => $amount,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Payout request submitted',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'payout_id' => $payoutId,
                        'status' => 'pending',
                        'amount' => $amount,
                    ],
                ], 201);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Payout request failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to create payout request',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /payouts/{id} — статус выплаты.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $payout = $this->db->table('payout_requests')
                ->where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if ($payout === null) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Payout not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $payout,
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Payout show failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve payout',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /payouts — список выплат пользователя.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $payouts = $this->db->table('payout_requests')
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate((int) $request->input('per_page', 20));

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $payouts->items(),
                'meta' => [
                    'current_page' => $payouts->currentPage(),
                    'last_page' => $payouts->lastPage(),
                    'total' => $payouts->total(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Payouts list failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve payouts',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
