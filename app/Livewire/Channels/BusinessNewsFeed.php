<?php

declare(strict_types=1);

namespace App\Livewire\Channels;

use ChannelSubscriptionService;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

use Livewire\Component;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Collection;

final class BusinessNewsFeed extends Component
{
    use WithPagination;

    protected $queryString = ['audience' => ['except' => 'all']];

    private readonly string $channelSlug = '';

    private readonly string $audience = 'all';

    private readonly bool $personalFeed = false;

    /** @var Collection|null */
    private $channel = null;

    public function __construct(private readonly ChannelSubscriptionService $channelSubscriptionService,
        private readonly ViewFactory $viewFactory,
        private readonly Guard $guard,) {}

    public function mount(
        string $channelSlug = '',
        string $audience = 'all',
        bool $personalFeed = false,
    ): void {
        $this->channelSlug  = $channelSlug;
        $this->audience     = $audience;
        $this->personalFeed = $personalFeed;

        if ($channelSlug !== '') {
            $this->channel = BusinessChannel::withoutGlobalScopes()
                ->where('slug', $channelSlug)
                ->where('status', 'active')
                ->with('plan')
                ->first();
        }
    }

    public function setAudience(string $audience): void
    {
        $this->audience = in_array($audience, ['b2c', 'b2b', 'all'], true) ? $audience : 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        if ($this->personalFeed && $this->guard->check()) {
            $posts = $this->channelSubscriptionService /* TODO: inject via constructor DI */ /* TODO: inject via DI */
                ->getPersonalFeed((int) $this->guard->id(), $this->audience, 10);
        } elseif ($this->channel !== null) {
            $posts = Post::withoutGlobalScopes()
                ->where('channel_id', $this->channel->id)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', CarbonImmutable::now())
                ->when($this->audience !== 'all', fn ($q) => $q->whereIn('visibility', [$this->audience, 'all']))
                ->with(['media'])
                ->orderByDesc('published_at')
                ->paginate(10);
        } else {
            $posts = Post::withoutGlobalScopes()->whereRaw('1=0')->paginate(10);
        }

        return $this->viewFactory->make('livewire.channels.business-news-feed', [
            'posts'   => $posts,
            'channel' => $this->channel,
        ]);
    }
}
