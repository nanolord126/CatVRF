<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentistResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateDentist extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = DentistResource::class;

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
