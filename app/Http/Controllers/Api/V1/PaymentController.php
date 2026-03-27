<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;
use App\Http\Requests\PaymentInitRequest;
use App\Services\FraudControlService;
use App\Services\Security\IdempotencyService;
use App\Services\Security\RateLimiterService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;
/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment processing endpoints"
 * )
 */
final class PaymentController extends BaseApiV1Controller
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly IdempotencyService $idempotencyService,
        private readonly RateLimiterService $rateLimiterService,
    ) {
    }
    /**
     * Initialize payment
     *
     * @OA\Post(
     *     path="/v1/payments/init",
     *     tags={"Payments"},
     *     summary="Initialize a new payment",
     *     description="Creates and initializes a payment transaction with idempotency and rate limiting",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payment initialization data",
     *         @OA\JsonContent(
     *             @OA\Property(property="order_id", type="integer", example=123),
     *             @OA\Property(property="amount", type="integer", example=50000, description="Amount in kopeks"),
     *             @OA\Property(property="currency", type="string", example="RUB"),
     *             @OA\Property(property="description", type="string", example="Order payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment initialized successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="amount", type="integer", example=50000)
     *             ),
     *             @OA\Property(property="correlation_id", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Duplicate payment (idempotency key already processed)"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limit exceeded (max 10 payments/minute)"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function init(PaymentInitRequest $request): \Illuminate\Routing\ResponseFactory
    {
        $correlationId = Str::uuid()->toString();
        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                'payment_init',
                (int) $request->get('amount', 0),
                $request->ip(),
                null,
                $correlationId
            );
            Log::channel('audit')->info('PaymentController::init called', [
                'user_id' => auth()->id(),
                'amount' => $request->get('amount'),
                'correlation_id' => $correlationId,
            ]);
            // Rate limiting check
            if (!$this->rateLimiterService->checkPaymentInit(
                auth()->id() ?? 0,
                $request->ip()
            )) {
                throw new AuthorizationException('Rate limit exceeded');
            }
            // Idempotency check
            $payload = json_encode($request->only(['order_id', 'amount', 'currency']));
            $idempotencyKey = $request->header('Idempotency-Key') ?? uniqid();
            if (!$this->idempotencyService->check($idempotencyKey, $payload)) {
                return $this->respondWithError('Duplicate payment', 409);
            }
            $validated = $request->all();
            return DB::transaction(function () use ($validated, $correlationId) {
                $paymentId = \Str::uuid()->toString();
                $payment = \App\Domains\Consulting\Finances\Models\PaymentTransaction::create([
                    'uuid' => $paymentId,
                    'tenant_id' => (int) tenant('id'),
                    'correlation_id' => $correlationId,
                    'idempotency_key' => $request->header('Idempotency-Key'),
                    'amount' => ($validated['amount'] ?? null),
                    'currency' => ($validated['currency'] ?? 'RUB'),
                    'status' => 'pending',
                    'metadata' => [
                        'description' => ($validated['description'] ?? null),
                        'api_version' => 'v1',
                    ],
                ]);
                \Illuminate\Support\Facades\Log::channel('audit')->info('Payment initiated V1', [
                    'payment_id' => $payment->id,
                    'correlation_id' => $paymentId,
                ]);
                return $this->respondWithSuccess(
                    [
                        'payment_id' => $paymentId,
                        'status' => 'pending',
                        'amount' => ($validated['amount'] ?? null),
                    ],
                    'Payment initialized',
                    201
                );
            });
        } catch (AuthorizationException $e) {
            return $this->respondWithError($e->getMessage(), 429);
        }
    }
    /**
     * Получить платёж по ID
     */
    public function show(string $paymentId): \Illuminate\Routing\ResponseFactory
    {
        $correlationId = (string) \Illuminate\Support\Str::uuid()->toString();
        try {
            $payment = \App\Domains\Consulting\Finances\Models\PaymentTransaction::where('uuid', $paymentId)
                ->where('tenant_id', (int) tenant('id'))
                ->firstOrFail();
            \Illuminate\Support\Facades\Log::channel('audit')->info('Payment retrieved', [
                'payment_id' => $payment->id,
                'correlation_id' => $correlationId,
            ]);
            return $this->respondWithSuccess([
                'payment_id' => $paymentId,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'created_at' => $payment->created_at,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error('Payment retrieval failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return $this->respondWithError('Payment not found', 404);
        }
    }
}
