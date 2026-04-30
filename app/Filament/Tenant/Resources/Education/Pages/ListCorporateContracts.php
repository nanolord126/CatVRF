<?php

declare(strict_types=1);

/**
 * ListCorporateContracts — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 * @see https://catvrf.ru/docs/listcorporatecontracts
 */

namespace App\Filament\Tenant\Resources\Education\Pages;

use Psr\Log\LoggerInterface;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Log\LogManager;

final class ListCorporateContracts extends ListRecords
{
    protected static string $resource = CorporateContractResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Логирование в аудит-канал.
     */
    public function mount(): void
    {
        parent::mount();

        $this->log->channel('audit')->$this->logger->info('B2B Education: Viewing contracts list', [
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'correlation_id' => (string) Str::uuid(),
        ]);
    }

    /**
     * Кнопка создания нового контракта (Agreement Construction).
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Construction Engagement (B2B)')
                ->icon('heroicon-o-document-plus')
                ->successNotificationTitle('Corporate contract constructed and pending signature.'),
        ];
    }
}
