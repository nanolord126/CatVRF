<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate;

use App\Filament\Tenant\Resources\RealEstate\PropertyViewingResource\Pages;
use App\Domains\RealEstate\Models\PropertyViewing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class PropertyViewingResource extends Resource
{
    protected static ?string $model = PropertyViewing::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Real Estate';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('property_id')
                            ->relationship('property', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('agent_id')
                            ->relationship('agent', 'full_name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->required()
                            ->minDate(now())
                            ->rules(['after:now']),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Ожидает',
                                'held' => 'Забронирован',
                                'confirmed' => 'Подтверждён',
                                'completed' => 'Завершён',
                                'cancelled' => 'Отменён',
                                'no_show' => 'Неявка',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_b2b')
                            ->label('B2B')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Детали бронирования')
                    ->schema([
                        Forms\Components\DateTimePicker::make('held_at')
                            ->label('Время брони'),
                        Forms\Components\DateTimePicker::make('hold_expires_at')
                            ->label('Истекает в'),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Завершён в'),
                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Отменён в'),
                        Forms\Components\TextInput::make('webrtc_room_id')
                            ->label('WebRTC Room ID'),
                        Forms\Components\Toggle::make('faceid_verified')
                            ->label('FaceID подтверждён'),
                    ])->columns(2),

                Forms\Components\Section::make('Метаданные')
                    ->schema([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Причина отмены')
                            ->rows(2),
                        Forms\Components\KeyValue::make('metadata')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->editable(),
                        Forms\Components\TagsInput::make('tags'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('property.title')
                    ->label('Объект')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent.full_name')
                    ->label('Агент')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\DateTimeColumn::make('scheduled_at')
                    ->label('Запланирован')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'held',
                        'success' => 'confirmed',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                        'danger' => 'no_show',
                    ]),
                Tables\Columns\IconColumn::make('is_b2b')
                    ->label('B2B')
                    ->boolean()
                    ->trueIcon('heroicon-o-building-office')
                    ->falseIcon('heroicon-o-user'),
                Tables\Columns\ToggleColumn::make('faceid_verified')
                    ->label('FaceID'),
                Tables\Columns\TextColumn::make('held_at')
                    ->label('Забронирован')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('hold_expires_at')
                    ->label('Истекает')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ожидает',
                        'held' => 'Забронирован',
                        'confirmed' => 'Подтверждён',
                        'completed' => 'Завершён',
                        'cancelled' => 'Отменён',
                        'no_show' => 'Неявка',
                    ]),
                Tables\Filters\TernaryFilter::make('is_b2b')
                    ->label('B2B')
                    ->placeholder('Все')
                    ->trueLabel('B2B')
                    ->falseLabel('B2C'),
                Tables\Filters\Filter::make('expired')
                    ->label('Просроченные')
                    ->query(fn (Builder $query): Builder => $query->expired()),
                Tables\Filters\Filter::make('upcoming')
                    ->label('Предстоящие')
                    ->query(fn (Builder $query): Builder => $query->where('scheduled_at', '>', now())),
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
            ->defaultSort('scheduled_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            'property',
            'user',
            'agent',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPropertyViewings::route('/'),
            'create' => Pages\CreatePropertyViewing::route('/create'),
            'view' => Pages\ViewPropertyViewing::route('/{record}'),
            'edit' => Pages\EditPropertyViewing::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
