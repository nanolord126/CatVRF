<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\FinancesResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class EditFinances
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class EditFinances extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FinancesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Удалить запись?')
                ->modalDescription('Это действие необратимо.')
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
        \Illuminate\Support\Facades\Log::channel('audit')->info('Financial transaction updated', [
            'transaction_id' => $record->id,
            'type'           => $record->type,
            'status'         => $record->status,
            'amount'         => $record->amount,
            'correlation_id' => $record->correlation_id,
        ]);
    }
}
