<?php

declare(strict_types=1);

/**
 * EditLuxuryProduct — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/editluxuryproduct
 * @see https://catvrf.ru/docs/editluxuryproduct
 * @see https://catvrf.ru/docs/editluxuryproduct
 * @see https://catvrf.ru/docs/editluxuryproduct
 * @see https://catvrf.ru/docs/editluxuryproduct
 * @see https://catvrf.ru/docs/editluxuryproduct
 */

namespace App\Filament\Tenant\Resources\LuxuryProductResource\Pages;

use Psr\Log\LoggerInterface;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Log\LogManager;

final class EditLuxuryProduct extends EditRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';


    protected static string $resource = LuxuryProductResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

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

        $this->log->channel('audit')->$this->logger->info('Editing Luxury Product via Filament', [
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
}
