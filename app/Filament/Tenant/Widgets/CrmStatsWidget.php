<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\DatabaseManager;

/**
 * CrmStatsWidget — ключевые CRM-метрики для Tenant Dashboard.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Показывает: всего клиентов, новых за неделю, спящих,
 * взаимодействий за месяц, средний чек, NPS.
 */
final class CrmStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $tenantId = tenant()?->id;

        if ($tenantId === null) {
            return [
                Stat::make('CRM', '–')->color('gray'),
            ];
        }

        $totalClients = app(\Illuminate\Database\DatabaseManager::class)->table('crm_clients')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->count();

        $newClientsWeek = app(\Illuminate\Database\DatabaseManager::class)->table('crm_clients')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $activeClients = app(\Illuminate\Database\DatabaseManager::class)->table('crm_clients')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->count();

        $sleepingClients = app(\Illuminate\Database\DatabaseManager::class)->table('crm_clients')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('last_interaction_at', '<', now()->subDays(30))
                    ->orWhereNull('last_interaction_at');
            })
            ->count();

        $interactionsMonth = app(\Illuminate\Database\DatabaseManager::class)->table('crm_interactions')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $avgSpent = app(\Illuminate\Database\DatabaseManager::class)->table('crm_clients')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('total_orders', '>', 0)
            ->avg('average_order_value');

        $avgSpentFormatted = $avgSpent !== null
            ? number_format((float) $avgSpent, 0, ',', ' ') . ' ₽'
            : '–';

        // Тренд новых клиентов за 7 дней
        $clientTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $clientTrend[] = app(\Illuminate\Database\DatabaseManager::class)->table('crm_clients')
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                ->count();
        }

        // Тренд взаимодействий за 7 дней
        $interactionTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $interactionTrend[] = app(\Illuminate\Database\DatabaseManager::class)->table('crm_interactions')
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                ->count();
        }

        return [
            Stat::make('Всего клиентов', (string) $totalClients)
                ->description("Новых за неделю: {$newClientsWeek}")
                ->descriptionIcon('heroicon-o-user-plus')
                ->chart($clientTrend)
                ->color('primary'),

            Stat::make('Активные', (string) $activeClients)
                ->description('Со статусом active')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Спящие', (string) $sleepingClients)
                ->description('Нет активности >30 дн')
                ->descriptionIcon('heroicon-o-moon')
                ->color($sleepingClients > ($totalClients * 0.3) ? 'danger' : 'warning'),

            Stat::make('Взаимодействия / мес', (string) $interactionsMonth)
                ->description('За последние 30 дней')
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->chart($interactionTrend)
                ->color('info'),

            Stat::make('Средний чек', $avgSpentFormatted)
                ->description('По клиентам с заказами')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }
}
