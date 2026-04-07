<?php declare(strict_types=1);

namespace App\Livewire\Channels;

use Livewire\Component;
use Illuminate\Contracts\Auth\Guard;

final class FollowButton extends Component
{
    public function __construct(
        private readonly Guard $guard,
    ) {}

    private string $channelSlug;

        private string $channelName;

        private bool $isSubscribed = false;

        private bool $loading = false;

        protected $listeners = ['channel-subscribed' => '$refresh'];

        public function mount(string $channelSlug, string $channelName): void
        {
            $this->channelSlug = $channelSlug;
            $this->channelName = $channelName;

            if ($this->guard->check()) {
                $channel = BusinessChannel::withoutGlobalScopes()
                    ->where('slug', $channelSlug)
                    ->first();

                if ($channel !== null) {
                    $this->isSubscribed = app(ChannelSubscriptionService::class)
                        ->isSubscribed((int) $this->guard->id(), $channel->id);
                }
            }
        }

        public function toggle(): void
        {
            if (!$this->guard->check()) {
                $this->redirect(route('login'));
                return;
            }

            $this->loading = true;

            try {
                $channel = BusinessChannel::withoutGlobalScopes()
                    ->where('slug', $this->channelSlug)
                    ->where('status', 'active')
                    ->firstOrFail();

                $service = app(ChannelSubscriptionService::class);

                if ($this->isSubscribed) {
                    $service->unsubscribe((int) $this->guard->id(), $channel);
                    $this->isSubscribed = false;
                    $this->dispatch('notify', type: 'info', message: "Вы отписались от «{$this->channelName}»");
                } else {
                    $service->subscribe((int) $this->guard->id(), $channel);
                    $this->isSubscribed = true;
                    $this->dispatch('notify', type: 'success', message: "Вы подписались на «{$this->channelName}»");
                }
            } catch (\Throwable $e) {
                $this->dispatch('notify', type: 'error', message: $e->getMessage());
            } finally {
                $this->loading = false;
            }
        }

        public function render(): View
        {
            return view('livewire.channels.follow-button');
        }
}
