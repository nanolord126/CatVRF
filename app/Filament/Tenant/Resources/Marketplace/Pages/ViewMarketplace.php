<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Marketplace\Pages;

use App\Filament\Tenant\Resources\Marketplace\MarketplaceResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

final class ViewMarketplace extends ViewRecord
{
    protected static string $resource = MarketplaceResource::class;

    protected function afterLoad(): void
    {
        Log::channel('audit')->info('Marketplace record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function render()
    {
        Log::channel('audit')->debug('ViewMarketplace page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }
}