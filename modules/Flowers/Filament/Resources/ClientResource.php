<?php

declare(strict_types=1);

namespace Modules\Flowers\Filament\Resources;

use Modules\Flowers\Infrastructure\Models\ClientModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class ClientResource extends Resource
{
    protected static ?string $model = ClientModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Flowers CRM';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->required()
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('birth_date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Preferences & Notes')
                    ->schema([
                        Forms\Components\Textarea::make('preferences')
                            ->rows(3)
                            ->helperText('Favorite flowers, colors, styles, etc.'),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Loyalty Program')
                    ->schema([
                        Forms\Components\TextInput::make('total_orders')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('total_spent')
                            ->numeric()
                            ->prefix('₽')
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('loyalty_points')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\Select::make('loyalty_tier')
                            ->options([
                                'bronze' => 'Bronze',
                                'silver' => 'Silver',
                                'gold' => 'Gold',
                                'platinum' => 'Platinum',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_subscribed')
                            ->label('Subscribed to newsletters'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('birth_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('loyalty_tier')
                    ->colors([
                        'gray' => 'bronze',
                        'blue' => 'silver',
                        'yellow' => 'gold',
                        'purple' => 'platinum',
                    ]),
                TextColumn::make('total_orders')
                    ->sortable()
                    ->label('Orders'),
                TextColumn::make('total_spent')
                    ->money('RUB')
                    ->sortable()
                    ->label('Total Spent'),
                TextColumn::make('loyalty_points')
                    ->sortable()
                    ->label('Points'),
                BadgeColumn::make('is_subscribed')
                    ->boolean()
                    ->label('Subscribed'),
            ])
            ->filters([
                SelectFilter::make('loyalty_tier')
                    ->options([
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'platinum' => 'Platinum',
                    ]),
                Tables\Filters\Filter::make('vip')
                    ->query(fn (Builder $query): Builder => $query->whereIn('loyalty_tier', ['gold', 'platinum'])),
                Tables\Filters\Filter::make('is_subscribed')
                    ->query(fn (Builder $query): Builder => $query->where('is_subscribed', true)),
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
            'orders' => Tables\Relations\RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Flowers\Filament\Resources\ClientResource\Pages\ListClients::route('/'),
            'create' => \Modules\Flowers\Filament\Resources\ClientResource\Pages\CreateClient::route('/create'),
            'view' => \Modules\Flowers\Filament\Resources\ClientResource\Pages\ViewClient::route('/{record}'),
            'edit' => \Modules\Flowers\Filament\Resources\ClientResource\Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
