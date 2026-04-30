<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Loyalty\Domain\Enums\VerticalType;
use Modules\Loyalty\Infrastructure\Models\LoyaltyProgramModel;

final class LoyaltyProgramResource extends Resource
{
    protected static ?string $model = LoyaltyProgramModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Program Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\Select::make('vertical_type')
                            ->options([
                                'restaurant' => 'Restaurant',
                                'hotel' => 'Hotel',
                                'beauty' => 'Beauty',
                                'auto' => 'Auto',
                                'fashion' => 'Fashion',
                                'jewelry' => 'Jewelry',
                                'bakery' => 'Bakery',
                            ])
                            ->required()
                            ->default('restaurant'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Point Calculation Settings')
                    ->schema([
                        Forms\Components\TextInput::make('base_points_per_currency')
                            ->numeric()
                            ->step(0.0001)
                            ->default(1.0)
                            ->helperText('Points earned per currency unit (e.g., 1 point per 1 ruble)')
                            ->required(),
                        Forms\Components\TextInput::make('points_to_currency_rate')
                            ->numeric()
                            ->step(0.0001)
                            ->default(0.01)
                            ->helperText('Currency value per point (e.g., 1 point = 0.01 ruble)')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Bonus Settings')
                    ->schema([
                        Forms\Components\TextInput::make('signup_bonus_points')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Bonus points for registration')
                            ->required(),
                        Forms\Components\TextInput::make('birthday_bonus_points')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Bonus points on birthday')
                            ->required(),
                        Forms\Components\TextInput::make('referral_bonus_points')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Bonus points for referrals')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Tier System')
                    ->schema([
                        Forms\Components\Toggle::make('tier_system_enabled')
                            ->default(true)
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('tier_config', null)),
                        Forms\Components\KeyValue::make('tier_config')
                            ->keyLabel('Tier Name')
                            ->valueLabel('Configuration (JSON)')
                            ->helperText('Configure tier settings in JSON format')
                            ->visible(fn (callable $get) => $get('tier_system_enabled')),
                    ]),

                Forms\Components\Section::make('Point Expiration')
                    ->schema([
                        Forms\Components\Toggle::make('points_expire')
                            ->default(false)
                            ->reactive(),
                        Forms\Components\TextInput::make('points_expiration_days')
                            ->numeric()
                            ->default(null)
                            ->helperText('Days until points expire')
                            ->visible(fn (callable $get) => $get('points_expire'))
                            ->required(fn (callable $get) => $get('points_expire')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at'),
                        Forms\Components\DateTimePicker::make('ends_at'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\Textarea::make('metadata')
                            ->rows(3)
                            ->helperText('Additional metadata in JSON format'),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vertical_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'restaurant' => 'warning',
                        'hotel' => 'info',
                        'beauty' => 'pink',
                        'auto' => 'gray',
                        'fashion' => 'purple',
                        'jewelry' => 'amber',
                        'bakery' => 'orange',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('base_points_per_currency')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('signup_bonus_points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tiers_count')
                    ->counts('tiers')
                    ->label('Tiers'),
                Tables\Columns\TextColumn::make('profiles_count')
                    ->counts('profiles')
                    ->label('Members'),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vertical_type')
                    ->options([
                        'restaurant' => 'Restaurant',
                        'hotel' => 'Hotel',
                        'beauty' => 'Beauty',
                        'auto' => 'Auto',
                        'fashion' => 'Fashion',
                        'jewelry' => 'Jewelry',
                        'bakery' => 'Bakery',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\Filter::make('active_now')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('is_active', true)
                        ->where(function ($q) {
                            $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                        })
                        ->where(function ($q) {
                            $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                        })
                    )->label('Currently Active'),
            ])
            ->actions([
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
            'tiers' => Tables\Columns\TextColumn::make('tiers'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Loyalty\Filament\Resources\LoyaltyProgramResource\Pages\ListLoyaltyPrograms::route('/'),
            'create' => \Modules\Loyalty\Filament\Resources\LoyaltyProgramResource\Pages\CreateLoyaltyProgram::route('/create'),
            'edit' => \Modules\Loyalty\Filament\Resources\LoyaltyProgramResource\Pages\EditLoyaltyProgram::route('/{record}/edit'),
        ];
    }
}
