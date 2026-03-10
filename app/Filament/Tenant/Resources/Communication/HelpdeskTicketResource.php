<?php

namespace App\Filament\Tenant\Resources\Communication;

use App\Domains\Communication\Models\HelpdeskTicket;
use Filament\{Forms, Forms\Form, Resources\Resource, Tables, Tables\Table};
use App\Filament\Tenant\Resources\Communication\Pages;

class HelpdeskTicketResource extends Resource
{
    protected static ?string $model = HelpdeskTicket::class;
    protected static ?string $navigationGroup = 'Support';

    public static function form(Form $form): Form {
        return $form->schema([
            Forms\Components\TextInput::make('subject')->required(),
            Forms\Components\Select::make('priority')->options(['low','mid','high']),
            Forms\Components\Textarea::make('description')->required(),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('subject')->searchable(),
            Tables\Columns\BadgeColumn::make('status')->colors(['success'=>'open','danger'=>'closed']),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ]);
    }

    public static function getPages(): array {
        return ['index' => Pages\ListHelpdeskTickets::route('/')];
    }
}
