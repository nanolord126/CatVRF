<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Domain\Enums\LoyaltyRuleType;
use Modules\Loyalty\Infrastructure\Models\LoyaltyRuleModel;

final class LoyaltyRuleResource extends Resource
{
    protected static ?string $model = LoyaltyRuleModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-lightning-bolt';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rule Information')
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
                                'order_based' => 'Order Based',
                                'visit_based' => 'Visit Based',
                                'item_based' => 'Item Based',
                                'time_based' => 'Time Based',
                                'first_visit' => 'First Visit',
                                'birthday' => 'Birthday',
                                'referral' => 'Referral',
                                'milestone' => 'Milestone',
                                'social' => 'Social',
                                'review' => 'Review',
                                'custom' => 'Custom',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->helperText('Higher priority rules are checked first'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Conditions')
                    ->schema([
                        Forms\Components\KeyValue::make('conditions')
                            ->keyLabel('Condition')
                            ->valueLabel('Value')
                            ->helperText('Add conditions in key-value format. Examples: min_amount: 1000, day_of_week: friday, item_ids: [1,2,3]'),
                    ]),

                Forms\Components\Section::make('Point Calculation')
                    ->schema([
                        Forms\Components\Select::make('calculation_type')
                            ->options([
                                'fixed' => 'Fixed Points',
                                'percentage' => 'Percentage of Order',
                                'multiplier' => 'Multiplier of Base Points',
                                'tiered' => 'Tiered Calculation',
                            ])
                            ->required()
                            ->default('percentage')
                            ->reactive(),
                        Forms\Components\TextInput::make('points_value')
                            ->numeric()
                            ->step(0.01)
                            ->nullable()
                            ->helperText('Fixed points or percentage value')
                            ->visible(fn (callable $get) => in_array($get('calculation_type'), ['fixed', 'percentage'])),
                        Forms\Components\TextInput::make('point_multiplier')
                            ->numeric()
                            ->step(0.01)
                            ->default(1.0)
                            ->helperText('Multiplier value')
                            ->visible(fn (callable $get) => in_array($get('calculation_type'), ['multiplier', 'tiered']))
                            ->required(fn (callable $get) => in_array($get('calculation_type'), ['multiplier', 'tiered'])),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Usage Limits')
                    ->schema([
                        Forms\Components\TextInput::make('max_uses_per_guest')
                            ->numeric()
                            ->nullable()
                            ->helperText('Maximum uses per guest'),
                        Forms\Components\TextInput::make('max_uses_total')
                            ->numeric()
                            ->nullable()
                            ->helperText('Maximum total uses'),
                        Forms\Components\TextInput::make('current_uses')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])
                    ->columns(3),

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
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'order_based' => 'primary',
                        'visit_based' => 'success',
                        'item_based' => 'info',
                        'time_based' => 'warning',
                        'first_visit' => 'danger',
                        'birthday' => 'pink',
                        'referral' => 'purple',
                        'milestone' => 'amber',
                        'social' => 'blue',
                        'review' => 'cyan',
                        'custom' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('calculation_type')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('points_value')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('point_multiplier')
                    ->numeric()
                    ->formatStateUsing(fn (string $state): string => $state . 'x'),
                Tables\Columns\TextColumn::make('current_uses')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_uses_total')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('loyalty_program_id')
                    ->relationship('program', 'name'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'order_based' => 'Order Based',
                        'visit_based' => 'Visit Based',
                        'item_based' => 'Item Based',
                        'time_based' => 'Time Based',
                        'first_visit' => 'First Visit',
                        'birthday' => 'Birthday',
                        'referral' => 'Referral',
                        'milestone' => 'Milestone',
                        'social' => 'Social',
                        'review' => 'Review',
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
            'index' => \Modules\Loyalty\Filament\Resources\LoyaltyRuleResource\Pages\ListLoyaltyRules::route('/'),
            'create' => \Modules\Loyalty\Filament\Resources\LoyaltyRuleResource\Pages\CreateLoyaltyRule::route('/create'),
            'edit' => \Modules\Loyalty\Filament\Resources\LoyaltyRuleResource\Pages\EditLoyaltyRule::route('/{record}/edit'),
        ];
    }
}
