<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceReviewResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditServiceReview extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ServiceReviewResource::class;

        protected function getHeaderActions(): array
        {
            return [Actions\DeleteAction::make()];
        }
}
