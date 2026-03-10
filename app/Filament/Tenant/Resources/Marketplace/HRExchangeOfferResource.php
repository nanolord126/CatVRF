<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\HRExchangeOfferResource\Pages;
use App\Models\Tenants\HRExchangeOffer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HRExchangeOfferResource extends Resource
{
    protected static ?string $model = HRExchangeOffer::class;
    protected static ?string $navigationGroup = 'Ecosystem';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $label = 'HR Exchange';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')->relationship('user', 'name')->searchable()->required(),
                Forms\Components\TextInput::make('role_code')->required(),
                Forms\Components\TextInput::make('hourly_rate')->numeric()->prefix('$')->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'booked' => 'Booked',
                        'completed' => 'Completed',
                    ])->default('open'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->sortable(),
                Tables\Columns\TextColumn::make('role_code')->sortable(),
                Tables\Columns\TextColumn::make('hourly_rate')->money('USD'),
                Tables\Columns\BadgeColumn::make('status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHRExchangeOffers::route('/'),
            'create' => Pages\CreateHRExchangeOffer::route('/create'),
            'edit' => Pages\EditHRExchangeOffer::route('/{record}/edit'),
        ];
    }
}
