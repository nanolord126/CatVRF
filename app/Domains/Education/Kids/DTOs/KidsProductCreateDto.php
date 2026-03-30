<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsProductCreateDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param array<string, int> $age_range
         * @param array<string, mixed> $material_details
         * @param array<string, mixed> $tags
         */
        public function __construct(
            public int $store_id,
            public string $name,
            public string $description,
            public int $price, // in kopecks
            public int $stock_quantity,
            public string $sku,
            public ?string $barcode,
            public array $age_range,
            public string $safety_class,
            public array $material_details,
            public string $origin_country,
            public ?string $correlation_id = null,
            public array $tags = [],
        ) {}

        /**
         * Create from request.
         */
        public static function fromRequest(array $data): self
        {
            return new self(
                store_id: (int) $data['store_id'],
                name: $data['name'],
                description: $data['description'],
                price: (int) $data['price'],
                stock_quantity: (int) $data['stock_quantity'],
                sku: $data['sku'],
                barcode: $data['barcode'] ?? null,
                age_range: $data['age_range'] ?? ['min_months' => 0, 'max_months' => 120],
                safety_class: $data['safety_class'] ?? 'B',
                material_details: $data['material_details'] ?? [],
                origin_country: $data['origin_country'] ?? 'Undefined',
                correlation_id: $data['correlation_id'] ?? null,
                tags: $data['tags'] ?? [],
            );
        }

        /**
         * Convert to array for database.
         */
        public function toArray(): array
        {
            return [
                'store_id' => $this->store_id,
                'name' => $this->name,
                'description' => $this->description,
                'price' => $this->price,
                'stock_quantity' => $this->stock_quantity,
                'sku' => $this->sku,
                'barcode' => $this->barcode,
                'age_range' => $this->age_range,
                'safety_class' => $this->safety_class,
                'material_details' => $this->material_details,
                'origin_country' => $this->origin_country,
                'correlation_id' => $this->correlation_id,
                'tags' => $this->tags,
            ];
        }
}
