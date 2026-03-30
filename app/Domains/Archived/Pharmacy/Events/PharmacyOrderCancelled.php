<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PharmacyOrderCancelled extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, SerializesModels;


        public function __construct(public readonly string $correlationId, public readonly mixed $order) {}
}
