<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class SportingGoodsResource extends Resource
{

    protected static ?string $model = SportingGood::class;

        protected static ?string $navigationIcon = 'heroicon-o-sparkles';

        protected static ?string $navigationGroup = 'Sports';

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
                                    Select::make('category')
                        ->required()
                        ->searchable(),
                                    Select::make('sport_type')
                        ->required()
                        ->searchable(),
                                    TextInput::make('price')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('stock')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('rating')
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
                'index' => Pages\ListSportingGoods::route('/'),
                'create' => Pages\CreateSportingGoods::route('/create'),
                'edit' => Pages\EditSportingGoods::route('/{record}/edit'),
                'view' => Pages\ViewSportingGoods::route('/{record}'),
            ];
        }
}
