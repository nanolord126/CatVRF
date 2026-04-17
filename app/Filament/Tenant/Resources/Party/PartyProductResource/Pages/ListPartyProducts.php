<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyProductResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\Party\PartyProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Class ListPartyProducts
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Party\PartyProductResource\Pages
 */
final class ListPartyProducts extends ListRecords
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = PartyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Item')
                ->icon('heroicon-o-gift'),
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

        \Illuminate\Support\Facades\Log::channel('audit')->info('PartyProduct catalog viewed', [
            'tenant_id' => tenant()->id ?? null,
            'user_id' => auth()->id() ?? null,
        ]);
    }
}
