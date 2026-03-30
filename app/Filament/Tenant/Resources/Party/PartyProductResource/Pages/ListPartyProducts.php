<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\PartyProductResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListPartyProducts extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $resource = PartyProductResource::class;

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

            Log::channel('audit')->info('PartyProduct catalog viewed', [
                'tenant_id' => tenant()->id ?? null,
                'user_id' => auth()->id() ?? null,
            ]);
        }
    }

    /**
     * CreatePartyProduct Page.
     */
    final class CreatePartyProduct extends \Filament\Resources\Pages\CreateRecord
    {
        protected static ?string $resource = PartyProductResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = tenant()->id ?? null;
            $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
            return $data;
        }

        protected function afterCreate(): void
        {
            Log::channel('audit')->info('New PartyProduct created', [
                'product_id' => $this->record->id,
                'sku' => $this->record->sku,
                'correlation_id' => $this->record->correlation_id,
            ]);
        }
    }

    /**
     * EditPartyProduct Page.
     */
    final class EditPartyProduct extends \Filament\Resources\Pages\EditRecord
    {
        protected static ?string $resource = PartyProductResource::class;

        protected function mutateFormDataBeforeSave(array $data): array
        {
            $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
            return $data;
        }

        protected function afterSave(): void
        {
            Log::channel('audit')->info('PartyProduct updated', [
                'product_id' => $this->record->id,
                'sku' => $this->record->sku,
                'correlation_id' => $this->record->correlation_id,
            ]);
        }
}
