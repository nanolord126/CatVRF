<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Ritual\FuneralOrderResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListFuneralOrders extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FuneralOrderResource::class;

        /**
         * Заголовочные действия (Канон: Минимум одна кнопка).
         */
        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Создать заказ')
                    ->icon('heroicon-o-plus'),

                Actions\Action::make('ritual_report')
                    ->label('Выгрузить отчет')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn () => Log::channel('audit')->info('Ritual report downloaded')),
            ];
        }

        /**
         * Изоляция на уровне запроса таблицы (Tenant + Business Group Scoping Канон).
         */
        protected function getTableQuery(): ?Builder
        {
            $query = parent::getTableQuery();

            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }

            return $query;
        }

        /**
         * Логирование входа на страницу (Audit Log Канон).
         */
        public function mount(): void
        {
            parent::mount();

            Log::channel('audit')->info('Ritual orders list visited', [
                'user_id' => auth()->id(),
                'tenant_id' => function_exists('tenant') ? tenant('id') : null,
            ]);
        }
}
