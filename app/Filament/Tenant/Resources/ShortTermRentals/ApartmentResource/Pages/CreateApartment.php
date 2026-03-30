<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateApartment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ApartmentResource::class;
}
