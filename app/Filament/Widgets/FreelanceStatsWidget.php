<?php declare(strict_types=1);

namespace App\Filament\Widgets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelanceStatsWidget extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $pollingInterval = '30s';

        protected function getStats(): array
        {
            return [
                Stat::make('Всего фрилансеров', Freelancer::count())
                    ->description('Зарегистрированных специалистов')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->chart([7, 12, 11, 15, 18, 20, 25])
                    ->color('success'),

                Stat::make('Активные заказы', FreelanceOrder::where('status', 'in_progress')->count())
                    ->description('В процессе выполнения')
                    ->descriptionIcon('heroicon-m-briefcase')
                    ->chart([1, 5, 2, 8, 4, 10, 12])
                    ->color('warning'),

                Stat::make('Оборот биржи (₽)', number_format(FreelanceOrder::sum('budget_kopecks') / 100, 2, '.', ' '))
                    ->description('Общий объем сделок')
                    ->descriptionIcon('heroicon-m-currency-ruble')
                    ->chart([1000, 5000, 15000, 30000, 45000, 80000, 120000])
                    ->color('info'),
            ];
        }
}
