<?php declare(strict_types=1);

namespace App\Domains\Channels\Jobs;

use App\Domains\Channels\Models\Post;
use App\Domains\Channels\Services\PostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Публикация отложенных постов.
 *
 * Запускается каждую минуту через Scheduler.
 * Проверяет посты со статусом draft и scheduled_at <= now().
 */
final class PostSchedulerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(PostService $postService): void
    {
        $correlationId = Str::uuid()->toString();

        $due = Post::withoutGlobalScopes()
            ->where('status', 'draft')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->with('channel')
            ->get();

        if ($due->isEmpty()) {
            return;
        }

        Log::channel('audit')->info('PostSchedulerJob processing', [
            'correlation_id' => $correlationId,
            'count'          => $due->count(),
        ]);

        foreach ($due as $post) {
            try {
                $needsModeration = config('channels.moderation.enabled', true);

                if ($needsModeration) {
                    $post->update(['status' => 'pending_moderation']);
                } else {
                    $postService->publishPost($post, 'scheduler', $correlationId);
                }
            } catch (\Throwable $e) {
                Log::channel('audit')->error('PostSchedulerJob failed for post', [
                    'correlation_id' => $correlationId,
                    'post_id'        => $post->id,
                    'error'          => $e->getMessage(),
                    'trace'          => $e->getTraceAsString(),
                ]);
            }
        }
    }

    public function tags(): array
    {
        return ['channel', 'posts', 'scheduler'];
    }
}
