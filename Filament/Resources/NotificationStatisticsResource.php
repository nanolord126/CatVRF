<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domains\Shared\Notifications\Services\NotificationLogService;
use App\Filament\Resources\NotificationStatisticsResource\Widgets;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

final class NotificationStatisticsResource extends Resource
{
    protected static ?string $model = null;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_notification_statistics') ?? true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Statistics Filters')
                    ->description('Filter notification statistics by date range and channel')
                    ->schema([
                        Forms\Components\Select::make('channel')
                            ->label('Channel')
                            ->options([
                                'all' => 'All Channels',
                                'telegram' => 'Telegram',
                                'whatsapp' => 'WhatsApp',
                                'viber' => 'Viber',
                                'kakaotalk' => 'KakaoTalk',
                                'signal' => 'Signal',
                                'wechat' => 'WeChat',
                                'vk' => 'VK',
                                'odnoklassniki' => 'Odnoklassniki',
                                'email' => 'Email',
                                'push' => 'Push',
                                'sms' => 'SMS',
                            ])
                            ->default('all')
                            ->required(),
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date')
                            ->default(now()->subDays(30))
                            ->required(),
                        Forms\Components\DatePicker::make('to')
                            ->label('To Date')
                            ->default(now())
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(\App\Models\NotificationLog::query())
            ->columns([
                Tables\Columns\TextColumn::make('channel')
                    ->label('Channel')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'delivered',
                        'primary' => 'sent',
                        'danger' => 'failed',
                        'warning' => 'bounced',
                    ]),
                Tables\Columns\TextColumn::make('count')
                    ->label('Count')
                    ->sortable(),
            ])
            ->defaultSort('count', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ViewNotificationStatistics::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\NotificationOverviewWidget::class,
            Widgets\ChannelPerformanceWidget::class,
            Widgets\DeliveryTrendWidget::class,
            Widgets\EventTypeDistributionWidget::class,
        ];
    }
}
