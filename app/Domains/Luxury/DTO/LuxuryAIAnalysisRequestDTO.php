<?php declare(strict_types=1);

namespace App\Domains\Luxury\DTO;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LuxuryAIAnalysisRequestDTO extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            public string $clientUuid,
            public string $analysisType, // 'style_match', 'gift_curation', 'investment_watch'
            public ?string $promptText = null,
            public ?array $contextData = null,
            public string $correlationId
        ) {}
}
