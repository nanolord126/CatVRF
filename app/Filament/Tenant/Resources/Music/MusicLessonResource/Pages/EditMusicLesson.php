<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music\MusicLessonResource\Pages;




use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\EditRecord;

final class EditMusicLesson extends EditRecord
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = MusicLessonResource::class;

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
        protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
        {
            return $this->db->transaction(function () use ($record, $data) {
                $record->update($data);

                $this->logger->info('Music lesson updated via UI', [
                    'lesson_id' => $record->id,
                    'tenant_id' => $record->tenant_id,
                    'correlation_id' => $record->correlation_id,
                    'updated_by' => $this->guard->id(),
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
