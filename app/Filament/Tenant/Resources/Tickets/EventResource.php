<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Tickets;

use App\Domains\Tickets\Models\Event;
use App\Filament\Tenant\Resources\Tickets\EventResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'События';

    protected static ?string $navigationGroup = 'Tickets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название события')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('URL-slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(4),
                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->options([
                                'music' => 'Музыка',
                                'theater' => 'Театр',
                                'cinema' => 'Кино',
                                'sports' => 'Спорт',
                                'conference' => 'Конференция',
                                'festival' => 'Фестиваль',
                                'workshop' => 'Мастер-класс',
                            ])
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Место и время')
                    ->schema([
                        Forms\Components\TextInput::make('location')
                            ->label('Место')
                            ->required(),
                        Forms\Components\TextInput::make('address')
                            ->label('Полный адрес')
                            ->required(),
                        Forms\Components\DateTimePickerInput::make('start_datetime')
                            ->label('Дата/время начала')
                            ->required(),
                        Forms\Components\DateTimePickerInput::make('end_datetime')
                            ->label('Дата/время окончания')
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Организатор')
                    ->schema([
                        Forms\Components\TextInput::make('organizer_name')
                            ->label('Организатор')
                            ->required(),
                        Forms\Components\TextInput::make('organizer_phone')
                            ->label('Телефон'),
                        Forms\Components\TextInput::make('organizer_email')
                            ->label('Email')
                            ->email(),
                    ])->columns(3),
                Forms\Components\Section::make('Билеты')
                    ->schema([
                        Forms\Components\TextInput::make('total_capacity')
                            ->label('Вместимость')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('min_ticket_price')
                            ->label('Минимальная цена (копейки)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Toggle::make('require_age_check')
                            ->label('Требуется проверка возраста 18+'),
                    ])->columns(3),
                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'published' => 'Опубликовано',
                                'cancelled' => 'Отменено',
                                'completed' => 'Завершено',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->badge(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Место')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Начало')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_capacity')
                    ->label('Вместимость'),
                Tables\Columns\TextColumn::make('sold_count')
                    ->label('Продано'),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->numeric(decimalPlaces: 1),
                Tables\Columns\BadgeColumn::make('is_live')
                    ->label('Live')
                    ->formatStateUsing(fn ($state) => $state ? '🔴 Live' : '⚪ Offline')
                    ->color(fn ($state) => $state ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'green',
                        'cancelled' => 'red',
                        'completed' => 'blue',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->options([
                        'music' => 'Музыка',
                        'theater' => 'Театр',
                        'cinema' => 'Кино',
                        'sports' => 'Спорт',
                        'conference' => 'Конференция',
                        'festival' => 'Фестиваль',
                        'workshop' => 'Мастер-класс',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'published' => 'Опубликовано',
                        'cancelled' => 'Отменено',
                        'completed' => 'Завершено',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('stream')
                    ->label('🎬 Трансляция')
                    ->icon('heroicon-m-play')
                    ->url(fn ($record) => route('stream.show', ['stream' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->status === 'published'),
                Tables\Actions\Action::make('toggle-live')
                    ->label('Live: Off')
                    ->icon('heroicon-m-video-camera')
                    ->action(function ($record) {
                        $record->update(['is_live' => !$record->is_live]);
                    })
                    ->color(fn ($record) => $record->is_live ? 'danger' : 'gray')
                    ->successNotificationTitle('Live статус обновлён'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
