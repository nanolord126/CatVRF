<?php declare(strict_types=1);

namespace App\Livewire\Channels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReactionPicker extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public int $postId;

        public string $postUuid;

        /** @var array{emoji: string, name: string, count: int}[] */
        public array $reactions = [];

        /** emoji → bool */
        public array $myReactions = [];

        public bool $showPicker = false;

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
            if (auth()->check()) {
                foreach ($this->reactions as $r) {
                    $this->myReactions[$r['emoji']] = $service->hasReacted(
                        $post,
                        $r['emoji'],
                        (int) auth()->id(),
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
                $userId      = auth()->id() ? (int) auth()->id() : null;

                $updatedReactions = app(ReactionService::class)->addReaction(
                    post:        $post,
                    emoji:       $emoji,
                    userId:      $userId,
                    sessionHash: $sessionHash,
                    ipAddress:   request()->ip() ?? '',
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
                'allowed' => config('channels.allowed_reactions', []),
            ]);
        }
}
