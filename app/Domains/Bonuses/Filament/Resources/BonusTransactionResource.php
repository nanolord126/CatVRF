<?php declare(strict_types=1);

namespace App\Domains\Bonuses\Filament\Resources;

use App\Domains\Bonuses\Models\BonusTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class BonusTransactionResource extends Resource
{
    protected static ?string $model = BonusTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Finance';
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
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('rub'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'credited' => 'success',
                        'expired' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('hold_until')
                    ->dateTime()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'credited' => 'Credited',
                        'expired' => 'Expired',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'loyalty' => 'Loyalty',
                        'referral' => 'Referral',
                        'turnover' => 'Turnover',
                        'promo' => 'Promo',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
    }
}
