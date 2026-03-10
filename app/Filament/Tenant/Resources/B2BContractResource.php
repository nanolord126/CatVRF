<?php

namespace App\Filament\Tenant\Resources;

use App\Models\B2BContract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use App\Filament\Tenant\Resources\B2BContractResource\Pages;

class B2BContractResource extends Resource
{
    protected static ?string $model = B2BContract::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'B2B/Corporate';
    protected static ?string $modelLabel = 'B2B Contract';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Select::make('partner_id')
                    ->relationship('partner', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Components\TextInput::make('contract_number')
                    ->required()
                    ->unique(ignoreRecord: true),
                Components\DatePicker::make('start_date')
                    ->required(),
                Components\DatePicker::make('end_date'),
                Components\TextInput::make('discount_percent')
                    ->numeric()
                    ->default(0)
                    ->suffix('%'),
                Components\TextInput::make('credit_limit')
                    ->numeric()
                    ->default(0)
                    ->prefix('₽'),
                Components\TextInput::make('payment_terms_days')
                    ->label('Payment terms (days)')
                    ->numeric()
                    ->default(0),
                Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'terminated' => 'Terminated',
                    ])
                    ->required()
                    ->default('active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('contract_number')->searchable(),
                Columns\TextColumn::make('partner.name')->sortable(),
                Columns\TextColumn::make('discount_percent')->suffix('%'),
                Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'terminated',
                        'warning' => 'expired',
                    ]),
                Columns\TextColumn::make('start_date')->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'terminated' => 'Terminated',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageB2BContracts::route('/'),
        ];
    }
}
