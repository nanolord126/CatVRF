<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\EventBookingResource\Pages;
use App\Models\Tenants\EventBooking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EventBookingResource extends Resource
{
    protected static ?string $model = EventBooking::class;
    protected static ?string $navigationGroup = 'Marketplace';
    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('event_name')->required(),
                Forms\Components\TextInput::make('price')->numeric()->prefix('$')->required(),
                Forms\Components\TextInput::make('tickets_count')->numeric()->minValue(1)->required(),
                Forms\Components\Hidden::make('correlation_id')->default(fn () => (string) Str::uuid()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_name')->searchable(),
                Tables\Columns\TextColumn::make('price')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('tickets_count')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventBookings::route('/'),
            'create' => Pages\CreateEventBooking::route('/create'),
            'edit' => Pages\EditEventBooking::route('/{record}/edit'),
        ];
    }
}
