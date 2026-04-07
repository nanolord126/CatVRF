<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\AutoServiceOrderResource\Pages;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateAutoServiceOrder extends CreateRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    protected static string $resource = AutoServiceOrderResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = Str::uuid()->toString();
            $data['correlation_id'] = $correlationId;
            $data['status'] = 'pending';

            // Fraud check перед созданием
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'create_auto_service_order', amount: 0, correlationId: $correlationId ?? '');

            if (!$fraudCheck['allowed']) {
                $this->notification->make()
                    ->title('Подозрение на мошенничество')
                    ->body($fraudCheck['reason'] ?? 'Операция заблокирована')
                    ->danger()
                    ->send();

                $this->halt();
            }

            $this->logger->info('Creating auto service order', [
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
                // Событие создания заказа-наряда
                event(new AutoServiceOrderCreated(
                    $this->record,
                    $this->record->correlation_id
                ));

                $this->logger->info('Auto service order created successfully', [
                    'correlation_id' => $this->record->correlation_id,
                    'order_id' => $this->record->id,
                    'service_type' => $this->record->service_type,
                    'appointment_datetime' => $this->record->appointment_datetime,
                    'tenant_id' => filament()->getTenant()->id,
                ]);

                $this->notification->make()
                    ->title('Заказ-наряд создан')
                    ->body("Услуга: {$this->record->service_type}, дата: {$this->record->appointment_datetime->format('d.m.Y H:i')}")
                    ->success()
                    ->send();
            });
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
