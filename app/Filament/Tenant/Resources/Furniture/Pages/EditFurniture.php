<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Furniture\Pages;




use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\Furniture\FurnitureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EditFurniture extends EditRecord
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FurnitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Удалить')
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->db->transaction(function () use (&$data) {
            $data['correlation_id'] = Str::uuid()->toString();
            $data['tenant_id'] = filament()->getTenant()->id;

            $this->logger->info('Furniture updated', [
                'user_id' => $this->guard->id(),
                'correlation_id' => $data['correlation_id'],
                'tenant_id' => $data['tenant_id'],
                'record_id' => $this->record->id,
            ]);
        });

        return $data;
    }

    protected function afterSave(): void
    {
        $this->logger->info('Furniture edit page saved', [
            'record_id' => $this->record->id,
            'user_id' => $this->guard->id(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
