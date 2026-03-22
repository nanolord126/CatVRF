<?php declare(strict_types=1);

namespace App\Livewire\Channels;

use App\Domains\Channels\Models\BusinessChannel;
use App\Domains\Channels\Models\Post;
use App\Domains\Channels\Services\ChannelSubscriptionService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * Лента новостей бизнес-канала.
 *
 * Используется на странице канала и в ЛК пользователя.
 * Поддерживает фильтрацию B2C/B2B/all.
 */
final class BusinessNewsFeed extends Component
{
    use WithPagination;

    public string $channelSlug = '';

    public string $audience = 'all';

    public bool $personalFeed = false;

    /** @var \Illuminate\Database\Eloquent\Collection|null */
    public $channel = null;

    protected $queryString = ['audience' => ['except' => 'all']];

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
        $this->audience = in_array($audience, ['b2c', 'b2b', 'all']) ? $audience : 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        if ($this->personalFeed && auth()->check()) {
            $posts = app(ChannelSubscriptionService::class)
                ->getPersonalFeed((int) auth()->id(), $this->audience, 10);
        } elseif ($this->channel !== null) {
            $posts = Post::withoutGlobalScopes()
                ->where('channel_id', $this->channel->id)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->when($this->audience !== 'all', fn ($q) => $q->whereIn('visibility', [$this->audience, 'all']))
                ->with(['media'])
                ->orderByDesc('published_at')
                ->paginate(10);
        } else {
            $posts = Post::withoutGlobalScopes()->whereRaw('1=0')->paginate(10);
        }

        return view('livewire.channels.business-news-feed', [
            'posts'   => $posts,
            'channel' => $this->channel,
        ]);
    }
}
