<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pet\Pages;




use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\Pet\PetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditPet extends EditRecord
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = PetResource::class;

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

            $this->logger->info('Pet updated', [
                'user_id' => auth()->id(),
                'correlation_id' => $data['correlation_id'],
                'tenant_id' => $data['tenant_id'],
                'record_id' => $this->record->id,
            ]);
        });

        return $data;
    }

    protected function afterSave(): void
    {
        $this->logger->info('Pet edit page saved', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
