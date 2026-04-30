<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hotels\Infrastructure\Models\VenueModel;
use Modules\Hotels\Filament\Resources\VenueResource\Pages;

final class VenueResource extends Resource
{
    protected static ?string $model = VenueModel::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Hotels CRM';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Venue Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\TextInput::make('address')
                            ->label('Address')
                            ->required(),
                        Forms\Components\TextInput::make('city')
                            ->label('City')
                            ->required(),
                        Forms\Components\TextInput::make('country')
                            ->label('Country')
                            ->default('RU')
                            ->maxLength(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email(),
                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->url(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Hotel Details')
                    ->schema([
                        Forms\Components\Select::make('star_rating')
                            ->label('Star Rating')
                            ->options([
                                1 => '1 Star',
                                2 => '2 Stars',
                                3 => '3 Stars',
                                4 => '4 Stars',
                                5 => '5 Stars',
                            ])
                            ->required()
                            ->default(3),
                        Forms\Components\Select::make('property_type')
                            ->label('Property Type')
                            ->options([
                                'hotel' => 'Hotel',
                                'hostel' => 'Hostel',
                                'apartment' => 'Apartment',
                                'guesthouse' => 'Guest House',
                            ])
                            ->required()
                            ->default('hotel'),
                        Forms\Components\TextInput::make('total_rooms')
                            ->label('Total Rooms')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('total_floors')
                            ->label('Total Floors')
                            ->numeric()
                            ->default(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Chain Information')
                    ->schema([
                        Forms\Components\Toggle::make('is_chain')
                            ->label('Is Part of Chain'),
                        Forms\Components\Select::make('parent_venue_id')
                            ->label('Parent Venue')
                            ->relationship('parentVenue', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Policies')
                    ->schema([
                        Forms\Components\KeyValue::make('checkin_policy')
                            ->label('Check-in Policy')
                            ->keyLabel('Rule')
                            ->valueLabel('Value'),
                        Forms\Components\KeyValue::make('checkout_policy')
                            ->label('Check-out Policy')
                            ->keyLabel('Rule')
                            ->valueLabel('Value'),
                        Forms\Components\KeyValue::make('cancellation_policy')
                            ->label('Cancellation Policy')
                            ->keyLabel('Rule')
                            ->valueLabel('Value'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Venue Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('City')
                    ->searchable(),
                Tables\Columns\TextColumn::make('star_rating')
                    ->label('Stars')
                    ->formatStateUsing(fn (string $state): string => str_repeat('★', (int) $state)),
                Tables\Columns\TextColumn::make('property_type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_rooms')
                    ->label('Rooms')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('property_type')
                    ->label('Property Type')
                    ->options([
                        'hotel' => 'Hotel',
                        'hostel' => 'Hostel',
                        'apartment' => 'Apartment',
                        'guesthouse' => 'Guest House',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenues::route('/'),
            'create' => Pages\CreateVenue::route('/create'),
            'view' => Pages\ViewVenue::route('/{record}'),
            'edit' => Pages\EditVenue::route('/{record}/edit'),
        ];
    }
}
