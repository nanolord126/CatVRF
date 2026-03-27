<?php

declare(strict_types=1);


namespace App\Domains\Pet\Events;

use App\Domains\Pet\Models\PetReview;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * ReviewCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ReviewCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly PetReview $review,
        public readonly string $correlationId,
    ) {}
}
