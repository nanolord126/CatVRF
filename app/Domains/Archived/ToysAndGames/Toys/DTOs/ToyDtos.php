<?php declare(strict_types=1);

namespace App\Domains\Archived\ToysAndGames\Toys\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ToySaveDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            public int $tenantId,


            public int $storeId,


            public int $categoryId,


            public int $ageGroupId,


            public string $title,


            public string $sku,


            public string $description,


            public int $priceB2c, // in kopecks


            public int $priceB2b, // in kopecks


            public int $stockQuantity,


            public string $safetyCertification,


            public string $materialType,


            public bool $isGiftWrappable = true,


            public bool $isActive = true,


            public array $tags = [],


            public array $metadata = [],


            public ?string $correlationId = null


        ) {}


        /**


         * Factory from raw request data.


         */


        public static function fromArray(array $data, int $tenantId, string $cid): self


        {


            return new self(


                tenantId: $tenantId,


                storeId: (int) ($data['store_id'] ?? 0),


                categoryId: (int) ($data['category_id'] ?? 0),


                ageGroupId: (int) ($data['age_group_id'] ?? 0),


                title: (string) ($data['title'] ?? ''),


                sku: (string) ($data['sku'] ?? ''),


                description: (string) ($data['description'] ?? ''),


                priceB2c: (int) ($data['price_b2c'] ?? 0),


                priceB2b: (int) ($data['price_b2b'] ?? 0),


                stockQuantity: (int) ($data['stock_quantity'] ?? 0),


                safetyCertification: (string) ($data['safety_certification'] ?? ''),


                materialType: (string) ($data['material_type'] ?? 'unknown'),


                isGiftWrappable: (bool) ($data['is_gift_wrappable'] ?? true),


                isActive: (bool) ($data['is_active'] ?? true),


                tags: (array) ($data['tags'] ?? []),


                metadata: (array) ($data['metadata'] ?? []),


                correlationId: $cid


            );


        }


    }


    /**


     * ToyAIRequestDto (Layer 2)


     * Input for the AI Toy & Game Constructor.


     * Features: context-sensitive matching for kids' interests.


     */


    final readonly class ToyAIRequestDto


    {


        public function __construct(


            public int $userId,


            public int $ageMonths,


            public array $interests, // e.g., ['space', 'dinosaurs', 'coding']


            public int $budgetLimit, // in kopecks


            public bool $educationalOnly = false,


            public bool $b2bMode = false


        ) {}


    }


    /**


     * VolumeToyOrderDto (Layer 2)


     * Normalized structure for bulk B2B B2B institutional procurement.


     */


    final readonly class VolumeToyOrderDto


    {


        public function __construct(


            public int $companyId,


            public int $storeId,


            public array $items, // array of ['toy_id' => int, 'quantity' => int]


            public string $correlationId,


            public bool $giftPackaging = false,


            public array $metadata = []


        ) {}
}
