<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PostSchedulerJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
