<?php declare(strict_types=1);

namespace App\Domains\Notifications\Filament\Resources;

use App\Domains\Notifications\Models\Notification;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Notifications';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'email' => 'blue',
                        'push' => 'green',
                        'sms' => 'orange',
                        'telegram' => 'cyan',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_read')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'email' => 'Email',
                        'push' => 'Push',
                        'sms' => 'SMS',
                        'telegram' => 'Telegram',
                    ]),
                Tables\Filters\Filter::make('unread')
                    ->query(fn ($query) => $query->whereNull('read_at')),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
    }
}
