<?php declare(strict_types=1);

namespace App\Domains\Archived\Veterinary\Services\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetHealthConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**


         * @param RecommendationService $recommendation


         * @param string $correlationId


         */


        public function __construct(


            private RecommendationService $recommendation,


            private string $correlationId = ''


        ) {


        }


        private function getCorrelationId(): string


        {


            return $this->correlationId ?: Str::uuid()->toString();


        }


        /**


         * Main Entry: Analyze Pet State and Suggest Services


         */


        public function analyzeAndSuggest(


            int $petId,


            ?UploadedFile $photo = null,


            ?string $symptoms = null,


            array $context = []


        ): array {


            $correlationId = $this->getCorrelationId();


            $pet = Pet::findOrFail($petId);


            Log::channel('audit')->info('PetHealthConstructor: Starting analysis', [


                'pet_id' => $petId,


                'pet_uuid' => $pet->uuid,


                'correlation_id' => $correlationId


            ]);


            // 1. Analyze pet metadata (Age, Species, Breed)


            $petAge = $pet->birth_date ? $pet->birth_date->age : 2; // Default age if not set


            $ageFactor = $this->resolveAgeFactor($petAge);


            // 2. Vision analysis (if photo provided)


            $visionResult = $photo ? $this->analyzePhotoWithAI($photo) : ['condition' => 'unknown'];


            // 3. NLP Symptom analysis (if symptoms provided)


            $nlpResult = $symptoms ? $this->analyzeSymptoms($symptoms) : ['severity' => 'low'];


            // 4. Cross-domain recommendations (AI Core)


            $suggestedItems = $this->recommendation->getForUser(


                userId: $pet->owner_id,


                vertical: 'Veterinary',


                context: array_merge($context, [


                    'pet_age' => $petAge,


                    'pet_species' => $pet->species,


                    'symptoms' => $symptoms,


                    'vision_result' => $visionResult,


                    'severity' => $nlpResult['severity'] ?? 'low',


                    'age_factor' => $ageFactor


                ])


            );


            // 5. Build final health roadmap


            $roadmap = [


                'pet' => [


                    'name' => $pet->name,


                    'age' => $petAge,


                    'species' => $pet->species,


                ],


                'ai_analysis' => [


                    'condition_summary' => $visionResult['condition'],


                    'severity' => $nlpResult['severity'],


                    'confidence_score' => $visionResult['confidence'] ?? 0.85


                ],


                'recommended_services' => $suggestedItems->map(fn($item) => [


                    'name' => $item['name'] ?? 'Treatment',


                    'service_id' => $item['service_id'] ?? null,


                    'price' => $item['price'] ?? 0,


                    'reason' => 'Ближайшая запись рекомендована на основе ' . ($symptoms ? 'симптомов' : 'возраста'),


                ]),


                'emergency_needed' => ($nlpResult['severity'] === 'high' || $visionResult['urgent'] === true),


                'correlation_id' => $correlationId


            ];


            Log::channel('audit')->info('PetHealthConstructor: Analysis complete', [


                'pet_id' => $petId,


                'emergency' => $roadmap['emergency_needed'],


                'correlation_id' => $correlationId


            ]);


            return $roadmap;


        }


        /**


         * Logic: How age affects pet recommendations


         */


        private function resolveAgeFactor(int $age): string


        {


            return match (true) {


                $age < 1 => 'pup_kitten_vaccination',


                $age > 10 => 'senior_annual_check',


                default => 'regular_active_pet'


            };


        }


        /**


         * Vision Layer: Mocking API call to Vision-enabled AI


         */


        private function analyzePhotoWithAI(UploadedFile $photo): array


        {


            // Integration with OpenAI Vision or GigaChat would happen here


            return [


                'condition' => 'healthy_coat_active',


                'urgent' => false,


                'confidence' => 0.92


            ];


        }


        /**


         * NLP Layer: Analyze symptoms string


         */


        private function analyzeSymptoms(string $text): array


        {


            $keywords = ['кровь', 'рвота', 'не дышит', 'травма'];


            foreach ($keywords as $word) {


                if (mb_stripos($text, $word) !== false) {


                    return ['severity' => 'high'];


                }


            }


            return ['severity' => 'medium'];


        }
}
