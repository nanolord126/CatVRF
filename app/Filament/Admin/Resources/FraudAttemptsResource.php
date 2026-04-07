<?php declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Models\FraudAttempt;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

final class FraudAttemptsResource extends Resource
{
    protected static ?string $model = FraudAttempt::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Безопасность';
    protected static ?string $navigationLabel = 'Попытки фрода';
    protected static ?string $modelLabel = 'Попытка фрода';
    protected static ?string $pluralModelLabel = 'Попытки фрода';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Информация о событии')
                ->columns(2)
                ->schema([
                    Placeholder::make('operation_type')
                        ->label('Тип операции')
                        ->content(fn (FraudAttempt $record): string => $record->operation_type)
                        ->columnSpan(1),
                    Placeholder::make('ip_address')
                        ->label('IP-адрес')
                        ->content(fn (FraudAttempt $record): string => $record->ip_address)
                        ->columnSpan(1),
                    Placeholder::make('ml_score')
                        ->label('ML-оценка')
                        ->content(fn (FraudAttempt $record): string => number_format((float) $record->ml_score, 4))
                        ->columnSpan(1),
                    Placeholder::make('ml_version')
                        ->label('Версия модели')
                        ->content(fn (FraudAttempt $record): string => (string) ($record->ml_version ?? '—'))
                        ->columnSpan(1),
                    Placeholder::make('correlation_id')
                        ->label('Correlation ID')
                        ->content(fn (FraudAttempt $record): string => (string) ($record->correlation_id ?? '—'))
                        ->columnSpan(2),
                ]),
            Section::make('Решение')
                ->columns(2)
                ->schema([
                    Select::make('decision')
                        ->label('Решение')
                        ->options([
                            'allow'  => 'Разрешить',
                            'review' => 'На проверку',
                            'block'  => 'Заблокировать',
                        ])
                        ->required()
                        ->columnSpan(1),
                    TextInput::make('reason')
                        ->label('Причина')
                        ->maxLength(500)
                        ->columnSpan(1),
                ]),
            Section::make('Фичи ML-модели')
                ->collapsed()
                ->schema([
                    KeyValue::make('features')
                        ->label('Признаки')
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label('User ID')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('operation_type')
                    ->label('Тип операции')
                    ->badge()
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('ml_score')
                    ->label('ML Score')
                    ->sortable()
                    ->formatStateUsing(fn (?float $state): string => $state !== null ? number_format($state, 4) : '—')
                    ->color(fn (?float $state): string => match (true) {
                        $state >= 0.85 => 'danger',
                        $state >= 0.65 => 'warning',
                        default        => 'success',
                    }),
                TextColumn::make('decision')
                    ->label('Решение')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'review' => 'warning',
                        'allow'  => 'success',
                        default  => 'gray',
                    }),
                TextColumn::make('reason')
                    ->label('Причина')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('blocked_at')
                    ->label('Заблокирован')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('decision')
                    ->label('Решение')
                    ->options([
                        'allow'  => 'Разрешено',
                        'review' => 'На проверке',
                        'block'  => 'Заблокировано',
                    ]),
                SelectFilter::make('operation_type')
                    ->label('Тип операции')
                    ->options([
                        'payment_init'   => 'Инициация платежа',
                        'payout'         => 'Вывод средств',
                        'login'          => 'Вход',
                        'cart_add'       => 'Корзина',
                        'ai_constructor' => 'AI-конструктор',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('changeToBlock')
                    ->label('Заблокировать')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (FraudAttempt $record): bool => $record->decision !== 'block')
                    ->requiresConfirmation()
                    ->action(fn (FraudAttempt $record) => $record->update([
                        'decision'   => 'block',
                        'blocked_at' => now(),
                    ])),
                Action::make('changeToAllow')
                    ->label('Разрешить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (FraudAttempt $record): bool => $record->decision !== 'allow')
                    ->requiresConfirmation()
                    ->action(fn (FraudAttempt $record) => $record->update(['decision' => 'allow'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkBlock')
                        ->label('Заблокировать выбранные')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update([
                            'decision'   => 'block',
                            'blocked_at' => now(),
                        ])),
                    BulkAction::make('bulkAllow')
                        ->label('Разрешить выбранные')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['decision' => 'allow'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\FraudAttemptsResource\Pages\ListFraudAttempts::route('/'),
            'view'  => \App\Filament\Admin\Resources\FraudAttemptsResource\Pages\ViewFraudAttempt::route('/{record}'),
        ];
    }
}
