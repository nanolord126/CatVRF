<?php declare(strict_types=1);

namespace App\Filament\Widgets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RealtimeRecentOrdersWidget extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                            'pending' => 'warning',
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
