<?php declare(strict_types=1);

/**
 * ViewAutoPart — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/viewautopart
 */


namespace App\Domains\Auto\Filament\Resources\AutoPartResource\Pages;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\ViewRecord;

final class ViewAutoPart extends ViewRecord
{
    public function __construct(
        private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    protected static string $resource = AutoPartResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\EditAction::make(),

                Actions\DeleteAction::make()
                    ->after(function () {
                        $this->logger->info('Auto part deleted from view page', [
                            'correlation_id' => $this->record->correlation_id,
                            'part_id' => $this->record->id,
                            'sku' => $this->record->sku,
                            'user_id' => $this->guard->id(),
                        ]);
                    }),
            ];
        }

        protected function mutateFormDataBeforeFill(array $data): array
        {
            $this->logger->info('Auto part viewed', [
                'correlation_id' => $this->record->correlation_id,
                'part_id' => $this->record->id,
                'sku' => $this->record->sku,
                'user_id' => $this->guard->id(),
            ]);

            return $data;
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
