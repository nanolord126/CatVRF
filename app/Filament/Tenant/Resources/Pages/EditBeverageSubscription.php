<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\BeverageSubscriptionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class EditBeverageSubscription
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class EditBeverageSubscription extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = BeverageSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->requiresConfirmation()
                ->modalHeading('Отменить подписку?')
                ->modalDescription('Подписка будет удалена без возврата средств.')
                ->modalSubmitActionLabel('Да, удалить'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();

        return $data;
    }

    protected function afterSave(): void
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('BeverageSubscription updated', [
            'subscription_id' => $this->record->id,
            'plan_type'       => $this->record->plan_type,
            'status'          => $this->record->status,
            'auto_renew'      => $this->record->auto_renew,
            'tenant_id'       => $this->record->tenant_id,
            'correlation_id'  => $this->record->correlation_id,
        ]);
    }
}
