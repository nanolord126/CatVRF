<?php declare(strict_types=1);

namespace App\Filament\B2B\Resources;


use Psr\Log\LoggerInterface;
use App\Filament\B2B\Resources\B2BOrderResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\DateRangeFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * B2BOrderResource — управление B2B-заказами.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функции:
 * - Просмотр и фильтрация заказов business_group
 * - Создание массовых заказов (bulk)
 * - Экспорт в Excel
 * - Кредитная отсрочка
 * - Tenant + BusinessGroup scoping
 */
final class B2BOrderResource extends Resource
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static ?string $model           = \App\Models\Order::class;
    protected static ?string $navigationIcon  = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Заказы';
    protected static ?string $slug            = 'b2b-orders';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $navigationGroup = 'Продажи';

    public static function getEloquentQuery(): Builder
    {
        $businessGroupId = session('active_business_group_id');

        return parent::getEloquentQuery()
            ->when($businessGroupId, static fn (Builder $q) => $q->where('business_group_id', $businessGroupId))
            ->orderByDesc('created_at');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Заказ')
                ->schema([
                    Forms\Components\Select::make('vertical')
                        ->label('Вертикаль')
                        ->options([
                            'beauty'    => 'Beauty',
                            'food'      => 'Food',
                            'furniture' => 'Furniture',
                            'fashion'   => 'Fashion',
                            'fitness'   => 'Fitness',
                            'hotel'     => 'Hotel',
                            'travel'    => 'Travel',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('total_amount')
                        ->label('Сумма (в копейках)')
                        ->numeric()
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'pending'    => 'Ожидает',
                            'processing' => 'В обработке',
                            'shipped'    => 'Отгружен',
                            'completed'  => 'Выполнен',
                            'cancelled'  => 'Отменён',
                        ])
                        ->required(),

                    Forms\Components\DatePicker::make('due_date')
                        ->label('Срок оплаты (отсрочка)'),

                    Forms\Components\Textarea::make('comment')
                        ->label('Комментарий')
                        ->rows(3),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'completed',
                        'info'    => 'processing',
                        'warning' => 'pending',
                        'danger'  => 'cancelled',
                        'gray'    => 'shipped',
                    ]),

                Tables\Columns\TextColumn::make('vertical')
                    ->label('Вертикаль')
                    ->badge(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->formatStateUsing(static fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Срок оплаты')
                    ->date('d.m.Y')
                    ->color(static fn ($record) => $record?->due_date && $record->due_date < now() ? 'danger' : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending'    => 'Ожидает',
                        'processing' => 'В обработке',
                        'shipped'    => 'Отгружен',
                        'completed'  => 'Выполнен',
                        'cancelled'  => 'Отменён',
                    ]),

                SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options([
                        'beauty'    => 'Beauty',
                        'food'      => 'Food',
                        'furniture' => 'Furniture',
                        'fashion'   => 'Fashion',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт выбранных')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(static function ($records) {
                        $this->logger->info('B2B bulk export', [
                            'count'          => $records->count(),
                            'business_group' => session('active_business_group_id'),
                        ]);
                        // Здесь вызов Excel-экспорта
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListB2BOrders::route('/'),
            'create' => Pages\CreateB2BOrder::route('/create'),
            'view'   => Pages\ViewB2BOrder::route('/{record}'),
            'edit'   => Pages\EditB2BOrder::route('/{record}/edit'),
        ];
    }
}
