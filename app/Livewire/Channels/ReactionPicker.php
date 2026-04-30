<?php

declare(strict_types=1);

namespace App\Livewire\Channels;

use ReactionService;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Livewire\Component;
use Illuminate\Contracts\Auth\Guard;

final class ReactionPicker extends Component
{
    protected $listeners = ['reaction-updated' => 'refreshReactions'];

    private readonly int $postId;

    private readonly string $postUuid;

    /** @var array{emoji: string, name: string, count: int}[] */
    private readonly array $reactions = [];

    /** emoji → bool */
    private readonly array $myReactions = [];

    private readonly bool $showPicker = false;

    public function __construct(private readonly ReactionService $reactionService,
        private readonly ViewFactory $viewFactory,
        private readonly Request $request,
        private readonly ConfigRepository $config,
        private readonly Guard $guard,) {}

    public function mount(int $postId, string $postUuid): void
    {
        $this->postId   = $postId;
        $this->postUuid = $postUuid;

        $this->loadReactions();
    }

    public function loadReactions(): void
    {
        $post = Post::withoutGlobalScopes()
            ->where('id', $this->postId)
            ->first();

        if ($post === null) {
            return;
        }

        $service = $this->reactionService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;

        $this->reactions = $service->getReactions($post);

        // Реакции текущего пользователя
        if ($this->guard->check()) {
            foreach ($this->reactions as $r) {
                $this->myReactions[$r['emoji']] = $service->hasReacted(
                    $post,
                    $r['emoji'],
                    (int) $this->guard->id(),
                    ''
                );
            }
        }
    }

    public function react(string $emoji): void
    {
        try {
            $post = Post::withoutGlobalScopes()
                ->where('id', $this->postId)
                ->where('status', 'published')
                ->firstOrFail();

            $sessionHash = session()->getId();
            $userId      = $this->guard->id() ? (int) $this->guard->id() : null;

            $updatedReactions = $this->reactionService /* TODO: inject via constructor DI */ /* TODO: inject via DI */->addReaction(
                post:        $post,
                emoji:       $emoji,
                userId:      $userId,
                sessionHash: $sessionHash,
                ipAddress:   $this->request->ip() ?? '',
            );

            $this->reactions = $updatedReactions;

            // Обновить мои реакции
            if ($userId !== null) {
                $this->myReactions[$emoji] = ! ($this->myReactions[$emoji] ?? false);
            }

        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function refreshReactions(): void
    {
        $this->loadReactions();
    }

    public function render(): View
    {
        return $this->viewFactory->make('livewire.channels.reaction-picker', [
            'allowed' => $this->config->get('channels.allowed_reactions', []),
        ]);
    }
}
