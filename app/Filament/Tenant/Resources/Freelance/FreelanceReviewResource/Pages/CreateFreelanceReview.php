<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelanceReviewResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateFreelanceReview extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FreelanceReviewResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['correlation_id'] = (string) Str::uuid();
            $data['tenant_id'] = auth()->user()->tenant_id;

            return $data;
        }

        protected function afterCreate(): void
        {
            // Пересчет среднего рейтинга фрилансера (Канон 2026)
            $freelancer = $this->record->freelancer;
            $avgRating = FreelanceReview::where('freelancer_id', $freelancer->id)->avg('rating');
            $freelancer->update(['rating' => $avgRating]);
        }
}
