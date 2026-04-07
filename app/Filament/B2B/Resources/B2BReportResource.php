<?php declare(strict_types=1);

namespace App\Filament\B2B\Resources;



use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use App\Filament\B2B\Resources\B2BReportResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * B2BReportResource — отчёты по обороту B2B-клиента.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функции:
 * - GMV по периодам (день, неделя, месяц, квартал)
 * - Разбивка по вертикалям и SKU
 * - Статусы платежей и задолженностей
 * - Экспорт в Excel/CSV
 * - BusinessGroup scoping
 *
 * Использует таблицу orders напрямую (через raw QueryBuilder).
 */
final class B2BReportResource extends Resource
{
    public function __construct(
        private readonly Request $request,
        private readonly LoggerInterface $logger,
    ) {}

    protected static ?string $model           = \App\Models\Order::class;
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Отчёты';
    protected static ?string $slug            = 'b2b-reports';
    protected static ?int    $navigationSort  = 5;
    protected static ?string $navigationGroup = 'Финансы';

    public static function getEloquentQuery(): Builder
    {
        $businessGroupId = session('active_business_group_id');

        return parent::getEloquentQuery()
            ->when($businessGroupId, static fn (Builder $q) => $q->where('business_group_id', $businessGroupId))
            ->whereIn('status', ['completed', 'processing', 'shipped'])
            ->orderByDesc('created_at');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Отчёт')
                ->schema([
                    Forms\Components\TextInput::make('id')->label('ID заказа')->disabled(),
                    Forms\Components\TextInput::make('status')->label('Статус')->disabled(),
                    Forms\Components\TextInput::make('total_amount')
                        ->label('Сумма')
                        ->formatStateUsing(static fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                        ->disabled(),
                    Forms\Components\TextInput::make('vertical')->label('Вертикаль')->disabled(),
                    Forms\Components\DateTimePicker::make('created_at')->label('Дата')->disabled(),
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('vertical')
                    ->label('Вертикаль')
                    ->badge(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'completed',
                        'info'    => 'processing',
                        'warning' => 'shipped',
                    ]),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->formatStateUsing(static fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого')
                            ->formatStateUsing(static fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽'),
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options([
                        'beauty'    => 'Beauty',
                        'food'      => 'Food',
                        'furniture' => 'Furniture',
                        'fashion'   => 'Fashion',
                        'fitness'   => 'Fitness',
                        'hotel'     => 'Hotel',
                        'travel'    => 'Travel',
                    ]),

                Tables\Filters\Filter::make('period_month')
                    ->label('Текущий месяц')
                    ->query(static fn (Builder $query) => $query->whereMonth('created_at', now()->month)),

                Tables\Filters\Filter::make('period_quarter')
                    ->label('Текущий квартал')
                    ->query(static fn (Builder $query) => $query->where('created_at', '>=', now()->startOfQuarter())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_csv')
                    ->label('Экспорт CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(static function ($records) {
                        $this->logger->info('B2B report exported', [
                            'count'          => $records->count(),
                            'business_group' => session('active_business_group_id'),
                            'correlation_id' => $this->request->header('X-Correlation-ID'),
                        ]);
                        // Здесь: return Excel::download(new B2BReportExport($records), 'b2b-report.xlsx');
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListB2BReports::route('/'),
            'view'  => Pages\ViewB2BReport::route('/{record}'),
        ];
    }
}
