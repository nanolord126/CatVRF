<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Ritual\FuneralOrderResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateFuneralOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FuneralOrderResource::class;

        /**
         * Мутация данных перед сохранением (Канон: Correlation ID + Tenant ID).
         */
        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['correlation_id'] = (string) Str::uuid();

            if (function_exists('tenant') && tenant('id')) {
                $data['tenant_id'] = (int) tenant('id');
            }

            return $data;
        }

        /**
         * Валидация прав и Fraud Check перед созданием (Канон 2026).
         */
        protected function beforeCreate(): void
        {
            /** @var FraudControlService $fraud */
            $fraud = app(FraudControlService::class);

            $fraud->check([
                'operation' => 'ritual_order_create_filament',
                'client_id' => $this->data['client_id'] ?? null,
                'amount' => $this->data['total_amount_kopecks'] ?? 0,
                'correlation_id' => $this->data['correlation_id'] ?? null,
            ]);

            Log::channel('audit')->info('Creating ritual order from Filament', [
                'data' => $this->data,
            ]);
        }

        /**
         * Выполнение в транзакции (Канон 2026).
         */
        protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
        {
            return DB::transaction(function () use ($data) {
                $record = parent::handleRecordCreation($data);

                Log::channel('audit')->info('Ritual order record created in DB', [
                    'order_id' => $record->id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);

                return $record;
            });
        }

        /**
         * Редирект после создания.
         */
        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
