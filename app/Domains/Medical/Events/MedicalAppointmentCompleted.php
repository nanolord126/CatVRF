<?php declare(strict_types=1);

namespace App\Domains\Medical\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalAppointmentCompleted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, SerializesModels;

        public function __construct(
            public MedicalAppointment $appointment,
            public MedicalRecord $record,
            public string $correlation_id
        ) {}
}
