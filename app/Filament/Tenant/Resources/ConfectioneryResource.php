<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class ConfectioneryResource extends Resource
{

    protected static ?string $model = ConfectioneryItem::class;

        protected static ?string $navigationIcon = 'heroicon-o-cake';

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
                                    Select::make('category')
                        ->required()
                        ->searchable(),
                                    TextInput::make('price')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('weight_grams')
                        ->required()
                        ->maxLength(255),
                                    Textarea::make('ingredients')
                        ->maxLength(1000),
                                    TagsInput::make('allergens'),
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
                'index' => Pages\ListConfectionery::route('/'),
                'create' => Pages\CreateConfectionery::route('/create'),
                'edit' => Pages\EditConfectionery::route('/{record}/edit'),
                'view' => Pages\ViewConfectionery::route('/{record}'),
            ];
        }
}
