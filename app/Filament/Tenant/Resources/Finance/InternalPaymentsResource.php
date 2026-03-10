<?php

namespace App\Filament\Tenant\Resources\Finance;

use App\Domains\Finances\Models\PaymentTransaction;
use Filament\{Tables, Tables\Table, Resources\Resource};

class InternalPaymentsResource extends Resource {
    protected static ?string $model = PaymentTransaction::class;
    protected static ?string $navigationLabel = 'Payments & Settlements';
    protected static ?string $navigationGroup = 'Financial Services';

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('payment_id')->searchable(),
            Tables\Columns\TextColumn::make('amount')->money('RUB'),
            Tables\Columns\TextColumn::make('fiscal_number')->label('FD #'),
            Tables\Columns\BadgeColumn::make('status')->colors(['success'=>'settled']),
            Tables\Columns\TextColumn::make('correlation_id')->label('Audit Trace'),
            Tables\Columns\ActionColumn::make('view_receipt')->url(fn($r) => $r->receipt_url, true)
        ]);
    }
}
