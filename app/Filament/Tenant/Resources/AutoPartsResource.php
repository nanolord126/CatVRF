<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class AutoPartsResource extends Resource
{

    protected static ?string $model = AutoPart::class;

        protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

        protected static ?string $navigationGroup = 'Auto';

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
                                    TextInput::make('sku')
                        ->required()
                        ->maxLength(255),
                                    Select::make('part_type')
                        ->required()
                        ->searchable(),
                                    Select::make('category')
                        ->required()
                        ->searchable(),
                                    TextInput::make('brand')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('price')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('current_stock')
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
                'index' => Pages\ListAutoParts::route('/'),
                'create' => Pages\CreateAutoParts::route('/create'),
                'edit' => Pages\EditAutoParts::route('/{record}/edit'),
                'view' => Pages\ViewAutoParts::route('/{record}'),
            ];
        }
}
