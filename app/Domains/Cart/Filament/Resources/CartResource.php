<?php declare(strict_types=1);

namespace App\Domains\Cart\Filament\Resources;

use App\Domains\Cart\Models\Cart;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class CartResource extends Resource
{
    protected static ?string $model = Cart::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Commerce';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('seller.email')
                    ->label('Seller')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'ordered' => 'info',
                    }),
                Tables\Columns\TextColumn::make('reserved_until')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'ordered' => 'Ordered',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
    }
}
