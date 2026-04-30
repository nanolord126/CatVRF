<?php

declare(strict_types=1);

namespace App\Livewire\Channels;

use ChannelSubscriptionService;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;
use Illuminate\Contracts\Auth\Guard;

final class FollowButton extends Component
{
    protected $listeners = ['channel-subscribed' => '$refresh'];

    private readonly string $channelSlug;

    private readonly string $channelName;

    private readonly bool $isSubscribed = false;

    private readonly bool $loading = false;

    public function __construct(private readonly ChannelSubscriptionService $channelSubscriptionService,
        private readonly ViewFactory $viewFactory,
        private readonly Guard $guard,) {}

    public function mount(string $channelSlug, string $channelName): void
    {
        $this->channelSlug = $channelSlug;
        $this->channelName = $channelName;

        if ($this->guard->check()) {
            $channel = BusinessChannel::withoutGlobalScopes()
                ->where('slug', $channelSlug)
                ->first();

            if ($channel !== null) {
                $this->isSubscribed = $this->channelSubscriptionService /* TODO: inject via constructor DI */ /* TODO: inject via DI */
                    ->isSubscribed((int) $this->guard->id(), $channel->id);
            }
        }
    }

    public function toggle(): void
    {
        if (! $this->guard->check()) {
            $this->redirect(route('login'));

            return;
        }

        $this->loading = true;

        try {
            $channel = BusinessChannel::withoutGlobalScopes()
                ->where('slug', $this->channelSlug)
                ->where('status', 'active')
                ->firstOrFail();

            $service = $this->channelSubscriptionService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;

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
        return $this->viewFactory->make('livewire.channels.follow-button');
    }
}
