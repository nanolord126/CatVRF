<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources;

use App\Domains\Payment\Filament\Resources\RecurringSubscriptionResource\Pages;
use App\Domains\Payment\Models\RecurringSubscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecurringSubscriptionResource extends Resource
{
    protected static ?string $model = RecurringSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Information')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'incomplete' => 'Incomplete',
                                'trialing' => 'Trialing',
                                'active' => 'Active',
                                'past_due' => 'Past Due',
                                'canceled' => 'Canceled',
                                'unpaid' => 'Unpaid',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount_kopecks')
                            ->label('Amount (kopecks)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('interval')
                            ->options([
                                'day' => 'Daily',
                                'week' => 'Weekly',
                                'month' => 'Monthly',
                                'year' => 'Yearly',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('interval_count')
                            ->label('Interval Count')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Forms\Components\Select::make('provider')
                            ->options([
                                'tinkoff' => 'Tinkoff',
                                'tochka' => 'Tochka',
                                'sber' => 'Sber',
                            ]),
                        Forms\Components\TextInput::make('provider_subscription_id')
                            ->label('Provider Subscription ID')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('cancel_at_period_end')
                            ->label('Cancel at Period End'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Billing Cycle')
                    ->schema([
                        Forms\Components\DateTimePicker::make('current_period_start')
                            ->label('Current Period Start')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('current_period_end')
                            ->label('Current Period End')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('trial_start')
                            ->label('Trial Start')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('trial_end')
                            ->label('Trial End')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('next_payment_at')
                            ->label('Next Payment At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('last_payment_at')
                            ->label('Last Payment At')
                            ->disabled(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('failed_payment_count')
                            ->label('Failed Payment Count')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('max_retries')
                            ->label('Max Retries')
                            ->numeric(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'canceled' => 'danger',
                        'unpaid' => 'danger',
                        'incomplete' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_kopecks')
                    ->label('Amount (kopecks)')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('interval')
                    ->label('Interval')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_payment_at')
                    ->label('Next Payment')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_payment_at')
                    ->label('Last Payment')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('failed_payment_count')
                    ->label('Failed Count')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'incomplete' => 'Incomplete',
                        'trialing' => 'Trialing',
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'canceled' => 'Canceled',
                        'unpaid' => 'Unpaid',
                    ]),
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'tinkoff' => 'Tinkoff',
                        'tochka' => 'Tochka',
                        'sber' => 'Sber',
                    ]),
                Tables\Filters\Filter::make('due')
                    ->label('Due for Payment')
                    ->query(fn ($query) => $query->where('next_payment_at', '<=', now())),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringSubscriptions::route('/'),
            'create' => Pages\CreateRecurringSubscription::route('/create'),
            'view' => Pages\ViewRecurringSubscription::route('/{record}'),
            'edit' => Pages\EditRecurringSubscription::route('/{record}/edit'),
        ];
    }
}
