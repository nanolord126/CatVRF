<?php

declare(strict_types=1);

namespace App\Filament\B2B\Widgets;

use App\Filament\Widgets\BaseCachedWidget;
use Carbon\CarbonImmutable;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;

/**
 * Tenant Performance Widget
 *
 * Shows performance comparison across all tenants in business group:
 * - Revenue per tenant
 * - Orders per tenant
 * - Growth rate
 * - Active users
 */
final class TenantPerformanceWidget extends BaseCachedWidget implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {
        parent::__construct();
    }

    protected static string $cacheKeyPrefix = 'b2b_tenant_performance';

    protected static int $cacheTtl = 600; // 10 minutes

    public function table(Table $table): Table
    {
        $businessGroupId = $this->guard->user()?->business_group_id;

        return $table
            ->query(
                $this->db->table('tenants')
                    ->where('business_group_id', $businessGroupId)
                    ->select([
                        'tenants.id',
                        'tenants.name',
                        'tenants.is_active',
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tenant Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('revenue')
                    ->label('Revenue (30d)')
                    ->money('rub')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $this->getTenantRevenue($record->id);
                    }),

                Tables\Columns\TextColumn::make('orders')
                    ->label('Orders (30d)')
                    ->numeric()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $this->getTenantOrders($record->id);
                    }),

                Tables\Columns\TextColumn::make('users')
                    ->label('Active Users')
                    ->numeric()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $this->getTenantUsers($record->id);
                    }),

                Tables\Columns\TextColumn::make('growth')
                    ->label('Growth %')
                    ->numeric()
                    ->suffix('%')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->getStateUsing(function ($record) {
                        return $this->getTenantGrowth($record->id);
                    }),
            ])
            ->defaultSort('revenue', 'desc')
            ->paginated([10, 25, 50]);
    }

    private function getTenantRevenue(int $tenantId): float
    {
        $now = CarbonImmutable::now();

        return (float) $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$now->subMonth(), $now])
            ->sum('total_amount');
    }

    private function getTenantOrders(int $tenantId): int
    {
        $now = CarbonImmutable::now();

        return $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$now->subMonth(), $now])
            ->count();
    }

    private function getTenantUsers(int $tenantId): int
    {
        return $this->db->table('users')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
    }

    private function getTenantGrowth(int $tenantId): float
    {
        $now = CarbonImmutable::now();

        $current = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$now->subMonth(), $now])
            ->sum('total_amount');

        $previous = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$now->subMonths(2), $now->subMonth()])
            ->sum('total_amount');

        if ($previous == 0) {
            return 0;
        }

        return (($current - $previous) / $previous) * 100;
    }
}
