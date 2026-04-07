<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\AutoPartResource\Pages;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateAutoPart extends CreateRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    protected static string $resource = AutoPartResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = Str::uuid()->toString();
            $data['correlation_id'] = $correlationId;

            // Fraud check перед созданием
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'create_auto_part', amount: 0, correlationId: $correlationId ?? '');

            if (!$fraudCheck['allowed']) {
                $this->notification->make()
                    ->title('Подозрение на мошенничество')
                    ->body($fraudCheck['reason'] ?? 'Операция заблокирована')
                    ->danger()
                    ->send();

                $this->halt();
            }

            $this->logger->info('Creating auto part', [
                'correlation_id' => $correlationId,
                'tenant_id' => filament()->getTenant()->id,
                'user_id' => $this->guard->id(),
                'data' => $data,
            ]);

            return $data;
        }

        protected function afterCreate(): void
        {
            $this->db->transaction(function () {
                // Событие создания запчасти
                event(new AutoPartCreated(
                    $this->record,
                    $this->record->correlation_id
                ));

                $this->logger->info('Auto part created successfully', [
                    'correlation_id' => $this->record->correlation_id,
                    'part_id' => $this->record->id,
                    'sku' => $this->record->sku,
                    'tenant_id' => filament()->getTenant()->id,
                ]);

                $this->notification->make()
                    ->title('Запчасть создана')
                    ->body("SKU: {$this->record->sku}, остаток: {$this->record->current_stock} шт")
                    ->success()
                    ->send();
            });
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
