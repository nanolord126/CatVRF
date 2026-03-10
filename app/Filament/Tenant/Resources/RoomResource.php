<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\RoomResource\Pages;
use Modules\Hotels\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\TearOffFilter;
use Filament\Tables\Filters\Filter;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup = 'Hotel Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Tabs::make('Room Management')
                    ->tabs([
                        Components\Tabs\Tab::make('General Info')
                            ->schema([
                                Components\TextInput::make('number')
                                    ->required()
                                    ->maxLength(50),
                                Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Components\Select::make('room_type')
                                    ->options([
                                        'standard' => 'Standard',
                                        'deluxe' => 'Deluxe',
                                        'suite' => 'Suite',
                                        'family' => 'Family Room',
                                        'economy' => 'Economy',
                                    ])
                                    ->required(),
                                Components\TextInput::make('square_meters')
                                    ->numeric()
                                    ->suffix('m²'),
                                Components\TextInput::make('price')
                                    ->numeric()
                                    ->prefix('₽')
                                    ->required(),
                            ]),
                        Components\Tabs\Tab::make('Amenities & Media')
                            ->schema([
                                Components\CheckboxList::make('amenities')
                                    ->options([
                                        'wifi' => 'Free Wi-Fi',
                                        'tv' => 'Smart TV',
                                        'ac' => 'Air Conditioning',
                                        'minibar' => 'Minibar',
                                        'safe' => 'In-room Safe',
                                        'balcony' => 'Balcony',
                                        'shower' => 'Shower',
                                        'bath' => 'Bathtub',
                                    ])
                                    ->columns(2),
                                Components\FileUpload::make('photos')
                                    ->multiple()
                                    ->image()
                                    ->directory('room-photos'),
                                Components\Textarea::make('description')
                                    ->rows(3),
                            ]),
                        Components\Tabs\Tab::make('Status & Housekeeping')
                            ->schema([
                                Components\Select::make('status')
                                    ->options([
                                        'available' => 'Available',
                                        'maintenance' => 'Maintenance',
                                        'occupied' => 'Occupied',
                                    ])
                                    ->default('available')
                                    ->required(),
                                Components\Toggle::make('requires_housekeeping')
                                    ->label('Requires Cleaning (Dirty)')
                                    ->default(false),
                                Components\Toggle::make('is_clean')
                                    ->label('Laundry / Linen Status OK')
                                    ->default(true),
                                Components\DateTimePicker::make('last_cleaned_at')
                                    ->disabled(),
                            ]),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\ImageColumn::make('photos')
                    ->circular()
                    ->limit(1)
                    ->label('Photo'),
                Columns\TextColumn::make('number')
                    ->sortable()
                    ->searchable(),
                Columns\TextColumn::make('room_type')
                    ->badge()
                    ->sortable(),
                Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'available',
                        'danger' => 'maintenance',
                        'warning' => 'occupied',
                    ]),
                Columns\IconColumn::make('requires_housekeeping')
                    ->boolean()
                    ->label('Dirty')
                    ->sortable(),
                Columns\IconColumn::make('is_clean')
                    ->boolean()
                    ->label('Clean')
                    ->sortable(),
                Columns\TextColumn::make('price')
                    ->money('RUB')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('room_type'),
                Tables\Filters\SelectFilter::make('status'),
                Tables\Filters\TearOffFilter::make('requires_housekeeping'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('mark_cleaned')
                        ->icon('heroicon-o-sparkles')
                        ->color('success')
                        ->label('Mark as Cleaned')
                        ->action(fn (Room $record) => $record->update([
                            'requires_housekeeping' => false,
                            'is_clean' => true,
                            'last_cleaned_at' => now(),
                        ]))
                        ->visible(fn (Room $record) => $record->requires_housekeeping || !$record->is_clean),
                ]),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => \Filament\Facades\Filament::auth()->user()?->can('delete_rooms')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => \Filament\Facades\Filament::auth()->user()?->can('delete_rooms')),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return \Filament\Facades\Filament::auth()->user()?->can('view_rooms') ?? false;
    }

    public static function canCreate(): bool
    {
        return \Filament\Facades\Filament::auth()->user()?->can('create_rooms') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return \Filament\Facades\Filament::auth()->user()?->can('edit_rooms') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRooms::route('/'),
        ];
    }
}
