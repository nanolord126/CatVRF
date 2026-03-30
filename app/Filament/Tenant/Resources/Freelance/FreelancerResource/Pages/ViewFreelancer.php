<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelancerResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ViewFreelancer extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FreelancerResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\EditAction::make(),
            ];
        }
}
