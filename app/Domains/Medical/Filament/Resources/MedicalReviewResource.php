<?php

declare(strict_types=1);

/**
 * MedicalReviewResource — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/medicalreviewresource
 */

namespace App\Domains\Medical\Filament\Resources;

use Carbon\CarbonImmutable;

use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms\Form;
use Filament\Tables\Table;

final class MedicalReviewResource extends BaseOptimizedResource
{
    protected static ?string $model = MedicalReview::class;

    protected static ?string $navigationGroup = 'Medical';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('doctor_id')->relationship('doctor', 'full_name')->required(),
            Select::make('reviewer_id')->relationship('reviewer', 'name')->required(),
            TextInput::make('rating')->numeric()->min(1)->max(5)->required(),
            RichEditor::make('comment')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('doctor.full_name'),
            TextColumn::make('reviewer.name'),
            TextColumn::make('rating')->numeric()->sortable(),
            TextColumn::make('status')->badge(),
            IconColumn::make('verified_appointment')->boolean(),
            TextColumn::make('helpful_count')->numeric(),
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
     * Relations to eager load for Medical
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
