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
 * Payment Method Controller — управление платёжными методами (карты, СБП).
 */
final class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * POST /payment-methods/bind-card — привязка карты.
     */
    public function bindCard(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($request, $correlationId): JsonResponse {
                $fraudResult = $this->fraudService->scoreOperation([
                    'type' => 'card_bind',
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                if (($fraudResult['decision'] ?? '') === 'block') {
                    $this->logger->channel('audit')->warning('Card bind blocked by fraud', [
                        'correlation_id' => $correlationId,
                        'user_id' => auth()->id(),
                    ]);

                    return $this->response->json([
                        'success' => false,
                        'message' => 'Card binding blocked by security check',
                        'correlation_id' => $correlationId,
                    ], 403);
                }

                $methodId = $this->db->table('payment_methods')->insertGetId([
                    'user_id' => auth()->id(),
                    'tenant_id' => (int) $request->header('X-Tenant-ID', '0'),
                    'uuid' => Str::uuid()->toString(),
                    'type' => 'card',
                    'masked_pan' => $request->input('masked_pan', '****'),
                    'card_brand' => $request->input('card_brand', 'unknown'),
                    'expires_at' => $request->input('expires_at'),
                    'is_default' => $this->db->table('payment_methods')->where('user_id', auth()->id())->count() === 0,
                    'status' => 'pending_3ds',
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->logger->channel('audit')->info('Card bind initiated', [
                    'correlation_id' => $correlationId,
                    'method_id' => $methodId,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Card binding initiated, verify with 3DS',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'method_id' => $methodId,
                        'status' => 'pending_3ds',
                    ],
                ], 201);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Card bind failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to bind card',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /payment-methods/{id}/verify-3ds — подтверждение 3DS.
     */
    public function verify3DS(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($id, $request, $correlationId): JsonResponse {
                $method = $this->db->table('payment_methods')
                    ->where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first();

                if ($method === null) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Payment method not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                if ($method->status !== 'pending_3ds') {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Payment method is not pending 3DS verification',
                        'correlation_id' => $correlationId,
                    ], 409);
                }

                $this->db->table('payment_methods')
                    ->where('id', $id)
                    ->update([
                        'status' => 'active',
                        'correlation_id' => $correlationId,
                        'updated_at' => now(),
                    ]);

                $this->logger->channel('audit')->info('Card 3DS verified', [
                    'correlation_id' => $correlationId,
                    'method_id' => $id,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Card verified via 3DS',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('3DS verification failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to verify 3DS',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * DELETE /payment-methods/{id} — удаление платёжного метода.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($id, $correlationId): JsonResponse {
                $deleted = $this->db->table('payment_methods')
                    ->where('id', $id)
                    ->where('user_id', auth()->id())
                    ->delete();

                if ($deleted === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Payment method not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Payment method removed', [
                    'correlation_id' => $correlationId,
                    'method_id' => $id,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Payment method removed',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Payment method removal failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to remove payment method',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /payment-methods — список платёжных методов пользователя.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $methods = $this->db->table('payment_methods')
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $methods,
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Payment methods list failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve payment methods',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
