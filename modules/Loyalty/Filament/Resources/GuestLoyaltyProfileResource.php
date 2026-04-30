<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Infrastructure\Models\GuestLoyaltyProfileModel;

final class GuestLoyaltyProfileResource extends Resource
{
    protected static ?string $model = GuestLoyaltyProfileModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile Information')
                    ->schema([
                        Forms\Components\Select::make('loyalty_program_id')
                            ->relationship('program', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('guest_id')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('user_id')
                            ->numeric()
                            ->nullable(),
                        Forms\Components\Select::make('current_tier_id')
                            ->relationship('currentTier', 'name')
                            ->searchable()
                            ->nullable(),
                        Forms\Components\DatePicker::make('birthday')
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Points Balance')
                    ->schema([
                        Forms\Components\TextInput::make('available_points')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('earned_points')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('redeemed_points')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Activity Tracking')
                    ->schema([
                        Forms\Components\TextInput::make('total_spend')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('total_visits')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('enrolled_at')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('last_activity_at')
                            ->disabled(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Preferences')
                    ->schema([
                        Forms\Components\Textarea::make('preferences')
                            ->rows(3)
                            ->helperText('Guest preferences in JSON format'),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('program.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guest_id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('currentTier.name')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Bronze' => 'gray',
                        'Silver' => 'slate',
                        'Gold' => 'amber',
                        'Platinum' => 'purple',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('available_points')
                    ->numeric()
                    ->sortable()
                    ->description(fn (GuestLoyaltyProfileModel $record): string => 'Earned: ' . $record->earned_points),
                Tables\Columns\TextColumn::make('total_spend')
                    ->money('rub')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_visits')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('enrolled_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_activity_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('available_points', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('loyalty_program_id')
                    ->relationship('program', 'name'),
                Tables\Filters\SelectFilter::make('current_tier_id')
                    ->relationship('currentTier', 'name'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Add bulk actions as needed
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Loyalty\Filament\Resources\GuestLoyaltyProfileResource\Pages\ListGuestLoyaltyProfiles::route('/'),
            'create' => \Modules\Loyalty\Filament\Resources\GuestLoyaltyProfileResource\Pages\CreateGuestLoyaltyProfile::route('/create'),
            'view' => \Modules\Loyalty\Filament\Resources\GuestLoyaltyProfileResource\Pages\ViewGuestLoyaltyProfile::route('/{record}'),
            'edit' => \Modules\Loyalty\Filament\Resources\GuestLoyaltyProfileResource\Pages\EditGuestLoyaltyProfile::route('/{record}/edit'),
        ];
    }
}
