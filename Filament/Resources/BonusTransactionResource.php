<?php

declare(strict_types=1);

namespace Filament\Resources;

use App\Domains\Bonuses\Models\BonusTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * BonusTransactionResource - Filament resource for bonus transactions
 * 
 * Admin interface for viewing and managing bonus transactions.
 */
final class BonusTransactionResource extends Resource
{
    protected static ?string $model = BonusTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Bonuses';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->disabled()
                    ->label('ID'),
                
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->label('User'),
                
                Forms\Components\Select::make('wallet_id')
                    ->relationship('wallet', 'id')
                    ->required()
                    ->label('Bonus Wallet'),
                
                Forms\Components\Select::make('type')
                    ->options([
                        'award' => 'Award',
                        'spend' => 'Spend',
                        'withdraw' => 'Withdraw',
                    ])
                    ->required()
                    ->label('Type'),
                
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->suffix('cents')
                    ->label('Amount'),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'credited' => 'Credited',
                        'spent' => 'Spent',
                        'expired' => 'Expired',
                        'withdrawal_pending' => 'Withdrawal Pending',
                        'withdrawal_completed' => 'Withdrawal Completed',
                        'withdrawal_rejected' => 'Withdrawal Rejected',
                    ])
                    ->required()
                    ->label('Status'),
                
                Forms\Components\DateTimePicker::make('hold_until')
                    ->label('Hold Until'),
                
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires At'),
                
                Forms\Components\KeyValue::make('metadata')
                    ->label('Metadata'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User ID')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'award' => 'success',
                        'spend' => 'warning',
                        'withdraw' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('rub')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'credited' => 'success',
                        'spent' => 'info',
                        'expired' => 'danger',
                        'withdrawal_pending' => 'warning',
                        'withdrawal_completed' => 'success',
                        'withdrawal_rejected' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('hold_until')
                    ->label('Hold Until')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'award' => 'Award',
                        'spend' => 'Spend',
                        'withdraw' => 'Withdraw',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'credited' => 'Credited',
                        'spent' => 'Spent',
                        'expired' => 'Expired',
                        'withdrawal_pending' => 'Withdrawal Pending',
                        'withdrawal_completed' => 'Withdrawal Completed',
                        'withdrawal_rejected' => 'Withdrawal Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBonusTransactions::route('/'),
            'create' => Pages\CreateBonusTransaction::route('/create'),
            'view' => Pages\ViewBonusTransaction::route('/{record}'),
            'edit' => Pages\EditBonusTransaction::route('/{record}/edit'),
        ];
    }
}
