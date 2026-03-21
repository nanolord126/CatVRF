<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Tickets;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;

final class EventTicketResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Tickets';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('event_name')->required(),
                Forms\Components\TextInput::make('event_date')->required(),
                Forms\Components\TextInput::make('ticket_type')->required(),
                Forms\Components\TextInput::make('price')->numeric()->required(),
                Forms\Components\TextInput::make('quantity')->numeric()->required(),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('event_name')->sortable(),
            Tables\Columns\TextColumn::make('ticket_type')->sortable(),
            Tables\Columns\TextColumn::make('price')->money('RUB')->sortable(),
            Tables\Columns\TextColumn::make('quantity')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => \App\Filament\Tenant\Resources\Tickets\EventTicketResource\Pages\ListEventTickets::route('/')];
    }
}
