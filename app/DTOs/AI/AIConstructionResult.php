<?php declare(strict_types=1);

namespace App\DTOs\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIConstructionResult extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            public string $vertical,
            public string $type, // 'image', 'list', 'design', 'calculation'
            public array $payload, // Основные данные генерации
            public array $suggestions, // Рекомендованные товары из Inventory
            public float $confidence_score,
            public string $correlation_id
        ) {}

        /**
         * Преобразовать в массив для ответа/логирования
         */
        public function toArray(): array
        {
            return [
                'vertical' => $this->vertical,
                'type' => $this->type,
                'payload' => $this->payload,
                'suggestions' => $this->suggestions,
                'confidence_score' => $this->confidence_score,
                'correlation_id' => $this->correlation_id,
            ];
        }
}
