<?php declare(strict_types=1);

namespace App\Domains\Archived\Gardening\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GardeningDomainService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly FraudControlService $fraudControl,


            private readonly RateLimiterService $rateLimiter


        ) {}


        /**


         * saveProduct() with transactional integrity and bi-layer update (Product + Plant)


         */


        public function saveProduct(ProductSaveDto $dto, int $tenantId): GardenProduct


        {


            $correlationId = $dto->correlationId ?? (string) Str::uuid();


            // 1. Core Security Check: Rate limiting and Fraud scoring


            $this->fraudControl->check($correlationId, 'GARDEN_PRODUCT_SAVE', ['sku' => $dto->sku]);


            return DB::transaction(function () use ($dto, $tenantId, $correlationId) {


                // 2. Main Product Creation/Update


                $product = GardenProduct::updateOrCreate(


                    ['sku' => $dto->sku, 'tenant_id' => $tenantId],


                    array_merge($dto->toArray(), [


                        'tenant_id' => $tenantId,


                        'correlation_id' => $correlationId


                    ])


                );


                // 3. Optional Bio-metadata (Plant extension)


                if ($dto->botanicalName || $dto->hardinessZone) {


                    GardenPlant::updateOrCreate(


                        ['product_id' => $product->id, 'tenant_id' => $tenantId],


                        [


                            'tenant_id' => $tenantId,


                            'botanical_name' => $dto->botanicalName,


                            'hardiness_zone' => $dto->hardinessZone,


                            'light_requirement' => $dto->lightRequirement,


                            'water_needs' => $dto->waterNeeds,


                            'care_calendar' => $dto->careCalendar,


                            'correlation_id' => $correlationId


                        ]


                    );


                }


                Log::channel('audit')->info('Gardening product persisted', [


                    'sku' => $product->sku,


                    'cid' => $correlationId,


                    'tenant' => $tenantId


                ]);


                return $product;


            });


        }


        /**


         * updateSubscriptionBox() with audit trail


         */


        public function updateSubscriptionBox(SubscriptionBoxDto $dto, int $tenantId): GardenSubscriptionBox


        {


            $correlationId = $dto->correlationId ?? (string) Str::uuid();


            return DB::transaction(function () use ($dto, $tenantId, $correlationId) {


                $box = GardenSubscriptionBox::updateOrCreate(


                    ['name' => $dto->name, 'tenant_id' => $tenantId],


                    [


                        'tenant_id' => $tenantId,


                        'frequency' => $dto->frequency,


                        'price' => $dto->price,


                        'contents_json' => $dto->contents,


                        'is_active' => $dto->isActive,


                        'correlation_id' => $correlationId


                    ]


                );


                Log::channel('audit')->info('Gardening subscription box updated', [


                    'box_id' => $box->id,


                    'cid' => $correlationId,


                    'tenant' => $tenantId


                ]);


                return $box;


            });


        }


        /**


         * getLandscaperPricing() - specialized B2B pricing logic for pros


         */


        public function getLandscaperPricing(GardenProduct $product, int $quantity): int


        {


            // Pros get B2B price minus bulk discount after 10 units


            $basePrice = $product->price_b2b;


            $bulkDiscount = match (true) {


                $quantity >= 100 => 0.15, // 15% discount


                $quantity >= 50 => 0.10, // 10% discount


                $quantity >= 10 => 0.05, // 5% discount


                default => 0.0


            };


            return (int) ($basePrice * (1 - $bulkDiscount));


        }


        /**


         * checkPlantHealthStatus() - Seasonal check helper


         */


        public function isPlantInSeason(GardenPlant $plant): bool


        {


            $month = (int) date('m');


            $calendar = $plant->care_calendar;


            // If 'active_months' key exists in calendar, check it


            if (isset($calendar['active_months']) && is_array($calendar['active_months'])) {


                return in_array($month, $calendar['active_months']);


            }


            return true; // Default to true if no meta provided


        }
}
