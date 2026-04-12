<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class GiftsResource extends Resource
{

    protected static ?string $model = GiftProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-gift';

        protected static ?string $navigationGroup = 'Gifts';

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
                                    TextInput::make('price')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('stock')
                        ->required()
                        ->maxLength(255),
                                    Select::make('occasion')
                        ->required()
                        ->searchable(),
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
                'index' => Pages\ListGifts::route('/'),
                'create' => Pages\CreateGifts::route('/create'),
                'edit' => Pages\EditGifts::route('/{record}/edit'),
                'view' => Pages\ViewGifts::route('/{record}'),
            ];
        }
}
