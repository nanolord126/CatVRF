<?php declare(strict_types=1);

namespace App\Domains\Channels\Events;

use App\Domains\Channels\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: пост опубликован.
 * Запускает уведомления подписчикам канала.
 */
final class PostPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Post $post,
        public readonly string $correlationId,
    ) {}
}
