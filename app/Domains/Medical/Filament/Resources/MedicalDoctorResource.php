<?php

declare(strict_types=1);

namespace App\Domains\Medical\Filament\Resources;

use Carbon\CarbonImmutable;

use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms\Form;
use Filament\Tables\Table;

final class MedicalDoctorResource extends BaseOptimizedResource
{
    protected static ?string $model = MedicalDoctor::class;

    protected static ?string $navigationGroup = 'Medical';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('clinic_id')->relationship('clinic', 'name')->required(),
            TextInput::make('full_name')->required(),
            TextInput::make('specialization')->required(),
            TextInput::make('experience_years')->numeric(),
            TextInput::make('license_number')->unique(),
            RichEditor::make('bio')->columnSpanFull(),
            TextInput::make('consultation_price')->numeric()->step(0.01),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('full_name')->searchable(),
            TextColumn::make('clinic.name'),
            TextColumn::make('specialization'),
            TextColumn::make('experience_years'),
            TextColumn::make('consultation_price')->numeric()->sortable(),
            TextColumn::make('rating')->numeric()->sortable(),
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
