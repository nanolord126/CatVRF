<?php declare(strict_types=1);

namespace App\Domains\Commissions\Filament\Resources;

use App\Domains\Commissions\Models\CommissionRecord;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class CommissionRecordResource extends Resource
{
    protected static ?string $model = CommissionRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vertical')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('rub'),
                Tables\Columns\TextColumn::make('commission')
                    ->money('rub'),
                Tables\Columns\TextColumn::make('rate')
                    ->formatStateType(fn ($state) => $state . '%'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                    }),
                Tables\Columns\TextColumn::make('operation_type')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payout_scheduled_for')
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
                        'paid' => 'Paid',
                    ]),
                Tables\Filters\SelectFilter::make('vertical')
                    ->options([
                        'beauty' => 'Beauty',
                        'food' => 'Food',
                        'hotels' => 'Hotels',
                        'auto' => 'Auto',
                        'tickets' => 'Tickets',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
    }
}
