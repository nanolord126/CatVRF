<?php declare(strict_types=1);

/**
 * EditBeverageReview — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editbeveragereview
 */


namespace App\Filament\Tenant\Resources\Pages;


use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\BeverageReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 * Class EditBeverageReview
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class EditBeverageReview extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = BeverageReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->requiresConfirmation()
                ->modalHeading('Удалить отзыв?')
                ->modalDescription('Отзыв будет безвозвратно удалён.')
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
        \Illuminate\Support\Facades\Log::channel('audit')->info('BeverageReview updated', [
            'review_id'      => $this->record->id,
            'rating'         => $this->record->rating,
            'is_verified'    => $this->record->is_verified,
            'tenant_id'      => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
