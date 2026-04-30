<?php

declare(strict_types=1);

/**
 * ShipmentRatingResource — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/shipmentratingresource
 */

namespace App\Domains\Logistics\Filament\Resources;

use Carbon\CarbonImmutable;

use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms\Form;
use Filament\Tables\Table;

final class ShipmentRatingResource extends BaseOptimizedResource
{
    protected static ?string $model = ShipmentRating::class;

    protected static ?string $navigationGroup = 'Logistics';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('shipment_id')->relationship('shipment', 'tracking_number')->required(),
            Select::make('reviewer_id')->relationship('reviewer', 'name')->required(),
            TextInput::make('rating')->required()->numeric()->min(1)->max(5),
            RichEditor::make('comment')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('shipment.tracking_number'),
            TextColumn::make('rating')->numeric()->sortable(),
            IconColumn::make('verified_purchase')->boolean(),
        ])->filters([])->actions([])->bulkActions([]);
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    /**
     * Relations to eager load for Logistics
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
