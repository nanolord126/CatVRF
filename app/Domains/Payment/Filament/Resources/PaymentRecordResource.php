<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources;

use App\Domains\Payment\Enums\PaymentProvider;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\PaymentRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Filament-ресурс для управления платёжными записями.
 *
 * Доступен в Admin Panel и Tenant Panel.
 */
final class PaymentRecordResource extends Resource
{
    protected static ?string $model = PaymentRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Финансы';

    protected static ?string $navigationLabel = 'Платежи';

    protected static ?string $modelLabel = 'Платёж';

    protected static ?string $pluralModelLabel = 'Платежи';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основные данные')->schema([
                Forms\Components\TextInput::make('idempotency_key')
                    ->label('Ключ идемпотентности')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('provider_code')
                    ->label('Провайдер')
                    ->options(
                        collect(PaymentProvider::cases())
                            ->mapWithKeys(fn (PaymentProvider $p) => [$p->value => $p->label()])
                            ->toArray()
                    )
                    ->required(),

                Forms\Components\TextInput::make('amount_kopecks')
                    ->label('Сумма (копейки)')
                    ->numeric()
                    ->required()
                    ->minValue(1),

                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options(
                        collect(PaymentStatus::cases())
                            ->mapWithKeys(fn (PaymentStatus $s) => [$s->value => $s->label()])
                            ->toArray()
                    )
                    ->disabled(),

                Forms\Components\Toggle::make('is_hold')
                    ->label('Холдирование'),

                Forms\Components\Textarea::make('correlation_id')
                    ->label('Correlation ID')
                    ->disabled(),
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
                    ->limit(12)
                    ->searchable(),

                Tables\Columns\TextColumn::make('provider_code')
                    ->label('Провайдер')
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentProvider ? $state->label() : $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_kopecks')
                    ->label('Сумма ₽')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' '))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')->badge()
                    ->label('Статус')
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentStatus ? $state->label() : $state)
                    ->colors([
                        'warning' => fn ($state) => $state === PaymentStatus::PENDING || $state === 'pending',
                        'info' => fn ($state) => $state === PaymentStatus::AUTHORIZED || $state === 'authorized',
                        'success' => fn ($state) => $state === PaymentStatus::CAPTURED || $state === 'captured',
                        'danger' => fn ($state) => $state === PaymentStatus::FAILED || $state === 'failed',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(
                        collect(PaymentStatus::cases())
                            ->mapWithKeys(fn (PaymentStatus $s) => [$s->value => $s->label()])
                            ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('provider_code')
                    ->options(
                        collect(PaymentProvider::cases())
                            ->mapWithKeys(fn (PaymentProvider $p) => [$p->value => $p->label()])
                            ->toArray()
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Payment\Filament\Resources\PaymentRecordResource\Pages\ListPaymentRecords::route('/'),
            'create' => \App\Domains\Payment\Filament\Resources\PaymentRecordResource\Pages\CreatePaymentRecord::route('/create'),
            'edit' => \App\Domains\Payment\Filament\Resources\PaymentRecordResource\Pages\EditPaymentRecord::route('/{record}/edit'),
        ];
    }
}
