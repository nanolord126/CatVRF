<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Filament\Resources;

use App\Domains\EventPlanning\Entertainment\Models\EntertainmentEvent;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class EntertainmentEventResource extends Resource
{
    protected static ?string $model = EntertainmentEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('venue_id')
                    ->relationship('venue', 'name')
                    ->required(),
                Forms\Components\Select::make('entertainer_id')
                    ->relationship('entertainer', 'full_name')
                    ->nullable(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\RichEditor::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('event_type')
                    ->required(),
                Forms\Components\DateTimePicker::make('event_date_start')
                    ->required(),
                Forms\Components\DateTimePicker::make('event_date_end')
                    ->required(),
                Forms\Components\TextInput::make('total_seats')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('base_price')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('vip_price')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('venue.name'),
                Tables\Columns\TextColumn::make('entertainer.full_name'),
                Tables\Columns\TextColumn::make('base_price')
                    ->money('RUB'),
                Tables\Columns\TextColumn::make('event_date_start')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['pending' => 'warning', 'completed' => 'success', 'cancelled' => 'danger']),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentEventResource\Pages\ListEntertainmentEvents::class,
            'create' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentEventResource\Pages\CreateEntertainmentEvent::class,
            'edit' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentEventResource\Pages\EditEntertainmentEvent::class,
        ];
    }
}
