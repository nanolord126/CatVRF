<?php

namespace App\Filament\Tenant\Resources;

use App\Models\B2BInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use App\Filament\Tenant\Resources\B2BInvoiceResource\Pages;

class B2BInvoiceResource extends Resource
{
    protected static ?string $model = B2BInvoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'B2B/Corporate';
    protected static ?string $modelLabel = 'Corporate Invoice';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('invoice_number')->searchable(),
                Columns\TextColumn::make('order.partner.name')->label('Partner')->sortable(),
                Columns\TextColumn::make('amount')->money('RUB'),
                Columns\TextColumn::make('due_date')->date(),
                Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'unpaid',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Confirm Payment')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn (B2BInvoice $record) => $record->markAsPaid())
                    ->visible(fn ($record) => $record->status === 'unpaid'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageB2BInvoices::route('/'),
        ];
    }
}
