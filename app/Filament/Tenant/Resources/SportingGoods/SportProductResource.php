<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportingGoods;

use App\Domains\SportingGoods\Models\SportProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class SportProductResource extends Resource
{
    protected static ?string $model = SportProduct::class;
    protected static ?string $navigationGroup = 'SportingGoods';
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')->schema([
                Forms\Components\TextInput::make('name')->required()->label('Название'),
                Forms\Components\TextInput::make('sku')->required()->unique()->label('SKU'),
                Forms\Components\TextInput::make('description')->label('Описание'),
            ]),
            Forms\Components\Section::make('Спорт и размеры')->schema([
                Forms\Components\Select::make('sport_type')
                    ->options([
                        'football' => 'Football',
                        'basketball' => 'Basketball',
                        'tennis' => 'Tennis',
                        'swimming' => 'Swimming',
                        'running' => 'Running',
                        'cycling' => 'Cycling',
                        'gym' => 'Gym',
                        'outdoor' => 'Outdoor',
                    ])
                    ->required(),
            ]),
            Forms\Components\Section::make('Цена и запас')->schema([
                Forms\Components\TextInput::make('price')->numeric()->required()->label('Цена (копейки)'),
                Forms\Components\TextInput::make('current_stock')->numeric()->required()->label('Запас'),
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
            Tables\Columns\TextColumn::make('sport_type')->badge()->label('Вид спорта'),
            Tables\Columns\TextColumn::make('price')
                ->formatStateUsing(fn ($state) => '₽' . number_format($state / 100, 2))
                ->label('Цена'),
            Tables\Columns\TextColumn::make('current_stock')->label('Запас'),
            Tables\Columns\TextColumn::make('rating')->label('Рейтинг'),
            Tables\Columns\TextColumn::make('status')->badge()->label('Статус'),
        ])->filters([
            Tables\Filters\SelectFilter::make('sport_type'),
            Tables\Filters\SelectFilter::make('status'),
        ]);
    }
}
