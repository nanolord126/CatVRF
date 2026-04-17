<?php declare(strict_types=1);

namespace App\Filament\Resources\TenantQuotaResource\Pages;

use App\Filament\Resources\TenantQuotaResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * List Tenant Quotas Page
 *
 * Production 2026 CANON - Filament Dashboard
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class ListTenantQuotas extends ListRecords
{
    protected static string $resource = TenantQuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export_stats')
                ->label('Export Statistics')
                ->icon('heroicon-o-download')
                ->action(function () {
                    $limiter = app(\App\Services\Tenancy\TenantResourceLimiterService::class);
                    $tenants = \App\Models\Tenant::all();
                    
                    $stats = [];
                    foreach ($tenants as $tenant) {
                        $stats[$tenant->id] = [
                            'name' => $tenant->name,
                            'plan' => $tenant->meta['quota_plan'] ?? 'free',
                            'quotas' => $limiter->getQuotaStats($tenant->id),
                        ];
                    }
                    
                    return response()->streamDownload(function () use ($stats) {
                        echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    }, 'tenant-quota-stats-' . now()->format('Y-m-d') . '.json');
                }),
        ];
    }
}
