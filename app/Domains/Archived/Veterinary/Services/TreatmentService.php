<?php declare(strict_types=1);

namespace App\Domains\Archived\Veterinary\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TreatmentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private FraudControlService $fraudControl,


            private string $correlationId = ''


        ) {


        }


        private function getCorrelationId(): string


        {


            return $this->correlationId ?: Str::uuid()->toString();


        }


        /**


         * Create a medical record for a pet


         */


        public function createRecord(array $data): MedicalRecord


        {


            $correlationId = $this->getCorrelationId();


            Log::channel('audit')->info('TreatmentService: Creating medical record', [


                'pet_id' => $data['pet_id'] ?? 'unknown',


                'correlation_id' => $correlationId


            ]);


            $this->fraudControl->check();


            return DB::transaction(function () use ($data, $correlationId) {


                $record = MedicalRecord::create(array_merge($data, [


                    'correlation_id' => $correlationId


                ]));


                Log::channel('audit')->info('TreatmentService: Medical record created', [


                    'id' => $record->id,


                    'correlation_id' => $correlationId


                ]);


                return $record;


            });


        }


        /**


         * Get full pet medical history


         */


        public function getPetHistory(int $petId): \Illuminate\Support\Collection


        {


            return MedicalRecord::where('pet_id', $petId)->orderByDesc('created_at')->get();


        }


        /**


         * Schedule next visit as recommendation


         */


        public function scheduleFollowUp(int $petId, int $veterinarianId, \DateTimeInterface $at): void


        {


            $correlationId = $this->getCorrelationId();


            Log::channel('audit')->info('TreatmentService: Scheduling follow-up', [


                'pet_id' => $petId,


                'at' => $at->format('Y-m-d H:i:s'),


                'correlation_id' => $correlationId


            ]);


            // This could trigger a notification/email to the pet owner


        }
}
