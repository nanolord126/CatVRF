<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalSupplies\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateRecordMedicalSupplies extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = MedicalSuppliesResource::class;
}
