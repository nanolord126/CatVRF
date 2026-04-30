<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Ritual\FuneralOrderResource\Pages;

use Psr\Log\LoggerInterface;

use App\Filament\Tenant\Resources\Ritual\FuneralOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Log\LogManager;

final class ListFuneralOrders extends ListRecords
{
    protected static string $resource = FuneralOrderResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Логирование входа на страницу (Audit Log Канон).
     */
    public function mount(): void
    {
        parent::mount();

        $this->log->channel('audit')->$this->logger->info('Ritual orders list visited', [
            'user_id' => auth()->id(),
            'tenant_id' => function_exists('tenant') ? tenant('id') : null,
        ]);
    }

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
                ->action(fn () => $this->log->channel('audit')->$this->logger->info('Ritual report downloaded')),
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
}
