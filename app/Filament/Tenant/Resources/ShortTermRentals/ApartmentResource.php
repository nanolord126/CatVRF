<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\ShortTermRentals;

use App\Domains\ShortTermRentals\Models\Apartment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final /**
 * ApartmentResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ApartmentResource extends Resource
{
    protected static ?string $model = Apartment::class;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Short-Term Rentals';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Textarea::make('description')->required(),
            Forms\Components\TextInput::make('address')->required(),
            Forms\Components\TextInput::make('price_per_night')
                ->numeric()
                ->prefix('₽')
                ->required(),
            Forms\Components\TextInput::make('bedrooms')->numeric()->required(),
            Forms\Components\TextInput::make('bathrooms')->numeric()->required(),
            Forms\Components\TextInput::make('max_guests')->numeric()->required(),
            Forms\Components\Toggle::make('is_available')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('address'),
                Tables\Columns\TextColumn::make('price_per_night')
                    ->money('RUB', divideBy: 100),
                Tables\Columns\TextColumn::make('bedrooms'),
                Tables\Columns\ToggleColumn::make('is_available'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApartments::route('/'),
            'create' => Pages\CreateApartment::route('/create'),
            'edit' => Pages\EditApartment::route('/{record}/edit'),
        ];
    }
}
