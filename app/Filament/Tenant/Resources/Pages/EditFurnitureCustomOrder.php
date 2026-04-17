<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\FurnitureCustomOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class EditFurnitureCustomOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class EditFurnitureCustomOrder extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FurnitureCustomOrderResource::class;

    public function getTitle(): string
    {
        return 'Редактирование индивидуального заказа';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Удалить заказ?')
                ->modalDescription('Заказ будет удалён без возможности восстановления.')
                ->modalSubmitActionLabel('Удалить'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();

        return $data;
    }

    protected function afterSave(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('Furniture custom order updated', [
            'order_id' => $this->record->id ?? null,
            'tenant_id' => $this->record->tenant_id ?? null,
            'correlation_id' => $this->record->correlation_id ?? null,
        ]);
    }
}
