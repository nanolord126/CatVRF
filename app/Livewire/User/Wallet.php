<?php

declare(strict_types=1);

namespace App\Livewire\User;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Auth\AuthManager;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Illuminate\Database\DatabaseManager;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Wallet — Livewire-компонент кошелька и бонусов пользователя.
 *
 * Канон:
 *  - Баланс кошелька (current_balance, hold_amount, available).
 *  - Список транзакций из balance_transactions с пагинацией.
 *  - Бонусные рубли (bonuses + bonus_transactions).
 *  - B2B: оптовый кошелёк + кредитный лимит + возможность вывода бонусов.
 *  - Никаких прямых мутаций — только через WalletService.
 */
final class Wallet extends Component
{
    // ── публичные свойства ───────────────────────────────────────────────────

    public int $walletId        = 0;

    public int $currentBalance  = 0;   // в копейках

    public int $holdAmount      = 0;

    public int $available       = 0;

    public int $bonusBalance    = 0;

    public string $activeTab       = 'money';    // 'money' | 'bonuses'

    public string $filterType      = 'all';      // 'all' | 'deposit' | 'withdrawal' | 'bonus' | 'payout'

    public bool $isB2B           = false;

    public int $creditLimit     = 0;

    public int $creditUsed      = 0;

    public string $correlationId   = '';

    // ── lifecycle ───────────────────────────────────────────────────────────

    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly AuthManager $authManager,
        private readonly WalletService $walletService,
        private readonly DatabaseManager $db,) {}

    public function mount(): void
    {
        $this->correlationId = (string) Str::uuid();

        /** @var User $user */
        $user = $this->authManager->user();
        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        $this->loadWallet($user);
        $this->detectB2B($user);
    }

    // ── публичные экшены ─────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $this->activeTab  = in_array($tab, ['money', 'bonuses'], true) ? $tab : 'money';
        $this->resetPage();
    }

    public function setFilter(string $type): void
    {
        $allowed = ['all', 'deposit', 'withdrawal', 'bonus', 'payout', 'commission', 'refund'];
        $this->filterType = in_array($type, $allowed, true) ? $type : 'all';
        $this->resetPage();
    }

    public function refresh(): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if ($user) {
            $this->loadWallet($user);
        }
    }

    // ── геттеры для view ─────────────────────────────────────────────────────

    /** Список транзакций с пагинацией и фильтром. */
    public function getTransactions(): LengthAwarePaginator
    {
        $query = $this->db->table('balance_transactions')
            ->where('wallet_id', $this->walletId)
            ->orderByDesc('created_at');

        if ($this->activeTab === 'bonuses') {
            $query = $this->db->table('bonus_transactions')
                ->where('user_id', $this->authManager->id())
                ->orderByDesc('created_at');
        } elseif ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        return $query
            ->select('*')
            ->paginate(15);
    }

    // ── рендер ──────────────────────────────────────────────────────────────

    public function render(): View
    {
        return $this->viewFactory->make('livewire.user.wallet', [
            'transactions' => $this->getTransactions(),
        ])->layout('layouts.user-cabinet');
    }

    // ── приватные методы ─────────────────────────────────────────────────────

    private function loadWallet(User $user): void
    {
        $walletRow = $this->db->table('wallets')
            ->where('user_id', $user->id)
            ->select('id', 'current_balance', 'hold_amount')
            ->first();

        if ($walletRow) {
            $this->walletId       = (int) $walletRow->id;
            $this->currentBalance = (int) $walletRow->current_balance;
            $this->holdAmount     = (int) $walletRow->hold_amount;
            $this->available      = $this->currentBalance - $this->holdAmount;
        }

        $this->bonusBalance = (int) $this->db->table('bonuses')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('amount');
    }

    private function detectB2B(User $user): void
    {
        $group = $this->db->table('business_groups')
            ->join('business_group_user', 'business_groups.id', '=', 'business_group_user.business_group_id')
            ->where('business_group_user.user_id', $user->id)
            ->where('business_groups.is_active', true)
            ->select('business_groups.credit_limit', 'business_groups.credit_used')
            ->first();

        if ($group) {
            $this->isB2B       = true;
            $this->creditLimit = (int) $group->credit_limit;
            $this->creditUsed  = (int) $group->credit_used;
        }
    }
}
