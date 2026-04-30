<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinicResource\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogManager;

final class CreateDentalClinic extends CreateRecord
{
    protected static string $resource = DentalClinicResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->db->transaction(function () use ($data) {
            $record = parent::handleRecordCreation($data);

            $this->log->channel('audit')->$this->logger->info('Dental Clinic Created', [
                'clinic_id' => $record->id,
                'name' => $record->name,
                'correlation_id' => $data['correlation_id'],
            ]);

            return $record;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
