<?php declare(strict_types=1);

namespace App\Livewire\Channels;

use App\Domains\Content\Channels\Models\BusinessChannel;
use App\Domains\Content\Channels\Services\ChannelSubscriptionService;
use Livewire\Component;
use Illuminate\View\View;

/**
 * Кнопка подписки на канал бизнеса.
 *
 * Показывается у аватара бизнеса в карточках товаров/услуг и в ЛК.
 * Для неавторизованных — редирект на логин.
 */
final class FollowButton extends Component
{
    public string $channelSlug;

    public string $channelName;

    public bool $isSubscribed = false;

    public bool $loading = false;

    protected $listeners = ['channel-subscribed' => '$refresh'];

    public function mount(string $channelSlug, string $channelName): void
    {
        $this->channelSlug = $channelSlug;
        $this->channelName = $channelName;

        if (auth()->check()) {
            $channel = BusinessChannel::withoutGlobalScopes()
                ->where('slug', $channelSlug)
                ->first();

            if ($channel !== null) {
                $this->isSubscribed = app(ChannelSubscriptionService::class)
                    ->isSubscribed((int) auth()->id(), $channel->id);
            }
        }
    }

    public function toggle(): void
    {
        if (!auth()->check()) {
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
                $service->unsubscribe((int) auth()->id(), $channel);
                $this->isSubscribed = false;
                $this->dispatch('notify', type: 'info', message: "Вы отписались от «{$this->channelName}»");
            } else {
                $service->subscribe((int) auth()->id(), $channel);
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
