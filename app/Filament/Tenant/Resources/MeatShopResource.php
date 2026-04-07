<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class MeatShopResource extends Resource
{

    protected static ?string $model = MeatProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-fire';

        protected static ?string $navigationGroup = 'Food';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Основная информация')
                        ->description('Базовые сведения об объекте')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                                    Select::make('type')
                        ->required()
                        ->searchable(),
                                    TextInput::make('cut')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('price_per_kg')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('stock_kg')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('certification')
                        ->required()
                        ->maxLength(255),
                                ]),
                        ]),

                    Section::make('Дополнительно')
                        ->description('Расширенные параметры')
                        ->collapsed()
                        ->schema([
                            Grid::make(2)
                                ->schema([]),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListMeatShop::route('/'),
                'create' => Pages\CreateMeatShop::route('/create'),
                'edit' => Pages\EditMeatShop::route('/{record}/edit'),
                'view' => Pages\ViewMeatShop::route('/{record}'),
            ];
        }
}
