<?php

declare(strict_types=1);


namespace App\Domains\Logistics\Filament\Resources;

use App\Domains\Logistics\Models\CourierService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

final /**
 * CourierServiceResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CourierServiceResource extends Resource
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
}
