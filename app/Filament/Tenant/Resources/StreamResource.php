<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Content\Bloggers\Models\Stream;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Infolists\Infolist;

final class StreamResource extends Resource
{
    protected static ?string $model = Stream::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'Трансляции';
    protected static ?string $pluralModelLabel = 'Трансляции';
    protected static ?string $modelLabel = 'Трансляция';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('title')
                            ->label('Название трансляции')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan('full'),

                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpan('full'),

                        TextInput::make('room_id')
                            ->label('ID комнаты')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('broadcast_key')
                            ->label('Ключ трансляции')
                            ->disabled()
                            ->columnSpan(1),

                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'scheduled' => 'Запланирован',
                                'live' => 'В прямом эфире',
                                'ended' => 'Завершен',
                                'cancelled' => 'Отменен',
                            ])
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('category')
                            ->label('Категория')
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Расписание')
                    ->schema([
                        TextInput::make('scheduled_at')
                            ->label('Запланировано на')
                            ->type('datetime-local')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('started_at')
                            ->label('Начало')
                            ->type('datetime-local')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('ended_at')
                            ->label('Конец')
                            ->type('datetime-local')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('duration_minutes')
                            ->label('Длительность (минуты)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Статистика и зрители')
                    ->schema([
                        TextInput::make('current_viewers')
                            ->label('Текущих зрителей')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('peak_viewers')
                            ->label('Пик зрителей')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('total_viewers')
                            ->label('Всего просмотров')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('unique_viewers')
                            ->label('Уникальных зрителей')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('chat_messages_count')
                            ->label('Сообщений в чате')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('average_engagement_rate')
                            ->label('Средн. вовлечённость (%)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(3),

                Section::make('Финансы')
                    ->schema([
                        TextInput::make('total_revenue')
                            ->label('Общий доход (копейки)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('platform_commission')
                            ->label('Комиссия платформы (копейки)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('orders_count')
                            ->label('Заказов')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('total_gifts')
                            ->label('Подарков получено')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Модерация')
                    ->schema([
                        Select::make('moderation_status')
                            ->label('Статус модерации')
                            ->options([
                                'approved' => 'Одобрена',
                                'flagged' => 'Отмечена',
                                'suspended' => 'Приостановлена',
                                'banned' => 'Заблокирована',
                            ])
                            ->columnSpan(1),

                        Textarea::make('moderation_notes')
                            ->label('Заметки модератора')
                            ->rows(2)
                            ->columnSpan('full'),

                        Select::make('is_vod_enabled')
                            ->label('VOD доступна')
                            ->boolean()
                            ->columnSpan(1),

                        TextInput::make('vod_url')
                            ->label('URL видеозаписи')
                            ->url()
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('blogger.display_name')
                    ->label('Блогер')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => 'scheduled',
                        'success' => 'live',
                        'info' => 'ended',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),

                TextColumn::make('current_viewers')
                    ->label('Зрители (текущ)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('peak_viewers')
                    ->label('Пик зрителей')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_revenue')
                    ->label('Доход')
                    ->formatStateUsing(fn (int $state) => '₽' . ($state / 100))
                    ->sortable(),

                BadgeColumn::make('moderation_status')
                    ->label('Модерация')
                    ->colors([
                        'success' => 'approved',
                        'warning' => 'flagged',
                        'danger' => 'suspended',
                        'gray' => 'banned',
                    ])
                    ->sortable(),

                TextColumn::make('scheduled_at')
                    ->label('Запланировано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'scheduled' => 'Запланирован',
                        'live' => 'В прямом эфире',
                        'ended' => 'Завершен',
                        'cancelled' => 'Отменен',
                    ]),

                Tables\Filters\SelectFilter::make('moderation_status')
                    ->label('Модерация')
                    ->options([
                        'approved' => 'Одобрена',
                        'flagged' => 'Отмечена',
                        'suspended' => 'Приостановлена',
                        'banned' => 'Заблокирована',
                    ]),

                Tables\Filters\Filter::make('high_revenue')
                    ->label('Высокий доход (>50k)')
                    ->query(fn ($query) => $query->where('total_revenue', '>', 5000000)),

                Tables\Filters\Filter::make('high_viewers')
                    ->label('Много зрителей (>1000)')
                    ->query(fn ($query) => $query->where('peak_viewers', '>', 1000)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('flag')
                    ->label('Отметить')
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('moderation_notes')
                            ->label('Причина')
                            ->required(),
                    ])
                    ->action(function (Stream $record, array $data) {
                        $record->update([
                            'moderation_status' => 'flagged',
                            'moderation_notes' => $data['moderation_notes'],
                        ]);
                    })
                    ->visible(fn (Stream $record) => $record->moderation_status === 'approved'),
                Tables\Actions\Action::make('suspend')
                    ->label('Приостановить')
                    ->icon('heroicon-o-pause')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Stream $record) {
                        $record->update(['moderation_status' => 'suspended']);
                    }),
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
            'index' => \App\Filament\Tenant\Resources\StreamResource\Pages\ListStreams::route('/'),
            'create' => \App\Filament\Tenant\Resources\StreamResource\Pages\CreateStream::route('/create'),
            'view' => \App\Filament\Tenant\Resources\StreamResource\Pages\ViewStream::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\StreamResource\Pages\EditStream::route('/{record}/edit'),
        ];
    }
}
