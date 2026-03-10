<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\PayoutResource\Pages;
use Modules\Payments\Models\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Payout Details')
                    ->schema([
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                if ($get('contract_type') === 'gph') {
                                    $tax = round($state * 0.13, 2);
                                    $set('tax_amount', $tax);
                                    $set('net_amount', $state - $tax);
                                } else {
                                    $set('tax_amount', 0);
                                    $set('net_amount', $state);
                                }
                            }),
                        Select::make('contract_type')
                            ->options([
                                'standard' => 'Standard',
                                'gph' => 'GPH Contract',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                $amount = $get('amount') ?: 0;
                                if ($state === 'gph') {
                                    $tax = round($amount * 0.13, 2);
                                    $set('tax_amount', $tax);
                                    $set('net_amount', $amount - $tax);
                                    $set('notes', 'NDFL 13% deducted. Other social contributions are the responsibility of the individual.');
                                } else {
                                    $set('tax_amount', 0);
                                    $set('net_amount', $amount);
                                    $set('notes', '');
                                }
                            }),
                        TextInput::make('tax_amount')->readOnly()->numeric(),
                        TextInput::make('net_amount')->readOnly()->numeric(),
                        TextInput::make('notes')->columnSpanFull()->readOnly(),
                        Placeholder::make('disclaimer')
                            ->content('NDFL 13% deducted automatically for GPH contracts. Platform commission is +20% for agency-flow.')
                            ->hidden(fn ($get) => $get('contract_type') !== 'gph'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('amount')->money('RUB')->sortable(),
                TextColumn::make('tax_amount')->money('RUB')->label('NDFL 13%'),
                TextColumn::make('net_amount')->money('RUB')->label('Net Payout'),
                TextColumn::make('contract_type')->badge(),
                TextColumn::make('status'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'edit' => Pages\EditPayout::route('/{record}/edit'),
        ];
    }
}
