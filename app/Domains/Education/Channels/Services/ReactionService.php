<?php declare(strict_types=1);

namespace App\Domains\Content\Channels\Services;

use App\Domains\Education\Channels\Models\Post;
use App\Domains\Education\Channels\Models\PostReactionLog;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Управление реакциями на посты.
 *
 * Разрешённые emoji по нормам РФ — из config('channels.allowed_reactions').
 * Реакции анонимны для пользователей, видны владельцу бизнеса в статистике.
 * FraudMLService проверяет накрутку реакций.
 */
final class ReactionService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    /**
     * Добавить реакцию на пост.
     *
     * @param int|null    $userId       Авторизованный пользователь (null = анонимно)
     * @param string      $sessionHash  Хэш сессии (для анонимов)
     * @param string      $ipAddress    IP адрес клиента
     * @throws \InvalidArgumentException Запрещённый emoji
     * @throws \RuntimeException         Фрод или rate-limit
     */
    public function addReaction(
        Post $post,
        string $emoji,
        ?int $userId = null,
        string $sessionHash = '',
        string $ipAddress = '',
        string $correlationId = '',
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        // Проверить разрешённый emoji
        $allowed = config('channels.allowed_reactions', []);
        if (!array_key_exists($emoji, $allowed)) {
            throw new \InvalidArgumentException(
                "Реакция «{$emoji}» недоступна. Используйте только разрешённые смайлы."
            );
        }

        // Rate limit: 30 реакций/мин на пользователя/сессию
        $limitKey = $userId
            ? "reaction_user:{$userId}"
            : "reaction_session:{$sessionHash}";

        $maxAttempts = config('channels.limits.reactions_per_minute', 30);

        if (RateLimiter::tooManyAttempts($limitKey, $maxAttempts)) {
            throw new \RuntimeException('Слишком много реакций. Подождите немного.');
        }
        RateLimiter::hit($limitKey, 60);

        // Проверить, не ставил ли уже эту реакцию
        $alreadyReacted = $this->hasReacted($post, $emoji, $userId, $sessionHash);

        if ($alreadyReacted) {
            // Убрать реакцию (toggle)
            return $this->removeReaction($post, $emoji, $userId, $sessionHash, $ipAddress, $correlationId);
        }

        // Fraud check (накрутка реакций)
        $fraudScore = $this->calculateFraudScore($post, $userId, $sessionHash, $ipAddress);

        if ($fraudScore > 0.8) {
            Log::channel('fraud_alert')->warning('Reaction fraud detected', [
                'correlation_id' => $correlationId,
                'post_id'        => $post->id,
                'user_id'        => $userId,
                'emoji'          => $emoji,
                'score'          => $fraudScore,
            ]);

            throw new \RuntimeException('Реакция заблокирована системой безопасности.');
        }

        DB::transaction(function () use ($post, $emoji, $userId, $sessionHash, $ipAddress, $fraudScore, $correlationId): void {
            // Записать лог реакции
            PostReactionLog::create([
                'post_id'        => $post->id,
                'tenant_id'      => $post->tenant_id,
                'user_id'        => $userId,
                'session_hash'   => $sessionHash ?: Str::uuid()->toString(),
                'ip_address'     => $ipAddress,
                'emoji'          => $emoji,
                'action'         => 'add',
                'fraud_score'    => $fraudScore,
                'correlation_id' => $correlationId,
                'reacted_at'     => now(),
            ]);

            // Обновить JSON reactions в посте (атомарно через DB JSON_SET)
            $current = $post->reactions ?? [];
            $current[$emoji] = (int) ($current[$emoji] ?? 0) + 1;

            DB::table('posts')
                ->where('id', $post->id)
                ->update([
                    'reactions'       => json_encode($current),
                    'reactions_count' => DB::raw('reactions_count + 1'),
                ]);

            // Обновить daily-статистику
            DB::table('post_stats_daily')->updateOrInsert(
                ['post_id' => $post->id, 'stat_date' => today()],
                [
                    'tenant_id'       => $post->tenant_id,
                    'reactions_total' => DB::raw('reactions_total + 1'),
                    'updated_at'      => now(),
                ]
            );
        });

        // Инвалидировать кэш поста
        Cache::forget("post:{$post->id}");

        return $this->getReactions($post->refresh());
    }

    /**
     * Убрать реакцию.
     */
    public function removeReaction(
        Post $post,
        string $emoji,
        ?int $userId = null,
        string $sessionHash = '',
        string $ipAddress = '',
        string $correlationId = '',
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        DB::transaction(function () use ($post, $emoji, $userId, $sessionHash, $ipAddress, $correlationId): void {
            PostReactionLog::create([
                'post_id'        => $post->id,
                'tenant_id'      => $post->tenant_id,
                'user_id'        => $userId,
                'session_hash'   => $sessionHash,
                'ip_address'     => $ipAddress,
                'emoji'          => $emoji,
                'action'         => 'remove',
                'fraud_score'    => 0.0,
                'correlation_id' => $correlationId,
                'reacted_at'     => now(),
            ]);

            $current = $post->reactions ?? [];
            $val     = max(0, (int) ($current[$emoji] ?? 0) - 1);

            if ($val === 0) {
                unset($current[$emoji]);
            } else {
                $current[$emoji] = $val;
            }

            DB::table('posts')
                ->where('id', $post->id)
                ->update([
                    'reactions'       => json_encode($current),
                    'reactions_count' => DB::raw('GREATEST(reactions_count - 1, 0)'),
                ]);
        });

        Cache::forget("post:{$post->id}");

        return $this->getReactions($post->refresh());
    }

    /**
     * Получить текущие реакции поста в публичном формате.
     *
     * @return array{emoji: string, name: string, count: int, allowed: bool}[]
     */
    public function getReactions(Post $post): array
    {
        $allowed   = config('channels.allowed_reactions', []);
        $reactions = $post->reactions ?? [];
        $result    = [];

        foreach ($allowed as $emoji => $name) {
            $count = (int) ($reactions[$emoji] ?? 0);
            if ($count > 0) {
                $result[] = [
                    'emoji'   => $emoji,
                    'name'    => $name,
                    'count'   => $count,
                    'allowed' => true,
                ];
            }
        }

        return $result;
    }

    /**
     * Проверить, поставил ли уже пользователь/сессия данную реакцию.
     */
    public function hasReacted(Post $post, string $emoji, ?int $userId, string $sessionHash): bool
    {
        return PostReactionLog::where('post_id', $post->id)
            ->where('emoji', $emoji)
            ->where(function ($q) use ($userId, $sessionHash): void {
                if ($userId !== null) {
                    $q->where('user_id', $userId);
                } elseif ($sessionHash !== '') {
                    $q->where('session_hash', $sessionHash);
                }
            })
            ->whereDate('reacted_at', today())
            ->where('action', 'add')
            ->exists();
    }

    /**
     * Рассчитать fraud-скор для реакции.
     */
    private function calculateFraudScore(Post $post, ?int $userId, string $sessionHash, string $ipAddress): float
    {
        $score = 0.0;

        // 1. Много реакций с одного IP за 1 час
        $recentFromIp = PostReactionLog::where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentFromIp > 50) {
            $score += 0.5;
        } elseif ($recentFromIp > 20) {
            $score += 0.2;
        }

        // 2. Много реакций на один пост за 5 минут
        $recentOnPost = PostReactionLog::where('post_id', $post->id)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentOnPost > 5) {
            $score += 0.4;
        }

        // 3. Новый пользователь/сессия — первые 10 минут
        if ($userId === null && $sessionHash !== '') {
            $firstActivity = PostReactionLog::where('session_hash', $sessionHash)
                ->orderBy('reacted_at')
                ->first();

            if ($firstActivity !== null && $firstActivity->reacted_at->diffInMinutes(now()) < 10) {
                $score += 0.1;
            }
        }

        return min($score, 1.0);
    }
}
