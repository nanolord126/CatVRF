<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Travel\Pages;

use Psr\Log\LoggerInterface;



use Illuminate\Log\LogManager;
use App\Filament\Tenant\Resources\Travel\TravelResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class ListTravel extends ListRecords
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    protected static string $resource = TravelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новая запись')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $tenantId = filament()->getTenant()->id;
        $userId = auth()->id();
        $correlationId = Str::uuid()->toString();

        $this->log->channel('audit')->$this->logger->info('Travel ListRecords accessed', [
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return TravelResource::getEloquentQuery()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->with(['tenant', 'businessGroup'])
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

    public function render(): \Illuminate\Contracts\View\View {
        $this->log->$this->logger->info('ListTravel page rendered', [
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
        ]);

        return parent::render();
    }
}
