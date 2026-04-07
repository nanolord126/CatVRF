<?php declare(strict_types=1);

namespace App\Livewire\Channels;

use Livewire\Component;
use Illuminate\Contracts\Auth\Guard;

final class PostCard extends Component
{
    public function __construct(
        private readonly Guard $guard,
    ) {}

    private int $postId;

        private ?Post $post = null;

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
            $tenantId = $this->guard->user()?->current_tenant_id ?? $this->guard->id();

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
                && $this->guard->check()
                && (string) $this->guard->user()->current_tenant_id === (string) ($this->post->channel?->tenant_id ?? '');

            return view('livewire.channels.post-card', [
                'post'    => $this->post,
                'isOwner' => $isOwner,
            ]);
        }
}
