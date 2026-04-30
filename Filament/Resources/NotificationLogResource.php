<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationLogResource\Pages;
use App\Models\NotificationLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Notification Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('channel')
                            ->options([
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
                            ->required(),
                        Forms\Components\Select::make('event_type')
                            ->options([
                                'created' => 'Created',
                                'confirmed' => 'Confirmed',
                                'ready_for_delivery' => 'Ready for Delivery',
                                'in_delivery' => 'In Delivery',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('entity_type')
                            ->default('order'),
                        Forms\Components\TextInput::make('entity_id')
                            ->numeric(),
                        Forms\Components\TextInput::make('recipient')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'sent' => 'Sent',
                                'delivered' => 'Delivered',
                                'failed' => 'Failed',
                                'bounced' => 'Bounced',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Textarea::make('message_content')
                            ->rows(3),
                        Forms\Components\Textarea::make('error_message')
                            ->rows(2),
                        Forms\Components\KeyValue::make('metadata'),
                        Forms\Components\DateTimePicker::make('sent_at'),
                        Forms\Components\DateTimePicker::make('delivered_at'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('channel')
                    ->colors([
                        'primary' => 'telegram',
                        'success' => 'whatsapp',
                        'info' => 'viber',
                        'warning' => 'email',
                        'danger' => 'sms',
                    ]),
                Tables\Columns\TextColumn::make('event_type')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'delivered',
                        'primary' => 'sent',
                        'danger' => 'failed',
                        'warning' => 'bounced',
                    ]),
                Tables\Columns\TextColumn::make('recipient')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
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
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                        'bounced' => 'Bounced',
                    ]),
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        'created' => 'Created',
                        'confirmed' => 'Confirmed',
                        'ready_for_delivery' => 'Ready for Delivery',
                        'in_delivery' => 'In Delivery',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            'user',
            'tenant',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationLogs::route('/'),
            'create' => Pages\CreateNotificationLog::route('/create'),
            'view' => Pages\ViewNotificationLog::route('/{record}'),
            'edit' => Pages\EditNotificationLog::route('/{record}/edit'),
        ];
    }
}
