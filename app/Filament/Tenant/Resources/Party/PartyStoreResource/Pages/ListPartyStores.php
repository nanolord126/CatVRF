<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages;

use App\Filament\Tenant\Resources\Party\PartyStoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * ListPartyStores Page.
 * Listing with tenant scoping and audit logging.
 */
final class ListPartyStores extends ListRecords
{
    protected static ?string $resource = PartyStoreResource::class;

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

        Log::channel('audit')->info('PartyStore list viewed', [
            'tenant_id' => tenant()->id ?? null,
            'user_id' => auth()->id() ?? null,
        ]);
    }
}

/**
 * CreatePartyStore Page.
 */
final class CreatePartyStore extends \Filament\Resources\Pages\CreateRecord
{
    protected static ?string $resource = PartyStoreResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id ?? null;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('New PartyStore created', [
            'store_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}

/**
 * EditPartyStore Page.
 */
final class EditPartyStore extends \Filament\Resources\Pages\EditRecord
{
    protected static ?string $resource = PartyStoreResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
        return $data;
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('PartyStore updated', [
            'store_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
