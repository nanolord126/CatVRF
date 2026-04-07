<?php declare(strict_types=1);

namespace App\Livewire\Channels;



use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Livewire\Component;
use Illuminate\Contracts\Auth\Guard;

final class ReactionPicker extends Component
{
    public function __construct(
        private readonly Request $request,
        private readonly ConfigRepository $config,
        private readonly Guard $guard,
    ) {}

    private int $postId;

        private string $postUuid;

        /** @var array{emoji: string, name: string, count: int}[] */
        private array $reactions = [];

        /** emoji → bool */
        private array $myReactions = [];

        private bool $showPicker = false;

        protected $listeners = ['reaction-updated' => 'refreshReactions'];

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

            $service = app(ReactionService::class);

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

                $updatedReactions = app(ReactionService::class)->addReaction(
                    post:        $post,
                    emoji:       $emoji,
                    userId:      $userId,
                    sessionHash: $sessionHash,
                    ipAddress:   $this->request->ip() ?? '',
                );

                $this->reactions = $updatedReactions;

                // Обновить мои реакции
                if ($userId !== null) {
                    $this->myReactions[$emoji] = !($this->myReactions[$emoji] ?? false);
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
            return view('livewire.channels.reaction-picker', [
                'allowed' => $this->config->get('channels.allowed_reactions', []),
            ]);
        }
}
