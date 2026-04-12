<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels;

use App\Domains\Hotels\Models\Room;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use App\Filament\Tenant\Resources\Hotels\RoomResource\Pages;

final class RoomResource extends Resource
{
    protected static ?string $model = Room::class;
    protected static ?string $navigationIcon = "heroicon-o-key";
    protected static ?string $navigationGroup = "Hotels & Travel";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make("Room Configuration")->schema([
                Select::make("hotel_id")->relationship("hotel", "name")->required(),
                TextInput::make("room_number")->required()->maxLength(50),
                TextInput::make("room_type")->required()->maxLength(100),
                Textarea::make("description")->maxLength(1000),
                TextInput::make("price_per_night")->numeric()->required(),
                TextInput::make("capacity")->numeric()->default(2)->required(),
                Checkbox::make("is_available")->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("hotel.name")->sortable()->searchable(),
                TextColumn::make("room_number")->searchable()->sortable(),
                TextColumn::make("room_type")->searchable(),
                TextColumn::make("price_per_night")->sortable(),
                TextColumn::make("capacity")->sortable(),
                BooleanColumn::make("is_available"),
            ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListRooms::route("/"),
            "create" => Pages\CreateRoom::route("/create"),
            "edit" => Pages\EditRoom::route("/{record}/edit"),
        ];
    }
}
