<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Channels;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;

final class ReactionController extends Controller
{
    public function __construct(
        private readonly ConfigRepository $config,
            private readonly ReactionService $reactionService,
            private readonly ResponseFactory $response,
    ) {}
        /** Поставить / убрать реакцию (toggle) */
        public function react(Request $request, string $postUuid): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
            $validated = $request->validate([
                'emoji' => ['required', 'string'],
            ]);
            try {
                $post = Post::withoutGlobalScopes()
                    ->where('uuid', $postUuid)
                    ->where('status', 'published')
                    ->firstOrFail();
                $userId      = $request->user()?->id;
                $sessionHash = $request->cookie('_session_hash') ?? md5($request->ip() . $request->userAgent());
                $reactions = $this->reactionService->addReaction(
                    post:          $post,
                    emoji:         $validated['emoji'],
                    userId:        $userId ? (int) $userId : null,
                    sessionHash:   $sessionHash,
                    ipAddress:     $request->ip() ?? '',
                    correlationId: $correlationId,
                );
                return $this->response->json([
                    'success'        => true,
                    'data'           => $reactions,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\InvalidArgumentException $e) {
                return $this->response->json([
                    'success'        => false,
                    'error'          => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'allowed_reactions' => $this->config->get('channels.allowed_reactions', []),
                ], 422);
            } catch (\Throwable $e) {
                return $this->errorResponse($e, $correlationId, 429);
            }
        }
        /** Получить список реакций поста */
        public function index(Request $request, string $postUuid): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
            try {
                $post = Post::withoutGlobalScopes()
                    ->where('uuid', $postUuid)
                    ->where('status', 'published')
                    ->firstOrFail();
                $reactions = $this->reactionService->getReactions($post);
                // Проверить, поставил ли текущий пользователь реакцию
                $userReactions = [];
                if ($request->user() !== null) {
                    foreach (array_column($reactions, 'emoji') as $emoji) {
                        $userReactions[$emoji] = $this->reactionService->hasReacted(
                            $post,
                            $emoji,
                            (int) $request->user()->id,
                            ''
                        );
                    }
                }
                return $this->response->json([
                    'success'         => true,
                    'data'            => $reactions,
                    'my_reactions'    => $userReactions,
                    'allowed'         => $this->config->get('channels.allowed_reactions', []),
                    'correlation_id'  => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return $this->errorResponse($e, $correlationId, 404);
            }
        }
}
