<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyOrderResource\Pages;

use App\Filament\Tenant\Resources\Party\PartyOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * ListPartyOrders Page.
 */
final class ListPartyOrders extends ListRecords
{
    protected static ?string $resource = PartyOrderResource::class;

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

    public function mount(): void
    {
        parent::mount();

        Log::channel('audit')->info('PartyOrder registry viewed', [
            'tenant_id' => tenant()->id ?? null,
            'user_id' => auth()->id() ?? null,
        ]);
    }
}

/**
 * CreatePartyOrder Page.
 */
final class CreatePartyOrder extends \Filament\Resources\Pages\CreateRecord
{
    protected static ?string $resource = PartyOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('New PartyOrder created', [
            'order_id' => $this->record->id,
            'event_date' => $this->record->event_date,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}

/**
 * EditPartyOrder Page.
 */
final class EditPartyOrder extends \Filament\Resources\Pages\EditRecord
{
    protected static ?string $resource = PartyOrderResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
        return $data;
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('PartyOrder updated', [
            'order_id' => $this->record->id,
            'status' => $this->record->status,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
