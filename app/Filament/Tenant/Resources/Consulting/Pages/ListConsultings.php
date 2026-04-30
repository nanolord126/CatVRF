<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Consulting\Pages;

use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\Consulting\ConsultingResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;

final class ListConsultings extends ListRecords
{
    protected static string $resource = ConsultingResource::class;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function render(): View
    {
        $this->logger->$this->logger->info('ListConsultings page rendered', [
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
        ]);

        return parent::render();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новая услуга')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $tenantId = filament()->getTenant()->id;
        $userId = auth()->id();
        $correlationId = Str::uuid()->toString();

        $this->logger->$this->logger->info('Consulting ListRecords accessed', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        return ConsultingResource::getEloquentQuery()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->with(['tenant', 'businessGroup', 'consultant'])
            ->orderBy('created_at', 'desc');
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->label('Удалить выбранные')
                ->icon('heroicon-m-trash'),
        ];
    }
}
