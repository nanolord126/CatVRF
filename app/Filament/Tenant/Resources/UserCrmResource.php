<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\UserCrmResource\Pages;
use App\Filament\Tenant\Resources\UserCrmResource\RelationManagers;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * CRM клиентов арендатора (КАНОН 2026).
 * Показывает всех клиентов текущего tenant с историей заказов,
 * балансом кошелька и суммой трат.
 */
final class UserCrmResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Клиенты (CRM)';

    protected static ?string $modelLabel = 'Клиент';

    protected static ?string $pluralModelLabel = 'Клиенты';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 1;

    // -------------------------------------------------------------------------
    // FORM
    // -------------------------------------------------------------------------

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Дата регистрации')
                        ->disabled(),
                ]),
            ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // TABLE
    // -------------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Регистрация')
                    ->dateTime('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_transactions_sum_amount')
                    ->label('Всего потрачено')
                    ->formatStateUsing(fn($state) => $state
                        ? number_format($state / 100, 2, '.', ' ') . ' ₽'
                        : '0.00 ₽'
                    )
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('payment_transactions_count')
                    ->label('Заказов')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('wallet_balance')
                    ->label('Баланс кошелька')
                    ->formatStateUsing(fn($state) => $state !== null
                        ? number_format((int)$state / 100, 2, '.', ' ') . ' ₽'
                        : '—'
                    )
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('last_order_at')
                    ->label('Последний заказ')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('has_orders')
                    ->label('Только с заказами')
                    ->query(fn(Builder $q) => $q->has('paymentTransactions')),

                Tables\Filters\Filter::make('active_last_30d')
                    ->label('Активные (30 дней)')
                    ->query(fn(Builder $q) => $q->whereHas('paymentTransactions', function (Builder $q) {
                        $q->where('created_at', '>=', now()->subDays(30));
                    })),

                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options([
                        'customer' => 'Клиент',
                        'business_owner' => 'Бизнес',
                        'employee' => 'Сотрудник',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Профиль'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Экспорт выбранных')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            $correlationId = (string) \Illuminate\Support\Str::uuid();
                            Log::channel('audit')->info('CRM: Export triggered', [
                                'count' => $records->count(),
                                'tenant_id' => Filament::getTenant()?->id,
                                'correlation_id' => $correlationId,
                            ]);
                        }),
                ]),
            ]);
    }

    // -------------------------------------------------------------------------
    // RELATIONS
    // -------------------------------------------------------------------------

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrderHistoryRelationManager::class,
            RelationManagers\WalletTransactionsRelationManager::class,
        ];
    }

    // -------------------------------------------------------------------------
    // PAGES
    // -------------------------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserCrm::route('/'),
            'view' => Pages\ViewUserCrm::route('/{record}'),
        ];
    }

    // -------------------------------------------------------------------------
    // QUERY (tenant scoping — КАНОН 2026)
    // -------------------------------------------------------------------------

    public static function getEloquentQuery(): Builder
    {
        $tenantId = Filament::getTenant()?->id;

        return parent::getEloquentQuery()
            ->withCount('paymentTransactions')
            ->withSum(['paymentTransactions' => fn(Builder $q) => $q
                ->where('tenant_id', $tenantId)
                ->where('status', 'captured'),
            ], 'amount')
            ->addSelect([
                'wallet_balance' => \App\Models\Wallet::select('cached_balance')
                    ->whereColumn('user_id', 'users.id')
                    ->where('tenant_id', $tenantId)
                    ->limit(1),

                'last_order_at' => \App\Models\PaymentTransaction::select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->where('tenant_id', $tenantId)
                    ->orderByDesc('created_at')
                    ->limit(1),
            ])
            ->whereHas('tenants', fn(Builder $q) => $q->where('tenant_id', $tenantId));
    }
}
