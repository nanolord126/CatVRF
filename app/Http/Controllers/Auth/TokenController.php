<?php declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class TokenController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * Создать новый token
         */
        public function create(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            $this->fraud->check(0, 'token_create', 0, $request->ip(), null, $correlationId);
            try {
                $validated = $request->validate([
                    'email' => 'required|email',
                    'password' => 'required|string|min:6',
                ]);
                $user = \App\Models\User::where('email', $validated['email'])->first();
                if (!$user || !$this->hash->check($validated['password'], $user->password)) {
                    $this->logger->channel('audit')->warning('Invalid token creation credentials', [
                        'email' => $validated['email'],
                        'correlation_id' => $correlationId,
                    ]);
                    return $this->response->json([
                        'error' => 'Invalid credentials',
                        'correlation_id' => $correlationId,
                    ], 401);
                }
                return $this->db->transaction(function () use ($user, $correlationId) {
                    $token = $user->createToken(
                        name: 'API Token',
                        abilities: ['*'],
                        expiresAt: now()->addDays(365)
                    );
                    $this->logger->channel('audit')->info('Token created', [
                        'user_id' => $user->id,
                        'correlation_id' => $correlationId,
                    ]);
                    return $this->response->json([
                        'token' => $token->plainTextToken,
                        'type' => 'Bearer',
                        'expires_in' => 86400,
                        'correlation_id' => $correlationId,
                    ], 201);
                });
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Token creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'error' => 'Token creation failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Обновить token
         */
        public function refresh(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            try {
                $user = $request->user();
                if (!$user) {
                    return $this->response->json([
                        'error' => 'Unauthorized',
                        'correlation_id' => $correlationId,
                    ], 401);
                }
                return $this->db->transaction(function () use ($user, $correlationId) {
                    $user->tokens()->delete();
                    $token = $user->createToken('API Token Refreshed');
                    $this->logger->channel('audit')->info('Token refreshed', [
                        'user_id' => $user->id,
                        'correlation_id' => $correlationId,
                    ]);
                    return $this->response->json([
                        'token' => $token->plainTextToken,
                        'type' => 'Bearer',
                        'correlation_id' => $correlationId,
                    ], 200);
                });
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Token refresh failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'error' => 'Token refresh failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
