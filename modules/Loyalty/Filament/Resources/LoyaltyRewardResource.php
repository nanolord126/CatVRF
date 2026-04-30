<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Infrastructure\Models\LoyaltyRewardModel;

final class LoyaltyRewardResource extends Resource
{
    protected static ?string $model = LoyaltyRewardModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reward Information')
                    ->schema([
                        Forms\Components\Select::make('loyalty_program_id')
                            ->relationship('program', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(2),
                        Forms\Components\Select::make('type')
                            ->options([
                                'discount' => 'Discount',
                                'free_item' => 'Free Item',
                                'upgrade' => 'Upgrade',
                                'service' => 'Service',
                                'cashback' => 'Cashback',
                                'voucher' => 'Voucher',
                                'privilege' => 'Privilege',
                                'custom' => 'Custom',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('image_url')
                            ->url()
                            ->placeholder('https://example.com/image.jpg'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Points Cost')
                    ->schema([
                        Forms\Components\TextInput::make('points_cost')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->helperText('Points required to redeem this reward'),
                    ]),

                Forms\Components\Section::make('Reward Value')
                    ->schema([
                        Forms\Components\Select::make('value_type')
                            ->options([
                                'fixed' => 'Fixed Value (e.g., 500 rubles)',
                                'percentage' => 'Percentage (e.g., 10% off)',
                                'item' => 'Specific Item/Service',
                            ])
                            ->required()
                            ->default('fixed')
                            ->reactive(),
                        Forms\Components\TextInput::make('value_amount')
                            ->numeric()
                            ->step(0.01)
                            ->nullable()
                            ->helperText('Fixed amount or percentage value')
                            ->visible(fn (callable $get) => in_array($get('value_type'), ['fixed', 'percentage'])),
                        Forms\Components\TextInput::make('menu_item_id')
                            ->numeric()
                            ->nullable()
                            ->helperText('For free_item type')
                            ->visible(fn (callable $get) => $get('value_type') === 'item'),
                        Forms\Components\TextInput::make('item_code')
                            ->nullable()
                            ->helperText('For item-based rewards')
                            ->visible(fn (callable $get) => $get('value_type') === 'item'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Conditions')
                    ->schema([
                        Forms\Components\KeyValue::make('conditions')
                            ->keyLabel('Condition')
                            ->valueLabel('Value')
                            ->helperText('Add conditions in key-value format. Examples: min_order: 1000, day_of_week: friday'),
                    ]),

                Forms\Components\Section::make('Availability')
                    ->schema([
                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->nullable()
                            ->helperText('Limited quantity rewards'),
                        Forms\Components\TextInput::make('max_redemptions_per_guest')
                            ->numeric()
                            ->nullable()
                            ->helperText('Limit per guest'),
                        Forms\Components\TextInput::make('max_redemptions_total')
                            ->numeric()
                            ->nullable()
                            ->helperText('Total limit'),
                        Forms\Components\TextInput::make('redeemed_count')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Targeting')
                    ->schema([
                        Forms\Components\TagsInput::make('target_tiers')
                            ->placeholder('Add tier slugs')
                            ->helperText('e.g., bronze, silver, gold'),
                        Forms\Components\TagsInput::make('target_segments')
                            ->placeholder('Add guest segments')
                            ->helperText('e.g., vip, new, regular'),
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
                Tables\Columns\TextColumn::make('program.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image_url')
                    ->circular()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'discount' => 'success',
                        'free_item' => 'primary',
                        'upgrade' => 'info',
                        'service' => 'warning',
                        'cashback' => 'danger',
                        'voucher' => 'purple',
                        'privilege' => 'amber',
                        'custom' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('points_cost')
                    ->numeric()
                    ->sortable()
                    ->description(fn (LoyaltyRewardModel $record): string => $record->value_type === 'percentage' 
                        ? ($record->value_amount * 100) . '% off' 
                        : ($record->value_amount . ' ₽')),
                Tables\Columns\TextColumn::make('redeemed_count')
                    ->numeric()
                    ->sortable()
                    ->description(fn (LoyaltyRewardModel $record): string => $record->stock_quantity 
                        ? "of {$record->stock_quantity}" 
                        : 'unlimited'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('loyalty_program_id')
                    ->relationship('program', 'name'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'discount' => 'Discount',
                        'free_item' => 'Free Item',
                        'upgrade' => 'Upgrade',
                        'service' => 'Service',
                        'cashback' => 'Cashback',
                        'voucher' => 'Voucher',
                        'privilege' => 'Privilege',
                        'custom' => 'Custom',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
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

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Loyalty\Filament\Resources\LoyaltyRewardResource\Pages\ListLoyaltyRewards::route('/'),
            'create' => \Modules\Loyalty\Filament\Resources\LoyaltyRewardResource\Pages\CreateLoyaltyReward::route('/create'),
            'edit' => \Modules\Loyalty\Filament\Resources\LoyaltyRewardResource\Pages\EditLoyaltyReward::route('/{record}/edit'),
        ];
    }
}
