<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinicResource\Pages;



use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;

final class EditDentalClinic extends EditRecord
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = DentalClinicResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ];
        }

        protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
        {
            return $this->db->transaction(function () use ($record, $data) {
                $oldName = $record->name;
                $record = parent::handleRecordUpdate($record, $data);

                $this->logger->info('Dental Clinic Updated', [
                    'clinic_id' => $record->id,
                    'old_name' => $oldName,
                    'new_name' => $record->name,
                    'correlation_id' => $record->correlation_id
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
