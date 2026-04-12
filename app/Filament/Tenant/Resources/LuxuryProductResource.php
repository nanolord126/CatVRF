<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class LuxuryProductResource extends Resource
{

    protected static ?string $model = LuxuryProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-sparkles';

        protected static ?string $navigationGroup = 'Luxury & VIP';

        protected static ?string $modelLabel = 'Эксклюзивный товар';

        protected static ?string $pluralModelLabel = 'Эксклюзивные товары';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->label('Название'),
                            Forms\Components\Select::make('brand_id')
                                ->relationship('brand', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Бренд'),
                            Forms\Components\TextInput::make('sku')
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->label('Артикул (SKU)'),
                        ])->columns(2),

                    Forms\Components\Section::make('Ценообразование (в копейках)')
                        ->schema([
                            Forms\Components\TextInput::make('price_kopecks')
                                ->numeric()
                                ->required()
                                ->suffix('коп.')
                                ->label('Полная стоимость'),
                            Forms\Components\TextInput::make('min_deposit_kopecks')
                                ->numeric()
                                ->required()
                                ->suffix('коп.')
                                ->label('Минимальный депозит'),
                        ])->columns(2),

                    Forms\Components\Section::make('Склад и опции')
                        ->schema([
                            Forms\Components\TextInput::make('current_stock')
                                ->numeric()
                                ->default(1)
                                ->label('В наличии'),
                            Forms\Components\Toggle::make('is_personalized')
                                ->label('Доступна персонализация')
                                ->default(false),
                            Forms\Components\KeyValue::make('specifications')
                                ->label('Характеристики (JSON)'),
                        ]),

                    Forms\Components\Textarea::make('description')
                        ->label('Описание')
                        ->columnSpanFull(),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListLuxuryProduct::route('/'),
                'create' => Pages\CreateLuxuryProduct::route('/create'),
                'edit' => Pages\EditLuxuryProduct::route('/{record}/edit'),
                'view' => Pages\ViewLuxuryProduct::route('/{record}'),
            ];
        }
}
