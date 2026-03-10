<?php

namespace App\Filament\Tenant\Resources;

use App\Models\Wishlist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;

class WishlistResource extends Resource
{
    protected static ?string $model = Wishlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Маркетплейс';

    protected static ?string $modelLabel = 'Список желаний';

    protected static ?string $pluralModelLabel = 'Списки желаний';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Toggle::make('is_public')
                    ->label('Публичный доступ')
                    ->default(false),
                
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->required(),
                        TextInput::make('price_at_addition')
                            ->label('Цена при добавлении')
                            ->numeric()
                            ->required(),
                        TextInput::make('collected_amount')
                            ->label('Собрано средств')
                            ->numeric()
                            ->disabled(),
                        Toggle::make('is_fully_paid')
                            ->label('Оплачено полностью')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->copyable(),
                IconColumn::make('is_public')->boolean(),
                TextColumn::make('items_count')->counts('items')->label('Товаров'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_public'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('share')
                    ->label('Поделиться')
                    ->icon('heroicon-o-share')
                    ->color('info')
                    ->url(fn (Wishlist $record) => route('wishlist.public', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (Wishlist $record) => $record->is_public),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\WishlistResource\Pages\ListWishlists::route('/'),
        ];
    }
}
