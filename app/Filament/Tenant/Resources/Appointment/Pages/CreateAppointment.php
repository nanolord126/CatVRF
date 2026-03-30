<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Appointment\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateRecordAppointment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = AppointmentResource::class;
}
