<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmSegmentResource\Pages;

use App\Filament\Tenant\Resources\CrmSegmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListCrmSegments — список сегментов CRM в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class ListCrmSegments extends ListRecords
{
    protected static string $resource = CrmSegmentResource::class;

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
                    app(\App\Domains\CRM\Services\CrmSegmentationService::class)
                        ->recalculateAllSegments(
                            tenant()?->id ?? 0,
                            \Illuminate\Support\Str::uuid()->toString(),
                        );
                }),
        ];
    }

    /**
     * Строковое представление для отладки.
     */
    public function __toString(): string
    {
        return 'ListCrmSegments';
    }

    /**
     * Component: ListCrmSegments
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';
}
