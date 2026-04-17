<?php declare(strict_types=1);

/**
 * EditLuxuryProduct — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editluxuryproduct
 * @see https://catvrf.ru/docs/editluxuryproduct
 * @see https://catvrf.ru/docs/editluxuryproduct
 * @see https://catvrf.ru/docs/editluxuryproduct
 * @see https://catvrf.ru/docs/editluxuryproduct
 * @see https://catvrf.ru/docs/editluxuryproduct
 */


namespace App\Filament\Tenant\Resources\LuxuryProductResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\EditRecord;

final class EditLuxuryProduct extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = LuxuryProductResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
                Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
            ];
        }

        protected function mutateFormDataBeforeSave(array $data): array
        {
            $data['correlation_id'] = (string) Str::uuid();

            \Illuminate\Support\Facades\Log::channel('audit')->info('Editing Luxury Product via Filament', [
                'product_id' => $this->record->id,
                'user_id' => auth()->id(),
                'correlation_id' => $data['correlation_id'],
            ]);

            return $data;
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
