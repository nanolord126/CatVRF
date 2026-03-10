<?php

namespace App\Filament\Tenant\Resources;

use App\Models\B2BPartner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Tenant\Resources\B2BPartnerResource\Pages;

class B2BPartnerResource extends Resource
{
    protected static ?string $model = B2BPartner::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'B2B/Corporate';
    protected static ?string $modelLabel = 'B2B Partner';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Tabs::make('Partner Details')
                    ->tabs([
                        Components\Tabs\Tab::make('General')
                            ->schema([
                                Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255),
                                Components\TextInput::make('inn')
                                    ->label('INN')
                                    ->length(12)
                                    ->numeric(),
                                Components\TextInput::make('kpp')
                                    ->label('KPP')
                                    ->length(9)
                                    ->numeric(),
                                Components\Textarea::make('legal_address')
                                    ->maxLength(500),
                            ]),
                        Components\Tabs\Tab::make('Wallet')
                            ->schema([
                                Components\Placeholder::make('balance')
                                    ->label('Current Balance')
                                    ->content(fn (B2BPartner $record): string => '₽ ' . number_format($record->balance, 2)),
                                Components\Actions::make([
                                    Components\Actions\Action::make('deposit')
                                        ->form([
                                            Components\TextInput::make('amount')
                                                ->numeric()
                                                ->required()
                                                ->prefix('₽'),
                                        ])
                                        ->action(function (B2BPartner $record, array $data) {
                                            $record->deposit($data['amount']);
                                        }),
                                ])->visible(fn ($record) => $record !== null),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Columns\TextColumn::make('inn')
                    ->label('INN')
                    ->searchable(),
                Columns\TextColumn::make('balance')
                    ->getStateUsing(fn (B2BPartner $record) => $record->balance)
                    ->money('RUB'),
                Columns\TextColumn::make('contracts_count')
                    ->label('Active Contracts')
                    ->counts('contracts')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relations will go here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListB2BPartners::route('/'),
            'create' => Pages\CreateB2BPartner::route('/create'),
            'edit' => Pages\EditB2BPartner::route('/{record}/edit'),
        ];
    }
}
