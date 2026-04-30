<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CooldownActionType;
use App\Enums\CooldownStatus;
use App\Filament\Resources\CooldownPeriodResource\Pages;
use App\Models\CooldownPeriod;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Cooldown Period Filament Resource
 * 
 * Admin interface for managing cooldown periods.
 * Allows viewing, filtering, and manually overriding cooldowns.
 */
final class CooldownPeriodResource extends Resource
{
    protected static ?string $model = CooldownPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cooldown Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\Select::make('action_type')
                            ->options(collect(CooldownActionType::cases())->pluck('getLabel', 'value'))
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\DateTimePicker::make('triggered_at')
                            ->disabled()
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->disabled()
                            ->seconds(false),

                        Forms\Components\Textarea::make('reason')
                            ->rows(3)
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\Select::make('status')
                            ->options(collect(CooldownStatus::cases())->pluck('getLabel', 'value'))
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Override Information')
                    ->schema([
                        Forms\Components\Select::make('overridden_by')
                            ->relationship('overriddenBy', 'email')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('overridden_at')
                            ->disabled()
                            ->seconds(false),

                        Forms\Components\Textarea::make('override_reason')
                            ->rows(3)
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record !== null && $record->overridden_by !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'tenant', 'overriddenBy']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('action_type')
                    ->label('Action Type')
                    ->formatStateUsing(fn ($state) => CooldownActionType::from($state)->getLabel())
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        CooldownActionType::HIGH_FRAUD_SCORE => 'danger',
                        CooldownActionType::CHANGE_BANK,
                        CooldownActionType::EMAIL_CHANGE,
                        CooldownActionType::PHONE_CHANGE => 'warning',
                        default => 'info',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => CooldownStatus::from($state)->getLabel())
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        CooldownStatus::ACTIVE->value => 'danger',
                        CooldownStatus::EXPIRED->value => 'success',
                        CooldownStatus::OVERRIDDEN->value => 'warning',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('triggered_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->description(fn ($record) => $record->isActive() ? $record->getRemainingTimeForHumans() : null),

                Tables\Columns\TextColumn::make('reason')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('overriddenBy.email')
                    ->label('Overridden By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action_type')
                    ->options(collect(CooldownActionType::cases())->pluck('getLabel', 'value')),

                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(CooldownStatus::cases())->pluck('getLabel', 'value'))
                    ->default(CooldownStatus::ACTIVE->value),

                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->active())
                    ->label('Active Only'),

                Tables\Filters\Filter::make('expired')
                    ->query(fn ($query) => $query->expired())
                    ->label('Expired'),

                Tables\Filters\Filter::make('overridden')
                    ->query(fn ($query) => $query->overridden())
                    ->label('Overridden'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('override')
                    ->label('Override')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Override Cooldown')
                    ->modalDescription('Are you sure you want to manually override this cooldown? This action will be logged.')
                    ->form([
                        Forms\Components\Textarea::make('override_reason')
                            ->label('Reason for override')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (CooldownPeriod $record, array $data) {
                        $record->override(
                            Auth::id(),
                            $data['override_reason']
                        );
                    })
                    ->visible(fn ($record) => $record->isActive()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('override')
                        ->requiresConfirmation()
                        ->modalHeading('Override Selected Cooldowns')
                        ->modalDescription('Are you sure you want to override these cooldowns?')
                        ->form([
                            Forms\Components\Textarea::make('override_reason')
                                ->label('Reason for override')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                if ($record->isActive()) {
                                    $record->override(Auth::id(), $data['override_reason']);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCooldownPeriods::route('/'),
            'view' => Pages\ViewCooldownPeriod::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return CooldownPeriod::active()->count() > 0
            ? (string) CooldownPeriod::active()->count()
            : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }
}
