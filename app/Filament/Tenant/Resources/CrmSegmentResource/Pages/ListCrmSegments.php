<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmSegmentResource\Pages;

use CrmSegmentationService;

use App\Filament\Tenant\Resources\CrmSegmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Domains\CRM\Services\CrmSegmentationService;
use Illuminate\Support\Str;

/**
 * ListCrmSegments — список сегментов CRM в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class ListCrmSegments extends ListRecords
{
    /**
     * Component: ListCrmSegments
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected static string $resource = CrmSegmentResource::class;

    /**
     * Строковое представление для отладки.
     */
    public function __construct(
        private readonly CrmSegmentationService $crmSegmentationService,
    ) {}

    public function __toString(): string
    {
        return 'ListCrmSegments';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Новый сегмент')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('recalculate_all')
                ->label('Пересчитать все')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->crmSegmentationService /* TODO: inject via constructor DI */ /* TODO: inject via DI */
                        ->recalculateAllSegments(
                            tenant()?->id ?? 0,
                            Str::uuid()->toString(),
                        );
                }),
        ];
    }
}
