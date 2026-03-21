<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Gifts;

use App\Domains\Gifts\Models\GiftProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class GiftProductResource extends Resource
{
    protected static ?string $model = GiftProduct::class;
    protected static ?string $navigationGroup = 'Gifts';
    protected static ?string $navigationIcon = 'heroicon-o-gift';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')->schema([
                Forms\Components\TextInput::make('name')->required()->label('Название'),
                Forms\Components\TextInput::make('sku')->required()->unique()->label('SKU'),
                Forms\Components\TextInput::make('description')->label('Описание'),
            ]),
            Forms\Components\Section::make('Категория и повод')->schema([
                Forms\Components\Select::make('category')
                    ->options(['experience' => 'Experience', 'gadget' => 'Gadget', 'luxury' => 'Luxury', 'budget' => 'Budget', 'romantic' => 'Romantic', 'kids' => 'Kids'])
                    ->required(),
                Forms\Components\Select::make('occasion')
                    ->options(['birthday' => 'Birthday', 'anniversary' => 'Anniversary', 'wedding' => 'Wedding', 'christmas' => 'Christmas', 'new_year' => 'New Year', 'any' => 'Any'])
                    ->required(),
            ]),
            Forms\Components\Section::make('Цена и запас')->schema([
                Forms\Components\TextInput::make('price')->numeric()->required()->label('Цена (копейки)'),
                Forms\Components\TextInput::make('current_stock')->numeric()->required()->label('Запас'),
                Forms\Components\Toggle::make('gift_wrap_available')->label('Подарочная упаковка'),
            ]),
            Forms\Components\Section::make('Рейтинг и статус')->schema([
                Forms\Components\TextInput::make('rating')->numeric()->label('Рейтинг'),
                Forms\Components\TextInput::make('review_count')->numeric()->label('Количество отзывов'),
                Forms\Components\Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->label('Название'),
            Tables\Columns\TextColumn::make('category')->badge()->label('Категория'),
            Tables\Columns\TextColumn::make('occasion')->badge()->label('Повод'),
            Tables\Columns\TextColumn::make('price')
                ->formatStateUsing(fn ($state) => '₽' . number_format($state / 100, 2))
                ->label('Цена'),
            Tables\Columns\TextColumn::make('current_stock')->label('Запас'),
            Tables\Columns\TextColumn::make('rating')->label('Рейтинг'),
            Tables\Columns\TextColumn::make('status')->badge()->label('Статус'),
        ])->filters([
            Tables\Filters\SelectFilter::make('category'),
            Tables\Filters\SelectFilter::make('occasion'),
        ]);
    }
}
