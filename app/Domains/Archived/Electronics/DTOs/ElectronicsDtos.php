<?php declare(strict_types=1);

namespace App\Domains\Archived\Electronics\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductCreateDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**


         * @param array<string, mixed> $specs


         * @param array<string> $tags


         */


        public function __construct(


            public int $categoryId,


            public int $storeId,


            public string $name,


            public string $sku,


            public string $brand,


            public string $modelNumber,


            public int $priceKopecks,


            public ?int $b2bPriceKopecks = null,


            public int $initialStock = 0,


            public string $availability = 'in_stock',


            public array $specs = [],


            public array $tags = [],


            public string $correlationId = '',


            public ?float $weightKg = null,


        ) {}


        /**


         * Create from array or request.


         */


        public static function fromArray(array $data): self


        {


            return new self(


                categoryId: (int) $data['category_id'],


                storeId: (int) $data['store_id'],


                name: (string) $data['name'],


                sku: (string) $data['sku'],


                brand: (string) $data['brand'],


                modelNumber: (string) ($data['model_number'] ?? ''),


                priceKopecks: (int) $data['price_kopecks'],


                b2bPriceKopecks: isset($data['b2b_price_kopecks']) ? (int) $data['b2b_price_kopecks'] : null,


                initialStock: (int) ($data['initial_stock'] ?? 0),


                availability: (string) ($data['availability'] ?? 'in_stock'),


                specs: (array) ($data['specs'] ?? []),


                tags: (array) ($data['tags'] ?? []),


                correlationId: (string) ($data['correlation_id'] ?? ''),


                weightKg: isset($data['weight_kg']) ? (float) $data['weight_kg'] : null,


            );


        }


    }


    /**


     * OrderProcessDto - For checkout operations.


     */


    final readonly class OrderProcessDto


    {


        /**


         * @param array<int, int> $items [productId => quantity]


         */


        public function __construct(


            public int $userId,


            public array $items,


            public string $mode = 'b2c', // b2c or b2b


            public ?string $businessId = null,


            public ?string $promoCode = null,


            public string $correlationId = '',


        ) {}


    }


    /**


     * WarrantyRegisterDto - For post-sale service tracking.


     */


    final readonly class WarrantyRegisterDto


    {


        public function __construct(


            public int $productId,


            public string $serialNumber,


            public string $orderId,


            public int $userId,


            public int $monthsDuration = 12,


            public string $correlationId = '',


        ) {}


    }


    /**


     * AISuggestionRequestDto - For AI constructor inputs.


     */


    final readonly class AISuggestionRequestDto


    {


        /**


         * @param array<string> $preferredBrands


         * @param array<string> $interests


         */


        public function __construct(


            public string $categorySlug,


            public int $budgetMaxKopecks,


            public array $preferredBrands = [],


            public array $interests = [],


            public string $correlationId = '',


        ) {}
}
