<?php

declare(strict_types=1);

namespace App\Domains\Medical\Filament\Resources;

use Carbon\CarbonImmutable;

use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms\Form;
use Filament\Tables\Table;

final class MedicalTestOrderResource extends BaseOptimizedResource
{
    protected static ?string $model = MedicalTestOrder::class;

    protected static ?string $navigationGroup = 'Medical';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('appointment_id')->relationship('appointment', 'appointment_number')->required(),
            Select::make('patient_id')->relationship('patient', 'name')->required(),
            Select::make('clinic_id')->relationship('clinic', 'name')->required(),
            TextInput::make('total_amount')->numeric()->step(0.01)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('test_order_number')->searchable(),
            TextColumn::make('patient.name'),
            TextColumn::make('clinic.name'),
            TextColumn::make('total_amount')->numeric()->sortable(),
            TextColumn::make('commission_amount')->numeric()->sortable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('ordered_at')->sortable(),
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
