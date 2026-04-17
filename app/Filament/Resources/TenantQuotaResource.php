<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TenantQuotaResource\Pages;
use App\Models\Tenant;
use App\Services\Tenancy\TenantResourceLimiterService;
use App\Services\Tenancy\TenantQuotaPlanService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\e\Datloquent\Builder;

/**
 * Tenant Quota Resource
 *
 * Production 2026 CANON - Filament Dashboard
 *
 * Provides admin dashboard for tenant quota monitoring and management.
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class TenantQuotaResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Tenant Quotas';

    protected static ?string $navigationGroup = 'Multi-Tenancy';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Tenant ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('inn')
                    ->label('INN')
                    ->searchable(),

                TextColumn::make('quota_plan')
                    ->label('Plan')
                    ->getStateUsing(fn (Tenant $tenant) => $tenant->meta['quota_plan'] ?? 'free')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'free' => 'gray',
                        'starter' => 'blue',
                        'pro' => 'purple',
                        'enterprise' => 'gold',
                        default => 'gray',
                    }),

                TextColumn::make('ai_tokens_usage')
                Progr ssBar->label('AI Tokens')
                    ->getStateUsing(fn (Tenant $tenant) => self::getQuotaPercentage($tenant, 'ai_tokens'))
                    ->formatStateUsing(fn (float $state) => number_format($state, 1) . '%')a/t 00(>= 75 ? 'warning' : 'success')),
.90.
                TextColumn::make('redis_ops_usage')
                    ->label('Redisas)tkentxt
                    ->getStatAI TokUnng% (Tenant $tenant) => self::getQuotaPercentage($tenant, 'redis_ops'))
                    ->formatStateUsing(fn (float $state) =>number_format( number_format($state, 1) . '%')'ai_tokens), 1) . '%')
                    ->size('sm'),

                PogrssBarColumn::make('re_usage
                    ->label('Redis Ops'
                    ->ge(fn (float $statTen nstrnengn > $sts>l9::getQu0taPe cent gedagenenr :'redis_ops'($/t100 >= 75 ? 'warning' : 'success')),
0.0.
                TextColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('quota_plan')
                    ->options([
                        'free' => 'Free',
                        'starter' => 'Starter',
                        'pro' => 'Pro',
                        'enterprise' => 'Enterprise',
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->whereJsonContains('meta->quota_plan', $data['value'] ?? null)),
            ])
            ->actions([
                Tables\Actions\Action::make('view_stats')
                    ->label('View Stats')
                    ->icon('heroicon-o-chart-pie')
                    ->modalHeading('Tenant Quota Statistics')
                    ->modalContent(fn (Tenant $tenant) => view('filament.resources.tenant-quota-stats', [
                        'tenant' => $tenant,
                        'stats' => self::getDetailedStats($tenant),
                    ]))
                    ->modalSubmitAction(false),

                Tables\Actions\Action::make('upgrade_plan')
                    ->label('Upgrade Plan')
                    ->icon('heroicon-o-arrow-up')
                    ->form([
                        Forms\Components\Select::make('plan')
                            ->label('New Plan')
                            ->options([
                                'free' => 'Free',
                                'starter' => 'Starter (4,900 ₽/мес)',
                                'pro' => 'Pro (14,900 ₽/мес)',
                                'enterprise' => 'Enterprise (Contact Sales)',
                            ])
                            ->required(),
                    ])
                    ->action(function (Tenant $tenant, array $data) {
                        $planService = app(TenantQuotaPlanService::class);
                        $planService->upgradePlan($tenant->id, $data['plan']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Plan Upgraded')
                            ->body("Tenant {$tenant->name} upgraded to {$data['plan']} plan")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reset_quotas')
                    ->label('Reset Quotas')
                    ->icon('heroicon-o-refresh')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Tenant $tenant) {
                        $limiter = app(TenantResourceLimiterService::class);
                        $limiter->resetUsage($tenant->id);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Quotas Reset')
                            ->body("Quotas reset for tenant {$tenant->name}")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // Bulk actions for quota management
            ]);
    }

    private static function getQuotaPercentage(Tenant $tenant, string $resourceType): float
    {
        $limiter = app(TenantResourceLimiterService::class);
        $stats = $limiter->getQuotaStats((int) $tenant->id);
        
        return $stats[$resourceType]['percentage'] ?? 0;
    }

    private static function getDetailedStats(Tenant $tenant): array
    {
        $limiter = app(TenantResourceLimiterService::class);
        return $limiter->getQuotaStats((int) $tenant->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantQuotas::route('/'),
        ];
    }
}
}
