<?php

declare(strict_types=1);

namespace App\Livewire\User;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Auth\AuthManager;
use App\Models\User;
use App\Services\WalletService;
use App\Services\AI\AIConstructorService;
use Illuminate\View\View;
use Livewire\Component;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

/**
 * User Dashboard — главная страница личного кабинета.
 *
 * Канон:
 *  - Личный кабинет пользователя — Livewire 3 + Alpine.js + Tailwind 4 (не Filament).
 *  - Переключение B2C ↔ B2B (если есть ИНН / бизнес-карта).
 *  - Показывает: баланс кошелька, последние заказы, сохранённые AI-дизайны.
 *  - Все данные tenant-scoped.
 *  - Никаких статических вызовов — только constructor injection.
 */
final class Dashboard extends Component
{
    // ── публичные свойства ───────────────────────────────────────────────────

    public int $walletBalance    = 0;     // в копейках

    public int $walletAvailable  = 0;

    public int $bonusBalance     = 0;

    public int $ordersTotal      = 0;

    public int $aiDesignsTotal   = 0;

    public bool $isB2B            = false;

    public string $userMode         = 'b2c'; // 'b2c' | 'b2b'

    public array $recentOrders     = [];

    public array $recentDesigns    = [];

    public string $correlationId    = '';

    // ── lifecycle ───────────────────────────────────────────────────────────

    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly AuthManager $authManager,
        private readonly WalletService $wallet,
        private readonly AIConstructorService $aiConstructor,
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
        $this->loadOrders($user);
        $this->loadAiDesigns($user);
        $this->detectMode($user);
    }

    // ── публичные экшены ─────────────────────────────────────────────────────

    /**
     * Переключить режим B2C ↔ B2B.
     * Доступно только если у пользователя есть активный BusinessGroup.
     */
    public function switchMode(string $mode): void
    {
        if (! in_array($mode, ['b2c', 'b2b'], true)) {
            return;
        }

        if ($mode === 'b2b' && ! $this->isB2B) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'B2B-доступ не предоставлен.']);

            return;
        }

        $this->userMode = $mode;
        session(['user_mode' => $mode]);

        $this->dispatch('mode-switched', ['mode' => $mode]);
    }

    public function refresh(): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if ($user) {
            $this->loadWallet($user);
            $this->loadOrders($user);
            $this->loadAiDesigns($user);
        }
    }

    // ── рендер ──────────────────────────────────────────────────────────────

    public function render(): View
    {
        return $this->viewFactory->make('livewire.user.dashboard')
            ->layout('layouts.user-cabinet');
    }

    // ── приватные загрузчики ─────────────────────────────────────────────────

    private function loadWallet(User $user): void
    {
        $walletRaw = $this->db->table('wallets')
            ->where('user_id', $user->id)
            ->select('id', 'current_balance', 'hold_amount')
            ->first();

        if ($walletRaw) {
            $this->walletBalance   = (int) $walletRaw->current_balance;
            $this->walletAvailable = (int) ($walletRaw->current_balance - $walletRaw->hold_amount);
        }

        // Бонусные баллы
        $this->bonusBalance = (int) $this->db->table('bonuses')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('amount');
    }

    private function loadOrders(User $user): void
    {
        $this->ordersTotal = $this->db->table('orders')
            ->where('user_id', $user->id)
            ->count();

        $this->recentOrders = $this->db->table('orders')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->select(['id', 'uuid', 'status', 'total_kopecks', 'created_at'])
            ->get()
            ->map(fn (object $row): array => [
                'id'         => $row->id,
                'uuid'       => $row->uuid,
                'status'     => $row->status,
                'total'      => $row->total_kopecks / 100,
                'created_at' => $row->created_at,
            ])
            ->toArray();
    }

    private function loadAiDesigns(User $user): void
    {
        $this->aiDesignsTotal = $this->db->table('user_ai_designs')
            ->where('user_id', $user->id)
            ->count();

        $this->recentDesigns = $this->db->table('user_ai_designs')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(4)
            ->select(['id', 'vertical', 'created_at'])
            ->get()
            ->map(fn (object $row): array => [
                'id'       => $row->id,
                'vertical' => $row->vertical,
                'created'  => $row->created_at,
            ])
            ->toArray();
    }

    private function detectMode(User $user): void
    {
        $this->isB2B    = (bool) $this->db->table('business_groups')
            ->join('business_group_user', 'business_groups.id', '=', 'business_group_user.business_group_id')
            ->where('business_group_user.user_id', $user->id)
            ->where('business_groups.is_active', true)
            ->exists();

        $this->userMode = session('user_mode', 'b2c');
    }
}
