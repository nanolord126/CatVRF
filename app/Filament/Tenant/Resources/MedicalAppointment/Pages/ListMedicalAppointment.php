<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalAppointment\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListRecordsMedicalAppointment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = MedicalAppointmentResource::class;
}
