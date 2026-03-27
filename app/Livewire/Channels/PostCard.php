<?php declare(strict_types=1);

namespace App\Livewire\Channels;

use App\Domains\Content\Channels\Models\Post;
use App\Domains\Content\Channels\Services\PostService;
use Livewire\Component;
use Illuminate\View\View;

/**
 * Карточка поста в ленте.
 *
 * Отображает: заголовок, текст, медиа, реакции, дату, тип видимости.
 * Для авторов (бизнес) — кнопки редактирования/архивации.
 */
final class PostCard extends Component
{
    public int $postId;

    public ?Post $post = null;

    public function mount(int $postId): void
    {
        $this->postId = $postId;
        $this->post   = Post::withoutGlobalScopes()
            ->where('id', $postId)
            ->with(['media', 'channel:id,name,slug,avatar_url,tenant_id'])
            ->first();
    }

    public function archivePost(): void
    {
        if ($this->post === null) {
            return;
        }

        // Проверка авторства
        $tenantId = auth()->user()?->current_tenant_id ?? auth()->id();

        if ((string) $tenantId !== (string) ($this->post->channel?->tenant_id ?? '')) {
            $this->dispatch('notify', type: 'error', message: 'Нет доступа.');
            return;
        }

        try {
            app(PostService::class)->archivePost($this->post);
            $this->dispatch('notify', type: 'info', message: 'Пост перенесён в архив.');
            $this->dispatch('post-archived', postId: $this->postId);
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function render(): View
    {
        $isOwner = $this->post !== null
            && auth()->check()
            && (string) auth()->user()->current_tenant_id === (string) ($this->post->channel?->tenant_id ?? '');

        return view('livewire.channels.post-card', [
            'post'    => $this->post,
            'isOwner' => $isOwner,
        ]);
    }
}
