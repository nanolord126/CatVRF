<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Jobs;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

final class PostSchedulerJob
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;

        public function handle(PostService $postService): void
        {
            $correlationId = Str::uuid()->toString();

            $due = Post::withoutGlobalScopes()
                ->where('status', 'draft')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<=', Carbon::now())
                ->with('channel')
                ->get();

            if ($due->isEmpty()) {
                return;
            }

            $this->logger->info('PostSchedulerJob processing', [
                'correlation_id' => $correlationId,
                'count'          => $due->count(),
            ]);

            foreach ($due as $post) {
                try {
                    $needsModeration = $this->config->get('channels.moderation.enabled', true);

                    if ($needsModeration) {
                        $post->update(['status' => 'pending_moderation']);
                    } else {
                        $postService->publishPost($post, 'scheduler', $correlationId);
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('PostSchedulerJob failed for post', [
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
