<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinicResource\Pages;




use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentalClinic extends CreateRecord
{
    public function __construct(
        private readonly Request $request,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = DentalClinicResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['tenant_id'] = tenant()->id;
            $data['correlation_id'] = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid();

            return $data;
        }

        protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
        {
            return $this->db->transaction(function () use ($data) {
                $record = parent::handleRecordCreation($data);

                \Illuminate\Support\Facades\Log::channel('audit')->info('Dental Clinic Created', [
                    'clinic_id' => $record->id,
                    'name' => $record->name,
                    'correlation_id' => $data['correlation_id']
                ]);

                return $record;
            });
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
