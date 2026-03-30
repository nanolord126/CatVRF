<?php declare(strict_types=1);

namespace App\Domains\RealEstate\DTO;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIPropertyMatchResultDTO extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * КАНОН 2026: DTO для результатов AI сопоставления.
         *
         * @param Collection $matchedProperties Коллекция найденных объектов Property
         * @param array $scores Ассоциативный массив [property_uuid => match_score]
         * @param string $dream Исходный запрос пользователя
         * @param string $correlation_id Идентификатор трассировки
         */
        public function __construct(
            public Collection $matchedProperties,
            public array $scores,
            public string $dream,
            public string $correlation_id
        ) {}

        public function toArray(): array
        {
            return [
                'count' => $this->matchedProperties->count(),
                'dream' => $this->dream,
                'correlation_id' => $this->correlation_id,
                'matches' => $this->matchedProperties->map(fn ($p) => [
                    'uuid' => $p->uuid,
                    'name' => $p->name,
                    'score' => $this->scores[$p->uuid] ?? 0,
                    'price' => $p->listings->first()?->price ?? 0,
                ])->toArray(),
            ];
        }
}
