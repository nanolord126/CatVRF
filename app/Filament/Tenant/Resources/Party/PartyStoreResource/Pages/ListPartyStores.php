<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\Party\PartyStoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Class ListPartyStores
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages
 */
final class ListPartyStores extends ListRecords
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = PartyStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Shop')
                ->icon('heroicon-o-plus'),
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

    public function mount(): void
    {
        parent::mount();

        $this->logger->info('PartyStore list viewed', [
            'tenant_id' => tenant()->id ?? null,
            'user_id' => $this->guard->id() ?? null,
        ]);
    }
}
