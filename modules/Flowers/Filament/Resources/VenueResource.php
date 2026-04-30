<?php

declare(strict_types=1);

namespace Modules\Flowers\Filament\Resources;

use Modules\Flowers\Infrastructure\Models\VenueModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class VenueResource extends Resource
{
    protected static ?string $model = VenueModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Flowers CRM';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Venue Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->required()
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Location')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->step(8),
                        Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->step(8),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Working Hours')
                    ->schema([
                        Forms\Components\KeyValue::make('working_hours')
                            ->keyLabel('Day')
                            ->valueLabel('Hours (e.g., 09:00-18:00)')
                            ->default([
                                'monday' => '09:00-18:00',
                                'tuesday' => '09:00-18:00',
                                'wednesday' => '09:00-18:00',
                                'thursday' => '09:00-18:00',
                                'friday' => '09:00-18:00',
                                'saturday' => '10:00-16:00',
                                'sunday' => 'closed',
                            ]),
                    ]),

                Forms\Components\Section::make('Delivery Settings')
                    ->schema([
                        Forms\Components\Toggle::make('supports_delivery')
                            ->default(true),
                        Forms\Components\Toggle::make('supports_pickup')
                            ->default(true),
                        Forms\Components\TextInput::make('delivery_radius_km')
                            ->numeric()
                            ->default(15)
                            ->suffix('km'),
                        Forms\Components\TextInput::make('preparation_time_minutes')
                            ->numeric()
                            ->default(30)
                            ->suffix('minutes'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active venue')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                BadgeColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                BadgeColumn::make('supports_delivery')
                    ->boolean()
                    ->label('Delivery'),
                BadgeColumn::make('supports_pickup')
                    ->boolean()
                    ->label('Pickup'),
                TextColumn::make('delivery_radius_km')
                    ->sortable()
                    ->label('Radius (km)')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('city')
                    ->options([
                        'moscow' => 'Moscow',
                        'saint-petersburg' => 'Saint Petersburg',
                        'novosibirsk' => 'Novosibirsk',
                        'yekaterinburg' => 'Yekaterinburg',
                        'kazan' => 'Kazan',
                        'nizhny-novgorod' => 'Nizhny Novgorod',
                    ]),
                Tables\Filters\Filter::make('is_active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
                Tables\Filters\Filter::make('supports_delivery')
                    ->query(fn (Builder $query): Builder => $query->where('supports_delivery', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'flowers' => Tables\Relations\RelationManager::class,
            'products' => Tables\Relations\RelationManager::class,
            'florists' => Tables\Relations\RelationManager::class,
            'orders' => Tables\Relations\RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Flowers\Filament\Resources\VenueResource\Pages\ListVenues::route('/'),
            'create' => \Modules\Flowers\Filament\Resources\VenueResource\Pages\CreateVenue::route('/create'),
            'view' => \Modules\Flowers\Filament\Resources\VenueResource\Pages\ViewVenue::route('/{record}'),
            'edit' => \Modules\Flowers\Filament\Resources\VenueResource\Pages\EditVenue::route('/{record}/edit'),
        ];
    }
}
