<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class FarmDirectResource extends Resource
{

    protected static ?string $model = FarmProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-leaf';

        protected static ?string $navigationGroup = 'Agriculture';

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
                                    TextInput::make('farm_name')
                        ->required()
                        ->maxLength(255),
                                    Select::make('category')
                        ->required()
                        ->searchable(),
                                    TextInput::make('price')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('quantity_available')
                        ->required()
                        ->maxLength(255),
                                    Select::make('unit')
                        ->required()
                        ->searchable(),
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
                'index' => Pages\ListFarmDirect::route('/'),
                'create' => Pages\CreateFarmDirect::route('/create'),
                'edit' => Pages\EditFarmDirect::route('/{record}/edit'),
                'view' => Pages\ViewFarmDirect::route('/{record}'),
            ];
        }
}
