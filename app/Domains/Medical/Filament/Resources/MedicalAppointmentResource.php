<?php

declare(strict_types=1);

namespace App\Domains\Medical\Filament\Resources;

use Carbon\CarbonImmutable;

use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms\Form;
use Filament\Tables\Table;

final class MedicalAppointmentResource extends BaseOptimizedResource
{
    protected static ?string $model = MedicalAppointment::class;

    protected static ?string $navigationGroup = 'Medical';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('clinic_id')->relationship('clinic', 'name')->required(),
            Select::make('doctor_id')->relationship('doctor', 'full_name')->required(),
            Select::make('patient_id')->relationship('patient', 'name')->required(),
            Select::make('service_id')->relationship('service', 'name')->required(),
            DateTimePickerInput::make('scheduled_at')->required(),
            TextInput::make('price')->numeric()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('appointment_number')->searchable(),
            TextColumn::make('doctor.full_name'),
            TextColumn::make('patient.name'),
            TextColumn::make('price')->numeric()->sortable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('scheduled_at')->sortable(),
            TextColumn::make('completed_at')->sortable(),
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
