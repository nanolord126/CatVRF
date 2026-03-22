<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Beauty\Models\BeautyProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class BeautyProductResource extends Resource
{
    protected static ?string $model = BeautyProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $navigationGroup = 'Beauty';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('salon_id')
                ->relationship('salon', 'name')
                ->required()
                ->label('Салон'),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Название'),
            Forms\Components\TextInput::make('sku')
                ->maxLength(100)
                ->label('Артикул'),
            Forms\Components\TextInput::make('price')
                ->numeric()
                ->required()
                ->label('Цена'),
            Forms\Components\TextInput::make('current_stock')
                ->numeric()
                ->default(0)
                ->label('Остаток'),
            Forms\Components\Select::make('consumable_type')
                ->options([
                    'none' => 'Не расходник',
                    'low' => 'Малый расход',
                    'medium' => 'Средний расход',
                    'high' => 'Высокий расход',
                ])
                ->default('none')
                ->label('Тип расхода'),
            Forms\Components\Toggle::make('is_active')
                ->label('Активен')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название'),
                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->label('Артикул'),
                Tables\Columns\TextColumn::make('salon.name')
                    ->searchable()
                    ->label('Салон'),
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB')
                    ->sortable()
                    ->label('Цена'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->sortable()
                    ->label('Остаток'),
                Tables\Columns\BadgeColumn::make('consumable_type')
                    ->colors([
                        'secondary' => 'none',
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                    ])
                    ->label('Тип'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('salon_id')
                    ->relationship('salon', 'name')
                    ->label('Салон'),
                Tables\Filters\SelectFilter::make('consumable_type')
                    ->options([
                        'none' => 'Не расходник',
                        'low' => 'Малый',
                        'medium' => 'Средний',
                        'high' => 'Высокий',
                    ])
                    ->label('Тип расхода'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);

        if (session()->has('business_card_id')) {
            $query->whereHas('salon', function ($q) {
                $q->where('business_group_id', session('business_card_id'));
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\BeautyProductResource\Pages\ListBeautyProducts::route('/'),
            'create' => \App\Filament\Tenant\Resources\BeautyProductResource\Pages\CreateBeautyProduct::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\BeautyProductResource\Pages\EditBeautyProduct::route('/{record}/edit'),
            'view' => \App\Filament\Tenant\Resources\BeautyProductResource\Pages\ViewBeautyProduct::route('/{record}'),
        ];
    }
}
