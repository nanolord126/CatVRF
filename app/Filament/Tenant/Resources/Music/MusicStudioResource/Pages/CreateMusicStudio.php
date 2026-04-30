<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music\MusicStudioResource\Pages;

use Psr\Log\LoggerInterface;

use Illuminate\Database\DatabaseManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogManager;

final class CreateMusicStudio extends CreateRecord
{
    protected static string $resource = MusicStudioResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    /**
     * Mutate form data before creation to inject metadata.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = (string) Str::uuid();

        return $data;
    }

    /**
     * Handle the creation in a transaction with audit logs.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return $this->db->transaction(function () use ($data) {
            $record = static::getModel()::create($data);

            $this->log->channel('audit')->$this->logger->info('New music studio created via UI', [
                'studio_id' => $record->id,
                'tenant_id' => $record->tenant_id,
                'correlation_id' => $record->correlation_id,
                'created_by' => auth()->id(),
            ]);

            return $record;
        });
    }

    /**
     * Redirect to index page after creation.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
