<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Ritual\FuneralOrderResource\Pages;



use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use App\Filament\Tenant\Resources\Ritual\FuneralOrderResource;
use App\Services\FraudControlService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateFuneralOrder extends CreateRecord
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

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

        $this->logger->info('Creating ritual order from Filament', [
            'data' => $this->data,
        ]);
    }

    /**
     * Выполнение в транзакции (Канон 2026).
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return $this->db->transaction(function () use ($data) {
            $record = parent::handleRecordCreation($data);

            $this->logger->info('Ritual order record created in DB', [
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
