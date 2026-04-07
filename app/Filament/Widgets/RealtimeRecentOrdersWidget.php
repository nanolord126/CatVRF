<?php declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class RealtimeRecentOrdersWidget extends TableWidget
{
    protected static ?int $sort = 3;
        protected int | string | array $columnSpan = 'full';

        public function table(Table $table): Table
        {
            $tenantId = filament()->getTenant()?->id;

            return $table
                ->query(
                    Order::query()
                        ->where('tenant_id', $tenantId)
                        ->latest('created_at')
                        ->limit(10)
                )
                ->columns([
                    Tables\Columns\TextColumn::make('id')
                        ->label('ID')
                        ->sortable()
                        ->searchable()
                        ->size('sm'),

                    Tables\Columns\TextColumn::make('user.name')
                        ->label('Клиент')
                        ->sortable()
                        ->searchable(),

                    Tables\Columns\TextColumn::make('total_price')
                        ->label('Сумма')
                        ->money('RUB')
                        ->sortable(),

                    Tables\Columns\BadgeColumn::make('status')
                        ->label('Статус')
                        ->color(fn(string $state): string => match ($state) {
                            'confirmed' => 'info',
                            'processing' => 'primary',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Создан')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),
                ])
                ->defaultSort('created_at', 'desc')
                ->paginated(false);
        }
}
