<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music\MusicReviewResource\Pages;

use Psr\Log\LoggerInterface;

use Illuminate\Database\DatabaseManager;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogManager;

final class EditMusicReview extends EditRecord
{
    protected static string $resource = MusicReviewResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Re-generate correlation_id for audit.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();

        return $data;
    }

    /**
     * Handle updates in a transaction.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->db->transaction(function () use ($record, $data) {
            $record->update($data);

            $this->log->channel('audit')->$this->logger->info('Music review updated via UI', [
                'review_id' => $record->id,
                'tenant_id' => $record->tenant_id,
                'correlation_id' => $record->correlation_id,
                'updated_by' => auth()->id(),
            ]);

            return $record;
        });
    }

    /**
     * Redirect to index page after save.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
