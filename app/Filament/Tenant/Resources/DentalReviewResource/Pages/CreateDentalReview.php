<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalReviewResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateDentalReview extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = DentalReviewResource::class;

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
