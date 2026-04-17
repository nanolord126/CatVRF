<?php declare(strict_types=1);

namespace Modules\RealEstate\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;

final class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Real Estate';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('uuid')
                    ->disabled()
                    ->maxLength(255),

                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->required()
                    ->searchable(),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->rows(3),

                TextInput::make('address')
                    ->required()
                    ->maxLength(500),

                TextInput::make('city')
                    ->required()
                    ->maxLength(100),

                TextInput::make('region')
                    ->maxLength(100),

                TextInput::make('lat')
                    ->numeric()
                    ->step(0.000001),

                TextInput::make('lon')
                    ->numeric()
                    ->step(0.000001),

                Select::make('property_type')
                    ->options([
                        'apartment' => 'Apartment',
                        'house' => 'House',
                        'commercial' => 'Commercial',
                        'land' => 'Land',
                        'parking' => 'Parking',
                        'warehouse' => 'Warehouse',
                    ])
                    ->required(),

                TextInput::make('price')
                    ->numeric()
                    ->prefix('₽')
                    ->required(),

                TextInput::make('area')
                    ->numeric()
                    ->suffix('m²'),

                TextInput::make('rooms')
                    ->numeric()
                    ->minValue(1),

                TextInput::make('floor')
                    ->numeric()
                    ->minValue(1),

                TextInput::make('total_floors')
                    ->numeric()
                    ->minValue(1),

                TextInput::make('year_built')
                    ->numeric()
                    ->minValue(1900)
                    ->maxValue(date('Y')),

                TextInput::make('virtual_tour_url')
                    ->url()
                    ->maxLength(500),

                TextInput::make('ar_model_url')
                    ->url()
                    ->maxLength(500),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),

                TextColumn::make('uuid')
                    ->searchable()
                    ->toggleable()
                    ->limit(10),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('city')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property_type')
                    ->badge()
                    ->color(fn (PropertyType $type): string => match ($type) {
                        PropertyType::APARTMENT => 'info',
                        PropertyType::HOUSE => 'success',
                        PropertyType::COMMERCIAL => 'warning',
                        PropertyType::LAND => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PropertyStatus $status): string => match ($status) {
                        PropertyStatus::AVAILABLE => 'success',
                        PropertyStatus::SOLD => 'danger',
                        PropertyStatus::RENTED => 'warning',
                        PropertyStatus::UNDER_CONTRACT => 'info',
                        PropertyStatus::WITHDRAWN => 'gray',
                        PropertyStatus::ARCHIVED => 'gray',
                    }),

                TextColumn::make('price')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('area')
                    ->numeric()
                    ->suffix('m²'),

                TextColumn::make('rooms'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'sold' => 'Sold',
                        'rented' => 'Rented',
                        'under_contract' => 'Under Contract',
                    ]),

                SelectFilter::make('property_type')
                    ->options([
                        'apartment' => 'Apartment',
                        'house' => 'House',
                        'commercial' => 'Commercial',
                        'land' => 'Land',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'bookings',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\RealEstate\Filament\Resources\PropertyResource\Pages\ListProperties::route('/'),
            'create' => \Modules\RealEstate\Filament\Resources\PropertyResource\Pages\CreateProperty::route('/create'),
            'view' => \Modules\RealEstate\Filament\Resources\PropertyResource\Pages\ViewProperty::route('/{record}'),
            'edit' => \Modules\RealEstate\Filament\Resources\PropertyResource\Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
