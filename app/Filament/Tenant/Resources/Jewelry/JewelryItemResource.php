<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Jewelry;

use App\Domains\Luxury\Jewelry\Models\JewelryItem;
use App\Filament\Tenant\Resources\Jewelry\JewelryItemResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class JewelryItemResource extends Resource
{
    protected static ?string $model = JewelryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-gem';

    protected static ?string $navigationLabel = 'Ювелирные изделия';

    protected static ?string $navigationGroup = 'Jewelry';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required(),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                    ])->columns(2),
                Forms\Components\Section::make('Характеристики')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->options([
                                'ring' => 'Кольцо',
                                'necklace' => 'Ожерелье',
                                'bracelet' => 'Браслет',
                                'earring' => 'Серьги',
                                'pendant' => 'Подвеска',
                                'watch' => 'Часы',
                            ])
                            ->required(),
                        Forms\Components\Select::make('metal')
                            ->label('Металл')
                            ->options([
                                'gold' => 'Золото',
                                'silver' => 'Серебро',
                                'platinum' => 'Платина',
                                'rose_gold' => 'Розовое золото',
                                'white_gold' => 'Белое золото',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('weight_grams')
                            ->label('Вес (граммы)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('purity')
                            ->label('Проба')
                            ->options([
                                '585' => '585',
                                '750' => '750',
                                '925' => '925',
                                '950' => '950',
                                '999' => '999',
                            ])
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Цена и сертификат')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Цена (копейки)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('current_stock')
                            ->label('Запасы')
                            ->numeric(),
                        Forms\Components\Toggle::make('certificate_required')
                            ->label('Требуется сертификат'),
                        Forms\Components\Select::make('certificate_type')
                            ->label('Тип сертификата')
                            ->options([
                                'GIA' => 'GIA',
                                'IGI' => 'IGI',
                                'HRD' => 'HRD',
                            ]),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->badge(),
                Tables\Columns\TextColumn::make('metal')
                    ->label('Металл')
                    ->badge(),
                Tables\Columns\TextColumn::make('purity')
                    ->label('Проба'),
                Tables\Columns\TextColumn::make('weight_grams')
                    ->label('Вес (г)')
                    ->numeric(decimalPlaces: 1),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state) => '₽' . number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Запасы')
                    ->numeric(),
                Tables\Columns\IconColumn::make('certificate_required')
                    ->label('Сертификат')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->options([
                        'ring' => 'Кольцо',
                        'necklace' => 'Ожерелье',
                        'bracelet' => 'Браслет',
                        'earring' => 'Серьги',
                        'pendant' => 'Подвеска',
                        'watch' => 'Часы',
                    ]),
                Tables\Filters\SelectFilter::make('metal')
                    ->label('Металл'),
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
            'index' => Pages\ListJewelryItems::route('/'),
            'create' => Pages\CreateJewelryItem::route('/create'),
            'edit' => Pages\EditJewelryItem::route('/{record}/edit'),
        ];
    }
}
