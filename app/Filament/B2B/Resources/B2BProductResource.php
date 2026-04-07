<?php declare(strict_types=1);

namespace App\Filament\B2B\Resources;

use App\Filament\B2B\Resources\B2BProductResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * B2BProductResource — просмотр каталога с оптовыми ценами.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функции:
 * - Просмотр каталога с B2B-ценами и MOQ
 * - Фильтр по вертикали, наличию, цене
 * - Быстрое добавление в заказ
 * - Только чтение (create/edit — только у тенанта)
 */
final class B2BProductResource extends Resource
{
    protected static ?string $model           = \App\Models\Product::class;
    protected static ?string $navigationIcon  = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Каталог (B2B)';
    protected static ?string $slug            = 'b2b-products';
    protected static ?int    $navigationSort  = 3;
    protected static ?string $navigationGroup = 'Продажи';

    public static function getEloquentQuery(): Builder
    {
        // Только активные товары с B2B-ценой
        return parent::getEloquentQuery()
            ->where('is_active', true)
            ->whereNotNull('b2b_price');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Товар')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Название')
                        ->disabled(),

                    Forms\Components\TextInput::make('b2b_price')
                        ->label('Оптовая цена (коп.)')
                        ->numeric()
                        ->disabled(),

                    Forms\Components\TextInput::make('moq')
                        ->label('Минимальный заказ (MOQ)')
                        ->numeric()
                        ->disabled(),

                    Forms\Components\TextInput::make('stock')
                        ->label('Остаток')
                        ->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    ->size(48)
                    ->defaultImageUrl('/images/placeholder.png'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vertical')
                    ->label('Вертикаль')
                    ->badge(),

                Tables\Columns\TextColumn::make('b2b_price')
                    ->label('B2B цена')
                    ->formatStateUsing(static fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),

                Tables\Columns\TextColumn::make('moq')
                    ->label('MOQ')
                    ->suffix(' шт.')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Остаток')
                    ->formatStateUsing(static fn ($state) => $state > 0 ? $state . ' шт.' : '–')
                    ->color(static fn ($state) => $state <= 0 ? 'danger' : ($state < 10 ? 'warning' : 'success')),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options([
                        'beauty'    => 'Beauty',
                        'food'      => 'Food',
                        'furniture' => 'Furniture',
                        'fashion'   => 'Fashion',
                        'fitness'   => 'Fitness',
                    ]),

                Tables\Filters\Filter::make('in_stock')
                    ->label('Только в наличии')
                    ->query(static fn (Builder $query) => $query->where('stock', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListB2BProducts::route('/'),
            'view'  => Pages\ViewB2BProduct::route('/{record}'),
        ];
    }
}
