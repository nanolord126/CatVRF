<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class AuthController extends Controller
{

    public function __construct(
        private readonly Request $request,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * Create new personal access token
         * POST /api/v1/auth/tokens
         *
         * @OA\Post(
         *     path="/api/v1/auth/tokens",
         *     operationId="createToken",
         *     tags={"Authentication"},
         *     summary="Create Personal Access Token",
         *     description="Generate a new personal access token for API authentication",
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             required={"email", "password", "name"},
         *             @OA\Property(property="email", type="string", format="email"),
         *             @OA\Property(property="password", type="string", format="password"),
         *             @OA\Property(property="name", type="string", example="My App"),
         *             @OA\Property(property="abilities", type="array", items={"type"="string"}, example={"*"})
         *         )
         *     ),
         *     @OA\Response(
         *         response=201,
         *         description="Token created successfully",
         *         @OA\JsonContent(
         *             @OA\Property(property="token", type="string"),
         *             @OA\Property(property="type", type="string", example="Bearer"),
         *             @OA\Property(property="expires_at", type="string", format="date-time"),
         *             @OA\Property(property="correlation_id", type="string")
         *         )
         *     ),
         *     @OA\Response(response=401, description="Invalid credentials"),
         *     @OA\Response(response=422, description="Validation failed"),
         *     @OA\Response(response=429, description="Too many requests")
         * )
         */
        public function store(TokenCreateRequest $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid()->toString();
            $this->fraud->check(0, 'token_store', 0, $request->ip(), null, $correlationId);
            try {
                // Validate credentials
                $user = $this->db->table('users')
                    ->where('email', $request->email)
                    ->first();
                if (!$user || !$this->hash->check($request->password, $user->password)) {
                    return $this->response->json([
                        'error' => 'Invalid credentials',
                        'correlation_id' => $correlationId,
                    ], 401);
                }
                // Create token
                $token = $user->createToken(
                    $request->name ?? 'API Token',
                    $request->abilities ?? ['*'],
                    now()->addDays((int)env('SANCTUM_EXPIRATION_DAYS', 365))
                );
                $this->logger->channel('audit')->info('Token created', [
                    'user_id' => $user->id,
                    'token_name' => $request->name,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'token' => $token->plainTextToken,
                    'type' => 'Bearer',
                    'expires_at' => $token->accessToken->expires_at,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Token creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? (string) Str::uuid()->toString(),
                ]);
                return $this->response->json([
                    'error' => 'Token creation failed',
                    'correlation_id' => $correlationId ?? Str::uuid()->toString(),
                ], 500);
            }
        }
        /**
         * Refresh personal access token
         * POST /api/v1/auth/tokens/refresh
         */
        public function refresh(TokenRefreshRequest $request): JsonResponse
        {
            try {
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
                $user = $this->guard->user();
                if (!$user) {
                    return $this->response->json([
                        'error' => 'Unauthorized',
                        'correlation_id' => $correlationId,
                    ], 401);
                }
                // Revoke old token
                $oldTokenId = $user->currentAccessToken()->id ?? null;
                if ($oldTokenId) {
                    $this->db->table('personal_access_tokens')
                        ->where('id', $oldTokenId)
                        ->update(['revoked' => true]);
                }
                // Create new token
                $newToken = $user->createToken(
                    'Refreshed Token',
                    $user->currentAccessToken()->abilities ?? ['*'],
                    now()->addDays((int)env('SANCTUM_EXPIRATION_DAYS', 365))
                );
                $this->logger->channel('audit')->info('Token refreshed', [
                    'user_id' => $user->id,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'token' => $newToken->plainTextToken,
                    'type' => 'Bearer',
                    'expires_at' => $newToken->accessToken->expires_at,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Token refresh failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? (string) Str::uuid()->toString(),
                ]);
                return $this->response->json([
                    'error' => 'Token refresh failed',
                    'correlation_id' => $correlationId ?? Str::uuid()->toString(),
                ], 500);
            }
        }
        /**
         * Revoke personal access token
         * DELETE /api/v1/auth/tokens/{id}
         */
        public function destroy(int $tokenId): JsonResponse
        {
            $correlationId = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid()->toString();
            $this->fraud->check($this->guard->id() ?? 0, 'token_destroy', 0, $this->request->ip(), null, $correlationId);
            try {
                $user = $this->guard->user();
                if (!$user) {
                    return $this->response->json([
                        'error' => 'Unauthorized',
                        'correlation_id' => $correlationId,
                    ], 401);
                }
                // Revoke token
                $this->db->table('personal_access_tokens')
                    ->where('id', $tokenId)
                    ->where('tokenable_id', $user->id)
                    ->update(['revoked' => true]);
                $this->logger->channel('audit')->info('Token revoked', [
                    'user_id' => $user->id,
                    'token_id' => $tokenId,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'message' => 'Token revoked',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Token revocation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? (string) Str::uuid()->toString(),
                ]);
                return $this->response->json([
                    'error' => 'Token revocation failed',
                    'correlation_id' => $correlationId ?? Str::uuid()->toString(),
                ], 500);
            }
        }
        /**
         * List user's tokens
         * GET /api/v1/auth/tokens
         */
        public function index(): JsonResponse
        {
            $user = $this->guard->user();
            $correlationId = $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
            if (!$user) {
                return $this->response->json([
                    'error' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 401);
            }
            $tokens = $this->db->table('personal_access_tokens')
                ->where('tokenable_id', $user->id)
                ->select('id', 'name', 'created_at', 'expires_at', 'revoked')
                ->get();
            return $this->response->json([
                'tokens' => $tokens,
                'correlation_id' => $correlationId,
            ]);
        }
}
