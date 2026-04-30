<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use FilamentAuditService;

use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use App\Services\Security\FilamentAuditService;

/**
 * Base Auditable Resource
 *
 * Base class for all Filament resources with automatic audit logging.
 * Logs all create, update, delete actions to FilamentAuditService.
 */
abstract class BaseAuditableResource extends Resource
{
    protected static ?FilamentAuditService $auditService = null;

    /**
     * Override to add audit logging on create
     */
    public static function afterCreate(Model $record): void
    {
        if (! config('filament.admin.audit_enabled', true)) {
            return;
        }

        static::getAuditService()->logCreated(
            modelType: $record::class,
            modelId: $record->id,
            attributes: $record->toArray()
        );
    }

    /**
     * Override to add audit logging on update
     */
    public static function afterUpdate(Model $record, array $old): void
    {
        if (! config('filament.admin.audit_enabled', true)) {
            return;
        }

        static::getAuditService()->logUpdated(
            modelType: $record::class,
            modelId: $record->id,
            old: $old,
            new: $record->toArray()
        );
    }

    /**
     * Override to add audit logging on delete
     */
    public static function afterDelete(Model $record): void
    {
        if (! config('filament.admin.audit_enabled', true)) {
            return;
        }

        static::getAuditService()->logDeleted(
            modelType: $record::class,
            modelId: $record->id,
            attributes: $record->toArray()
        );
    }

    protected static function getAuditService(): FilamentAuditService
    {
        if (! static::$auditService) {
            static::$auditService = $this->filamentAuditService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
        }

        return static::$auditService;
    }

    /**
     * Helper to enable audit logging in table actions
     */
    protected static function getAuditedTableActions(): array
    {
        return [
            Tables\Actions\EditAction::make()
                ->after(function (Model $record) {
                    $old = $record->getOriginal();
                    static::afterUpdate($record, $old);
                }),
            Tables\Actions\DeleteAction::make()
                ->after(function (Model $record) {
                    static::afterDelete($record);
                }),
        ];
    }
}
