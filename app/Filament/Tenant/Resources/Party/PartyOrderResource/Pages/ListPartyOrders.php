<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyOrderResource\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\Party\PartyOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Log\LogManager;

/**
 * Class ListPartyOrders
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ListPartyOrders extends ListRecords
{
    protected static string $resource = PartyOrderResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    public function mount(): void
    {
        parent::mount();

        $this->log->channel('audit')->$this->logger->info('PartyOrder registry viewed', [
            'tenant_id' => tenant()->id ?? null,
            'user_id' => auth()->id() ?? null,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Order')
                ->icon('heroicon-o-shopping-cart'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if (function_exists('tenant') && tenant()) {
            $query->where('tenant_id', tenant()->id);
        }

        return $query;
    }
}
