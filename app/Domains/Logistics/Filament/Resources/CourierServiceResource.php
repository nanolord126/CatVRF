<?php declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class CourierServiceResource extends Resource
{

    protected static ?string $model = CourierService::class;

        protected static ?string $navigationGroup = 'Logistics';

        public static function form(Form $form): Form
        {
            return $form->schema([
                TextInput::make('company_name')->required()->maxLength(255),
                RichEditor::make('description')->columnSpanFull(),
                TextInput::make('license_number')->required()->unique('courier_services', 'license_number'),
                TextInput::make('service_radius')->required()->numeric(),
                TextInput::make('base_rate')->required()->numeric()->step(0.01),
                TextInput::make('per_km_rate')->required()->numeric()->step(0.01),
                Toggle::make('is_verified')->default(false),
                Toggle::make('is_active')->default(true),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('company_name')->searchable(),
                TextColumn::make('service_radius')->sortable(),
                TextColumn::make('rating')->numeric()->sortable(),
                IconColumn::make('is_verified')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])->filters([])->actions([])->bulkActions([]);
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
