<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music\MusicReviewResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateMusicReview extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = MusicReviewResource::class;

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
        protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
        {
            return DB::transaction(function () use ($data) {
                $record = static::getModel()::create($data);

                Log::channel('audit')->info('New music review created via UI', [
                    'review_id' => $record->id,
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
