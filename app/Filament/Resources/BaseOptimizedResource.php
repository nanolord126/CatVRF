<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Optimized Resource
 *
 * Base class for all Filament resources with performance optimizations:
 * - Default pagination (25 per page)
 * - Configurable eager loading for relations
 * - Database query optimization hints
 *
 * Extend this class instead of Filament\Resources\Resource for all new resources.
 */
abstract class BaseOptimizedResource extends BaseAuditableResource
{
    /**
     * Apply eager loading to queries
     */
    public static function applyEagerLoading(Builder $query): Builder
    {
        $relations = static::getEagerLoading();

        if (! empty($relations)) {
            return $query->with($relations);
        }

        return $query;
    }

    /**
     * Apply pagination to table
     */
    public static function getTablePagination(Tables\Table $table): Tables\Table
    {
        return $table
            ->paginate(static::getDefaultRecordsPerPage())
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Optimized table query with eager loading
     */
    public static function getTableQuery(): Builder
    {
        $query = static::getModel()::query();

        // Apply eager loading
        $query = static::applyEagerLoading($query);

        // Add tenant scope if applicable
        if (method_exists(static::getModel(), 'scopeTenant')) {
            $query = $query->tenant();
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Invalidate cache for this resource
     */
    public static function invalidateTableCache(?string $key = null): void
    {
        $pattern = $key
            ? "filament_table_{$key}"
            : 'filament_table_'.class_basename(static::class);

        cache()->forget($pattern);
    }

    /**
     * Relations to eager load by default
     * Override in child classes to specify relations
     *
     * @return array<string>
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }

    /**
     * Get default pagination options
     */
    protected static function getDefaultPaginationOptions(): array
    {
        return config('filament.paginator_options', [10, 25, 50, 100]);
    }

    /**
     * Get default records per page
     */
    protected static function getDefaultRecordsPerPage(): int
    {
        return config('filament.default_paginator', 25);
    }

    /**
     * Get optimized table columns with proper caching hints
     */
    protected static function getOptimizedTableColumns(): array
    {
        return [];
    }

    /**
     * Helper to get cached table data
     * Use for widgets that don't need real-time data
     */
    protected static function getCachedTableData(string $key, callable $callback, int $ttl = 300): mixed
    {
        return cache()->remember(
            key: "filament_table_{$key}",
            ttl: $ttl,
            callback: $callback
        );
    }
}
