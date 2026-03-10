<?php

namespace App\Filament\Tenant\Resources\Finance;

use App\Domains\Finances\Models\PaymentTransaction;
use Filament\{Tables, Tables\Table, Resources\Resource};

class WalletResource extends Resource {
    protected static ?string $model = PaymentTransaction::class;
    protected static ?string $navigationLabel = 'Wallet & Receipts';
    protected static ?string $navigationGroup = 'Personal';

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
            Tables\Columns\TextColumn::make('amount')->money('RUB'),
            Tables\Columns\BadgeColumn::make('status')->colors(['success'=>'settled']),
            Tables\Columns\ActionColumn::make('receipt')
                ->label('Check')
                ->icon('heroicon-o-document-text')
                ->url(fn($record) => $record->receipt_url, true)
                ->extraAttributes(['class' => 'text-blue-600'])
        ])->query(fn() => PaymentTransaction::where('user_id', auth()->id()));
    }
}
