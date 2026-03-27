<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalSupplies;

use App\Domains\Pharmacy\MedicalSupplies\Models\MedicalSupply;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class MedicalSupplyResource extends Resource
{
    protected static ?string $model = MedicalSupply::class;
    protected static ?string $navigationGroup = 'MedicalSupplies';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')->schema([
                Forms\Components\TextInput::make('name')->required()->label('Название'),
                Forms\Components\TextInput::make('sku')->required()->unique()->label('SKU'),
                Forms\Components\TextInput::make('description')->label('Описание'),
            ]),
            Forms\Components\Section::make('Категория')->schema([
                Forms\Components\Select::make('category')
                    ->options([
                        'equipment' => 'Equipment',
                        'consumables' => 'Consumables',
                        'bandages' => 'Bandages',
                        'syringes' => 'Syringes',
                        'instruments' => 'Instruments',
                        'medications' => 'Medications',
                    ])
                    ->required(),
            ]),
            Forms\Components\Section::make('Цена и запас')->schema([
                Forms\Components\TextInput::make('price')->numeric()->required()->label('Цена (копейки)'),
                Forms\Components\TextInput::make('current_stock')->numeric()->required()->label('Текущий запас'),
                Forms\Components\TextInput::make('min_stock_threshold')->numeric()->required()->label('Минимальный порог'),
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
            Tables\Columns\TextColumn::make('price')
                ->formatStateUsing(fn ($state) => '₽' . number_format($state / 100, 2))
                ->label('Цена'),
            Tables\Columns\TextColumn::make('current_stock')->label('Запас'),
            Tables\Columns\TextColumn::make('min_stock_threshold')->label('Минимум'),
            Tables\Columns\TextColumn::make('status')->badge()->label('Статус'),
        ])->filters([
            Tables\Filters\SelectFilter::make('category'),
            Tables\Filters\SelectFilter::make('status'),
        ]);
    }
}
