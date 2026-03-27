<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\Channels;
use App\Domains\Content\Channels\Models\Post;
use App\Domains\Content\Channels\Services\ReactionService;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
/**
 * API реакций на посты.
 *
 * POST /api/v1/posts/{uuid}/react  — поставить/убрать реакцию (toggle)
 * GET  /api/v1/posts/{uuid}/react  — получить реакции поста
 */
final class ReactionController extends BaseApiV1Controller
{
    public function __construct(
        private readonly ReactionService $reactionService,
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
            return response()->json([
                'success'        => true,
                'data'           => $reactions,
                'correlation_id' => $correlationId,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success'        => false,
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
                'allowed_reactions' => config('channels.allowed_reactions', []),
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
            return response()->json([
                'success'         => true,
                'data'            => $reactions,
                'my_reactions'    => $userReactions,
                'allowed'         => config('channels.allowed_reactions', []),
                'correlation_id'  => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId, 404);
        }
    }
}
