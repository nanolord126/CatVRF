<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Livewire;

use Livewire\Component;
use Modules\BeautyMasters\Application\Services\BonusService;
use Modules\BeautyMasters\Infrastructure\Models\LoyaltyProfileModel;
use Modules\BeautyMasters\Infrastructure\Models\LoyaltyTransactionModel;
use Illuminate\Support\Facades\Cache;

final class BonusDashboard extends Component
{
    public int $venueId;
    public array $stats = [];
    public array $topUsers = [];
    public array $tierDistribution = [];
    public array $recentTransactions = [];
    public string $selectedTier = 'all';

    protected $bonusService;

    public function mount(int $venueId, BonusService $bonusService): void
    {
        $this->venueId = $venueId;
        $this->bonusService = $bonusService;
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loadStats();
        $this->loadTopUsers();
        $this->loadTierDistribution();
        $this->loadRecentTransactions();
    }

    private function loadStats(): void
    {
        $cacheKey = "beauty:bonus_dashboard_stats:{$this->venueId}";

        $this->stats = Cache::remember($cacheKey, now()->addMinutes(15), function () {
            $profiles = LoyaltyProfileModel::where('venue_id', $this->venueId)->get();

            return [
                'total_users' => $profiles->count(),
                'total_points_issued' => $profiles->sum('points_earned'),
                'total_points_redeemed' => $profiles->sum('points_redeemed'),
                'total_points_balance' => $profiles->sum('points_balance'),
                'total_spent' => $profiles->sum('total_spent'),
                'average_points_per_user' => $profiles->avg('points_balance'),
                'platinum_users' => $profiles->where('tier', 'platinum')->count(),
                'gold_users' => $profiles->where('tier', 'gold')->count(),
                'silver_users' => $profiles->where('tier', 'silver')->count(),
                'bronze_users' => $profiles->where('tier', 'bronze')->count(),
            ];
        });
    }

    private function loadTopUsers(): void
    {
        $cacheKey = "beauty:bonus_dashboard_top:{$this->venueId}";

        $this->topUsers = Cache::remember($cacheKey, now()->addMinutes(30), function () {
            return LoyaltyProfileModel::where('venue_id', $this->venueId)
                ->with('client')
                ->orderBy('points_balance', 'desc')
                ->limit(10)
                ->get()
                ->map(fn ($profile) => [
                    'user_id' => $profile->client_id,
                    'user_name' => $profile->client->full_name,
                    'points_balance' => $profile->points_balance,
                    'tier' => $profile->tier,
                    'tier_label' => $this->getTierLabel($profile->tier),
                    'total_spent' => $profile->total_spent,
                    'total_visits' => $profile->total_visits,
                ])
                ->toArray();
        });
    }

    private function loadTierDistribution(): void
    {
        $cacheKey = "beauty:bonus_dashboard_tiers:{$this->venueId}";

        $this->tierDistribution = Cache::remember($cacheKey, now()->addMinutes(30), function () {
            $profiles = LoyaltyProfileModel::where('venue_id', $this->venueId)->get();

            return [
                'bronze' => [
                    'count' => $profiles->where('tier', 'bronze')->count(),
                    'percentage' => $profiles->count() > 0 
                        ? round(($profiles->where('tier', 'bronze')->count() / $profiles->count()) * 100, 1)
                        : 0,
                ],
                'silver' => [
                    'count' => $profiles->where('tier', 'silver')->count(),
                    'percentage' => $profiles->count() > 0 
                        ? round(($profiles->where('tier', 'silver')->count() / $profiles->count()) * 100, 1)
                        : 0,
                ],
                'gold' => [
                    'count' => $profiles->where('tier', 'gold')->count(),
                    'percentage' => $profiles->count() > 0 
                        ? round(($profiles->where('tier', 'gold')->count() / $profiles->count()) * 100, 1)
                        : 0,
                ],
                'platinum' => [
                    'count' => $profiles->where('tier', 'platinum')->count(),
                    'percentage' => $profiles->count() > 0 
                        ? round(($profiles->where('tier', 'platinum')->count() / $profiles->count()) * 100, 1)
                        : 0,
                ],
            ];
        });
    }

    private function loadRecentTransactions(): void
    {
        $cacheKey = "beauty:bonus_dashboard_transactions:{$this->venueId}";

        $this->recentTransactions = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $query = LoyaltyTransactionModel::with(['loyaltyProfile.client', 'appointment'])
                ->whereHas('loyaltyProfile', fn ($q) => $q->where('venue_id', $this->venueId))
                ->orderBy('created_at', 'desc')
                ->limit(20);

            if ($this->selectedTier !== 'all') {
                $query->whereHas('loyaltyProfile', fn ($q) => $q->where('tier', $this->selectedTier));
            }

            return $query
                ->get()
                ->map(fn ($transaction) => [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'type_label' => $this->getTransactionTypeLabel($transaction->type),
                    'points' => $transaction->points,
                    'balance_after' => $transaction->balance_after,
                    'description' => $transaction->description,
                    'user_name' => $transaction->loyaltyProfile->client->full_name,
                    'tier' => $transaction->loyaltyProfile->tier,
                    'created_at' => $transaction->created_at->format('d.m.Y H:i'),
                ])
                ->toArray();
        });
    }

    private function getTierLabel(string $tier): string
    {
        return match ($tier) {
            'bronze' => 'Бронза',
            'silver' => 'Серебро',
            'gold' => 'Золото',
            'platinum' => 'Платина',
            default => 'Без уровня',
        };
    }

    private function getTransactionTypeLabel(string $type): string
    {
        return match ($type) {
            'earned' => 'Начислено',
            'redeemed' => 'Списано',
            'expired' => 'Истекло',
            'adjusted' => 'Корректировка',
            default => 'Неизвестно',
        };
    }

    public function filterByTier(string $tier): void
    {
        $this->selectedTier = $tier;
        $this->loadRecentTransactions();
    }

    public function render()
    {
        return view('beauty-masters::livewire.bonus-dashboard');
    }
}
