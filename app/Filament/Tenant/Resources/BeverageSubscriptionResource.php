<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Food\Beverages\Models\BeverageSubscription;
use App\Filament\Tenant\Resources\BeverageSubscriptionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class BeverageSubscriptionResource extends Resource
{
    protected static ?string $model = BeverageSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Beverage Management';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Core')
                    ->description('Identify the subscriber and the recurring beverage plan.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->disabled()
                            ->label('Protocol UUID'),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'active' => 'Operational (Active)',
                                'paused' => 'Temporarily Suspend',
                                'expired' => 'Contract Elapsed',
                                'cancelled' => 'Terminated by User/System',
                            ])
                            ->native(false),
                    ]),

                Forms\Components\Section::make('Plan & Vertical Settings')
                    ->description('Details of the specialized beverage subscription.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('plan_type')
                            ->required()
                            ->options([
                                'daily_coffee' => 'Daily Barista Pick (1 cup/day)',
                                'office_water' => 'Hydration Protocol (50L/month)',
                                'wine_club' => 'Premium Vineyard Selection',
                                'detox_smoothie' => 'Nutritional Detox Program',
                            ])
                            ->native(false),
                        Forms\Components\Select::make('cadence')
                            ->required()
                            ->options([
                                'daily' => 'Iterative Daily Delivery',
                                'weekly' => 'Weekly Batch Cycle',
                                'monthly' => 'Monthly Bulk Fulfillment',
                            ])
                            ->native(false),
                        Forms\Components\TextInput::make('price_per_period')
                            ->numeric()
                            ->prefix('RUB')
                            ->required()
                            ->label('Billing Amount (Kopeks)'),
                        Forms\Components\TextInput::make('remaining_credits')
                            ->numeric()
                            ->required()
                            ->label('Unused Drink/Volume Credits'),
                    ]),

                Forms\Components\Section::make('Temporal Boundaries')
                    ->description('When the subscription is valid.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->required()
                            ->label('Activation Timestamp'),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Contract Termination Date'),
                        Forms\Components\DateTimePicker::make('last_billed_at')
                            ->label('Last Successful Billing Cycle')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Metadata & System Audit')
                    ->description('System tags and analytical JSON data.')
                    ->columns(1)
                    ->schema([
                        Forms\Components\KeyValue::make('config_json')
                            ->label('Plan Variables (Modifiers, Preferences)'),
                        Forms\Components\KeyValue::make('tags')
                            ->label('Analytical Segment Tags'),
                        Forms\Components\TextInput::make('correlation_id')
                            ->disabled()
                            ->label('Audit Correlation Identifier'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Subscriber')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('plan_type')
                    ->label('Vertical Plan')
                    ->colors([
                        'primary' => 'daily_coffee',
                        'info' => 'office_water',
                        'danger' => 'wine_club',
                        'success' => 'detox_smoothie',
                    ]),
                Tables\Columns\TextColumn::make('cadence')
                    ->label('Billing Frequency')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('price_per_period')
                    ->money('RUB', divideBy: 100)
                    ->label('Recurring Revenue')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'paused',
                        'danger' => ['expired', 'cancelled'],
                    ])
                    ->label('Life Cycle'),
                Tables\Columns\TextColumn::make('remaining_credits')
                    ->label('Credits Left')
                    ->color(fn ($state) => (int)$state < 5 ? 'danger' : 'success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('starts_at')
                    ->date('d M Y')
                    ->label('Effective From'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->date('d M Y')
                    ->label('Valid Until')
                    ->placeholder('Open Contract'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Operational Only',
                        'paused' => 'Paused Accounts',
                    ]),
                Tables\Filters\SelectFilter::make('plan_type')
                    ->options([
                        'daily_coffee' => 'Coffee Enthusiasts',
                        'office_water' => 'Hydration Protocols',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('renew')
                    ->label('Force Billing')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(fn (BeverageSubscription $record) => $record->update(['last_billed_at' => now(), 'remaining_credits' => $record->remaining_credits + 30])),
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
            'index' => Pages\ListBeverageSubscriptions::route('/'),
            'create' => Pages\CreateBeverageSubscription::route('/create'),
            'edit' => Pages\EditBeverageSubscription::route('/{record}/edit'),
        ];
    }
}
