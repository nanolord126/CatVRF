<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\FashionRetailResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class EditFashionRetail
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class EditFashionRetail extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FashionRetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Удалить B2B-ритейл заказ?')
                ->modalDescription('Это действие необратимо. Заказ и все связанные данные будут удалены.')
                ->modalSubmitActionLabel('Да, удалить'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        $this->logger->info('B2B Fashion retail order updated', [
            'order_id'       => $record->id,
            'status'         => $record->status,
            'buyer_inn'      => $record->buyer_inn,
            'total_amount'   => $record->total_amount,
            'correlation_id' => $record->correlation_id,
        ]);
    }
}