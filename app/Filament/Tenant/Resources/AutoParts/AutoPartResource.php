<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoParts;

use App\Domains\AutoParts\Models\AutoPart;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class AutoPartResource extends Resource
{
    protected static ?string $model = AutoPart::class;
    protected static ?string $navigationGroup = 'AutoParts';
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')->schema([
                Forms\Components\TextInput::make('name')->required()->label('Название'),
                Forms\Components\TextInput::make('sku')->required()->unique()->label('SKU'),
                Forms\Components\TextInput::make('description')->label('Описание'),
            ]),
            Forms\Components\Section::make('Категория')->schema([
                Forms\Components\Select::make('category')->options([
                    'engine' => 'Engine', 'suspension' => 'Suspension', 'brakes' => 'Brakes',
                    'electrical' => 'Electrical', 'body' => 'Body', 'interior' => 'Interior', 'accessories' => 'Accessories'
                ])->required(),
            ]),
            Forms\Components\Section::make('Цена и запас')->schema([
                Forms\Components\TextInput::make('price')->numeric()->required()->label('Цена (копейки)'),
                Forms\Components\TextInput::make('current_stock')->numeric()->required()->label('Запас'),
            ]),
            Forms\Components\Section::make('Статус')->schema([
                Forms\Components\Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->label('Название'),
            Tables\Columns\TextColumn::make('category')->badge()->label('Категория'),
            Tables\Columns\TextColumn::make('price')->formatStateUsing(fn ($state) => '₽' . number_format($state / 100, 2))->label('Цена'),
            Tables\Columns\TextColumn::make('current_stock')->label('Запас'),
            Tables\Columns\TextColumn::make('status')->badge()->label('Статус'),
        ])->filters([
            Tables\Filters\SelectFilter::make('category'),
            Tables\Filters\SelectFilter::make('status'),
        ]);
    }
}
