<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StreamResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListStream::route('/'),
                'create' => Pages\\CreateStream::route('/create'),
                'edit' => Pages\\EditStream::route('/{record}/edit'),
                'view' => Pages\\ViewStream::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListStream::route('/'),
                'create' => Pages\\CreateStream::route('/create'),
                'edit' => Pages\\EditStream::route('/{record}/edit'),
                'view' => Pages\\ViewStream::route('/{record}'),
            ];
        }
}
