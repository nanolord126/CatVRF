<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Restaurant\Domain\ValueObjects\Money;
use Modules\Restaurant\Infrastructure\Models\MenuItemModel;

final class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItemModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Блюда меню';

    protected static ?string $modelLabel = 'Блюдо';

    protected static ?string $navigationGroup = 'Ресторан';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Категория'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Название'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->label('Описание'),

                        Forms\Components\FileUpload::make('image_url')
                            ->image()
                            ->directory('menu-items')
                            ->label('Изображение'),

                        Forms\Components\TextInput::make('price_rubles')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->label('Цена (₽)')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('price_kopecks', (int) round($state * 100));
                            }),

                        Forms\Components\Hidden::make('price_kopecks'),

                        Forms\Components\TextInput::make('sku')
                            ->unique(ignoreRecord: true)
                            ->label('SKU/Артикул'),

                        Forms\Components\TextInput::make('preparation_time')
                            ->numeric()
                            ->default(15)
                            ->minValue(1)
                            ->suffix('мин')
                            ->label('Время приготовления'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активно'),

                        Forms\Components\Toggle::make('is_available')
                            ->default(true)
                            ->label('Доступно для заказа'),

                        Forms\Components\Toggle::make('is_featured')
                            ->default(false)
                            ->label('Рекомендуемое блюдо'),

                        Forms\Components\Textarea::make('allergens')
                            ->rows(2)
                            ->label('Аллергены'),

                        Forms\Components\Textarea::make('nutritional_info')
                            ->rows(3)
                            ->label('Информация о питательности'),

                        Forms\Components\TextInput::make('calories')
                            ->numeric()
                            ->default(0)
                            ->label('Калории (ккал)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->circular()
                    ->label(''),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название'),

                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->sortable()
                    ->label('Категория'),

                Tables\Columns\TextColumn::make('price_kopecks')
                    ->money('rubles', divideBy: 100)
                    ->sortable()
                    ->label('Цена'),

                Tables\Columns\TextColumn::make('preparation_time')
                    ->sortable()
                    ->suffix(' мин')
                    ->label('Время'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активно'),

                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('Доступно'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Рекомендуемое'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Создано')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Категория'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активно'),

                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Доступно'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Рекомендуемое'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Restaurant\Presentation\Resources\MenuItemResource\Pages\ListMenuItems::route('/'),
            'create' => \Modules\Restaurant\Presentation\Resources\MenuItemResource\Pages\CreateMenuItem::route('/create'),
            'edit' => \Modules\Restaurant\Presentation\Resources\MenuItemResource\Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
