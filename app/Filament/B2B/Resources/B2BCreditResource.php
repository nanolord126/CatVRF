<?php declare(strict_types=1);

namespace App\Filament\B2B\Resources;



use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use App\Filament\B2B\Resources\B2BCreditResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * B2BCreditResource — управление кредитным лимитом B2B-клиента.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функции:
 * - Просмотр текущего лимита, использованного и доступного
 * - История использования кредита
 * - Статус задолженностей и просроченных платежей
 * - Запрос на увеличение лимита (Action)
 * - BusinessGroup scoping
 */
final class B2BCreditResource extends Resource
{
    public function __construct(
        private readonly Request $request,
        private readonly LoggerInterface $logger,
    ) {}

    protected static ?string $model           = \App\Models\BusinessGroup::class;
    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Кредитный лимит';
    protected static ?string $slug            = 'b2b-credit';
    protected static ?int    $navigationSort  = 4;
    protected static ?string $navigationGroup = 'Финансы';

    public static function getEloquentQuery(): Builder
    {
        $businessGroupId = session('active_business_group_id');

        return parent::getEloquentQuery()
            ->when(
                $businessGroupId,
                static fn (Builder $q) => $q->where('id', $businessGroupId)
            );
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Кредитный лимит')
                ->schema([
                    Forms\Components\TextInput::make('legal_name')
                        ->label('Юридическое лицо')
                        ->disabled(),

                    Forms\Components\TextInput::make('inn')
                        ->label('ИНН')
                        ->disabled(),

                    Forms\Components\TextInput::make('credit_limit')
                        ->label('Кредитный лимит (коп.)')
                        ->numeric()
                        ->disabled(),

                    Forms\Components\TextInput::make('credit_used')
                        ->label('Использовано (коп.)')
                        ->numeric()
                        ->disabled(),

                    Forms\Components\TextInput::make('payment_term_days')
                        ->label('Отсрочка платежа (дней)')
                        ->numeric()
                        ->disabled(),

                    Forms\Components\Select::make('b2b_tier')
                        ->label('Тариф')
                        ->options([
                            'standard' => 'Standard',
                            'silver'   => 'Silver',
                            'gold'     => 'Gold',
                            'platinum' => 'Platinum',
                        ])
                        ->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('legal_name')
                    ->label('Компания')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('inn')
                    ->label('ИНН')
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('b2b_tier')
                    ->label('Тариф')
                    ->colors([
                        'gray'    => 'standard',
                        'info'    => 'silver',
                        'warning' => 'gold',
                        'success' => 'platinum',
                    ]),

                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Лимит')
                    ->formatStateUsing(static fn ($state) => number_format($state / 100, 0, '.', ' ') . ' ₽')
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_used')
                    ->label('Использовано')
                    ->formatStateUsing(static fn ($state) => number_format($state / 100, 0, '.', ' ') . ' ₽')
                    ->color(static function ($record) {
                        if (!$record?->credit_limit) {
                            throw new \DomainException('Entity not found');
                        }
                        $pct = $record->credit_used / $record->credit_limit;
                        return $pct > 0.8 ? 'danger' : ($pct > 0.6 ? 'warning' : null);
                    }),

                Tables\Columns\TextColumn::make('payment_term_days')
                    ->label('Отсрочка')
                    ->suffix(' дней'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('request_limit_increase')
                    ->label('Запросить увеличение')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Запрос на увеличение кредитного лимита')
                    ->modalDescription('Заявка будет отправлена менеджеру платформы. Рассмотрение до 3 рабочих дней.')
                    ->action(static function ($record) {
                        $this->logger->info('B2B credit limit increase requested', [
                            'business_group_id' => $record->id,
                            'current_limit'     => $record->credit_limit,
                            'correlation_id'    => $this->request->header('X-Correlation-ID'),
                        ]);
                        // Здесь: NotificationService::notifyManager('credit_limit_increase_request', $record);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListB2BCredits::route('/'),
            'view'  => Pages\ViewB2BCredit::route('/{record}'),
        ];
    }
}
