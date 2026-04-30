<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationReactionResource\Pages;
use App\Models\NotificationReaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class NotificationReactionResource extends Resource
{
    protected static ?string $model = NotificationReaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-thumb-up';

    protected static ?string $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('notification_log_id')
                    ->relationship('notificationLog', 'id')
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('reaction_type')
                    ->options([
                        'like' => 'Like',
                        'dislike' => 'Dislike',
                        'neutral' => 'Neutral',
                        'helpful' => 'Helpful',
                        'not_helpful' => 'Not Helpful',
                        'reported' => 'Reported',
                    ])
                    ->required(),

                Forms\Components\KeyValue::make('reaction_metadata')
                    ->label('Reaction Metadata')
                    ->keyLabel('Key')
                    ->valueLabel('Value')
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('reacted_at')
                    ->default(now())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('notificationLog.channel')
                    ->label('Channel')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('notificationLog.event_type')
                    ->label('Event Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('reaction_type')
                    ->colors([
                        'success' => 'like',
                        'success' => 'helpful',
                        'danger' => 'dislike',
                        'danger' => 'not_helpful',
                        'danger' => 'reported',
                        'gray' => 'neutral',
                    ])
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->sortable(),

                Tables\Columns\TextColumn::make('reacted_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reaction_type')
                    ->options([
                        'like' => 'Like',
                        'dislike' => 'Dislike',
                        'neutral' => 'Neutral',
                        'helpful' => 'Helpful',
                        'not_helpful' => 'Not Helpful',
                        'reported' => 'Reported',
                    ]),

                Tables\Filters\SelectFilter::make('channel')
                    ->relationship('notificationLog', 'channel'),

                Tables\Filters\Filter::make('reacted_at')
                    ->form([
                        Forms\Components\DatePicker::make('reacted_from'),
                        Forms\Components\DatePicker::make('reacted_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['reacted_from'],
                                fn ($query) => $query->whereDate('reacted_at', '>=', $data['reacted_from'])
                            )
                            ->when(
                                $data['reacted_until'],
                                fn ($query) => $query->whereDate('reacted_at', '<=', $data['reacted_until'])
                            );
                    }),
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
            ])
            ->defaultSort('reacted_at', 'desc');
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
            'index' => Pages\ListNotificationReactions::route('/'),
            'create' => Pages\CreateNotificationReaction::route('/create'),
            'view' => Pages\ViewNotificationReaction::route('/{record}'),
            'edit' => Pages\EditNotificationReaction::route('/{record}/edit'),
        ];
    }
}
