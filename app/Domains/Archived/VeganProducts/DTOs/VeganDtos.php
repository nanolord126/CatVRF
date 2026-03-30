<?php declare(strict_types=1);

namespace App\Domains\Archived\VeganProducts\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VeganProductCreateDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            public string $name,


            public string $sku,


            public string $brand,


            public int $storeId,


            public int $categoryId,


            public int $price,


            public ?int $b2bPrice = null,


            public array $nutritionInfo = [],


            public array $allergenInfo = [],


            public string $ingredients = '',


            public int $initialStock = 0,


            public ?int $shelfLifeDays = null,


            public float $weightGrams = 0.0,


            public ?string $correlationId = null,


            public array $tags = [],


        ) {}


        /**


         * Map request data to DTO with validation-ready types.


         */


        public static function fromRequest(array $data, ?string $correlationId = null): self


        {


            return new self(


                name: (string) ($data['name'] ?? ''),


                sku: (string) ($data['sku'] ?? ''),


                brand: (string) ($data['brand'] ?? ''),


                storeId: (int) ($data['vegan_store_id'] ?? 0),


                categoryId: (int) ($data['vegan_category_id'] ?? 0),


                price: (int) ($data['price'] ?? 0),


                b2bPrice: isset($data['b2b_price']) ? (int) $data['b2b_price'] : null,


                nutritionInfo: (array) ($data['nutrition_info'] ?? []),


                allergenInfo: (array) ($data['allergen_info'] ?? []),


                ingredients: (string) ($data['ingredients'] ?? ''),


                initialStock: (int) ($data['current_stock'] ?? 0),


                shelfLifeDays: isset($data['shelf_life_days']) ? (int) $data['shelf_life_days'] : null,


                weightGrams: (float) ($data['weight_grams'] ?? 0.0),


                correlationId: $correlationId,


                tags: (array) ($data['tags'] ?? []),


            );


        }


    }


    /**


     * VeganOrderProcessDto - Data for processing retail or B2B sales.


     */


    final readonly class VeganOrderProcessDto


    {


        public function __construct(


            public int $userId,


            public int $productId,


            public int $quantity,


            public bool $isB2B = false,


            public ?string $correlationId = null,


            public array $metadata = [],


        ) {}


    }


    /**


     * VeganBoxSubscriptionDto - Data for setting up recurrent food deliveries.


     */


    final readonly class VeganBoxSubscriptionDto


    {


        public function __construct(


            public int $userId,


            public int $boxId,


            public string $planType, // weekly, monthly


            public array $exclusionAllergens = [],


            public ?string $promoCode = null,


            public ?string $correlationId = null,


        ) {}


    }


    /**


     * AIVeganConstructorRequestDto - Input for the AI Box & Menu Constructor.


     */


    final readonly class AIVeganConstructorRequestDto


    {


        public function __construct(


            public int $userId,


            public string $dietGoal, // muscle_gain, weight_loss, maintain


            public array $allergies = [],


            public int $budgetLimitCop = 0,


            public int $servingsPerDay = 3,


            public array $favorites = [],


            public ?string $correlationId = null,


        ) {}
}
