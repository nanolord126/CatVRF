<?php declare(strict_types=1);

/**
 * EditFitness — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editfitness
 */


namespace App\Filament\Tenant\Resources\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\FitnessResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class EditFitness
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class EditFitness extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FitnessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Удалить B2B-заказ фитнес?')
                ->modalDescription('Это действие необратимо. Заказ будет удалён.')
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
        \Illuminate\Support\Facades\Log::channel('audit')->info('B2B Fitness order updated', [
            'order_id'       => $record->id,
            'status'         => $record->status,
            'total_amount'   => $record->total_amount,
            'correlation_id' => $record->correlation_id,
        ]);
    }
}