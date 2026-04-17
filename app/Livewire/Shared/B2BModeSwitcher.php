<?php declare(strict_types=1);

namespace App\Livewire\Shared;

use Illuminate\View\View;
use Livewire\Component;
use Illuminate\Auth\AuthManager;
use App\Models\BusinessGroup;

/**
 * B2BModeSwitcher — переключатель B2C ↔ B2B.
 * Показывается ТОЛЬКО если у пользователя есть хотя бы одна BusinessGroup.
 * Определение B2B по канону: $isB2B = request()->has('inn') && request()->has('business_card_id')
 *
 * @see resources/views/livewire/shared/b2b-mode-switcher.blade.php
 */
final class B2BModeSwitcher extends Component
{
    public bool   $isB2B           = false;
    public bool   $hasBusinessCard = false;
    public array  $businessGroups  = [];
    public int    $activeGroupId   = 0;
    public string $correlationId   = '';

    public function __construct(
        private readonly AuthManager $auth,
        private readonly LogManager $logger,
    ) {}

    public function mount(): void
    {
        $this->correlationId = (string) \Illuminate\Support\Str::uuid();

        // Определение B2B строго по канону
        $this->isB2B = request()->has('inn') && request()->has('business_card_id');

        $user = $this->auth->user();
        if (!$user) {
            return;
        }

        $groups = BusinessGroup::where('tenant_id', tenant()->id)
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->get(['id', 'legal_name', 'inn']);

        $this->hasBusinessCard = $groups->isNotEmpty();
        $this->businessGroups  = $groups->map(fn($g) => [
            'id'   => $g->id,
            'name' => $g->legal_name,
            'inn'  => $g->inn,
        ])->toArray();

        $this->activeGroupId = (int) session('active_business_group_id', 0);
    }

    public function switchToB2B(int $businessGroupId): void
    {
        $group = BusinessGroup::where('id', $businessGroupId)
            ->where('tenant_id', tenant()->id)
            ->firstOrFail();

        session(['active_business_group_id' => $group->id]);
        $this->activeGroupId = $group->id;
        $this->isB2B         = true;

        $this->logger->channel('audit')->info('User switched to B2B', [
            'user_id'           => $this->auth->id(),
            'business_group_id' => $group->id,
            'correlation_id'    => $this->correlationId,
        ]);

        $this->dispatch('mode-switched', mode: 'b2b', groupId: $group->id);
        $this->redirect(request()->url());
    }

    public function switchToB2C(): void
    {
        session()->forget('active_business_group_id');
        $this->activeGroupId = 0;
        $this->isB2B         = false;

        $this->logger->channel('audit')->info('User switched to B2C', [
            'user_id'        => $this->auth->id(),
            'correlation_id' => $this->correlationId,
        ]);

        $this->dispatch('mode-switched', mode: 'b2c');
        $this->redirect(request()->url());
    }

    public function render(): View
    {
        return view('livewire.shared.b2b-mode-switcher');
    }
}
