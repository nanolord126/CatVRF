<?php declare(strict_types=1);

namespace App\Livewire\Shared;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Auth\AuthManager;
use Illuminate\Notifications\DatabaseNotification;

/**
 * NotificationBell — колокольчик с непрочитанными уведомлениями.
 * Обновляется через событие notifications-updated.
 *
 * @see resources/views/livewire/shared/notification-bell.blade.php
 */
final class NotificationBell extends Component
{
    public int    $unreadCount   = 0;
    public array  $notifications = [];
    public bool   $isOpen        = false;
    public string $correlationId = '';

    public function __construct(
        private readonly AuthManager $auth,
    ) {}

    public function mount(): void
    {
        $this->correlationId = (string) \Illuminate\Support\Str::uuid();
        $this->refresh();
    }

    #[On('notifications-updated')]
    public function refresh(): void
    {
        $user = $this->auth->user();
        if (!$user) {
            $this->unreadCount   = 0;
            $this->notifications = [];
            return;
        }

        $this->unreadCount   = (int) $user->unreadNotifications()->count();
        $this->notifications = $user->notifications()
            ->latest()
            ->take(6)
            ->get()
            ->map(fn(DatabaseNotification $n) => [
                'id'       => $n->id,
                'read'     => !is_null($n->read_at),
                'type'     => $n->data['type']    ?? 'info',
                'message'  => $n->data['message'] ?? '',
                'created'  => $n->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function markAllRead(): void
    {
        $user = $this->auth->user();
        if (!$user) {
            return;
        }

        $user->unreadNotifications()->update(['read_at' => now()]);
        $this->refresh();
        $this->dispatch('notifications-updated');
    }

    public function render(): View
    {
        return view('livewire.shared.notification-bell');
    }
}
