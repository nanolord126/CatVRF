<?php

declare(strict_types=1);


namespace App\Domains\Content\Channels\Events;

use App\Domains\Education\Channels\Models\Post;
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
