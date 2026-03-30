<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PropertySold extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, SerializesModels;

        public function __construct(
            public readonly SaleListing $listing,
            public readonly string $correlationId,
        ) {}
}
