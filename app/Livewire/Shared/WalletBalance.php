<?php declare(strict_types=1);

namespace App\Livewire\Shared;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\WalletService;
use Illuminate\Auth\AuthManager;

/**
 * WalletBalance — текущий баланс кошелька + бонусы пользователя.
 * Обновляется при событии wallet-updated (dispatched из PaymentService / BonusService).
 * Поддерживает B2C и B2B режимы.
 *
 * @see resources/views/livewire/shared/wallet-balance.blade.php
 */
final class WalletBalance extends Component
{
    public int    $balanceKop      = 0;  // в копейках
    public int    $bonusKop        = 0;  // в копейках
    public bool   $isB2B           = false;
    public string $correlationId   = '';

    public function __construct(
        private readonly WalletService $walletService,
        private readonly AuthManager  $auth,
    ) {}

    public function mount(): void
    {
        $this->correlationId = (string) \Illuminate\Support\Str::uuid();
        $this->isB2B         = request()->has('inn') && request()->has('business_card_id');
        $this->refreshBalance();
    }

    #[On('wallet-updated')]
    public function refreshBalance(): void
    {
        $user = $this->auth->user();
        if (!$user) {
            $this->balanceKop = 0;
            $this->bonusKop   = 0;
            return;
        }

        $data = $this->walletService->getBalanceSummary($user->id, $this->correlationId);

        $this->balanceKop = (int) ($data['balance_kop'] ?? 0);
        $this->bonusKop   = (int) ($data['bonus_kop']   ?? 0);
    }

    public function render(): View
    {
        return view('livewire.shared.wallet-balance');
    }
}
