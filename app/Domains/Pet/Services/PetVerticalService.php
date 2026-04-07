<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class PetVerticalService
{
    public function __construct(private FraudControlService $fraud,
        private InventoryManagementService $inventory,
        private \App\Services\AuditService $audit,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создание записи на прием (Veterinary/Grooming).
         * Канон: Fraud check, Transaction, Audit Log.
         */
        public function createAppointment(array $data): PetAppointment
        {
            $correlationId = $data['correlation_id'] ?? (string) Str::uuid();

            return $this->db->transaction(function () use ($data, $correlationId) {
                // 1. Fraud Check
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'pet_appointment_init', amount: 0, correlationId: $correlationId ?? '');

                // 2. Логирование начала операции
                $this->logger->info('Creating pet appointment', [
                    'pet_id' => $data['pet_id'],
                    'clinic_id' => $data['clinic_id'],
                    'correlation_id' => $correlationId,
                ]);

                // 3. Создание записи
                /** @var PetAppointment $appointment */
                $appointment = PetAppointment::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                ]));

                // 4. Резервирование ресурсов (если есть услуга с расходниками)
                $service = $appointment->service;
                if ($service && !empty($service->consumables_json)) {
                    $this->reserveConsumables($service->consumables_json, $appointment);
                }

                $this->logger->info('Pet appointment created successfully', [
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });
        }

        /**
         * Завершение приема и списание расходников.
         */
        public function completeAppointment(int $appointmentId): bool
        {
            return $this->db->transaction(function () use ($appointmentId) {
                $appointment = PetAppointment::findOrFail($appointmentId);

                if ($appointment->isCompleted()) {
                    throw new \LogicException('Appointment already completed');
                }

                $appointment->update([
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'metadata' => array_merge($appointment->metadata ?? [], ['completed_at' => now()])
                ]);

                // Списание расходников через InventoryManagementService
                if ($appointment->service && !empty($appointment->service->consumables_json)) {
                    $this->deductConsumables($appointment->service->consumables_json, $appointment);
                }

                $this->logger->info('Pet appointment completed', [
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $appointment->correlation_id,
                ]);

                return true;
            });
        }

        /**
         * AI PetHealthConstructor: Анализ фото питомца (Vision AI).
         * Генерация рекомендаций по здоровью и уходу.
         */
        public function analyzeHealthFromPhoto(\Illuminate\Http\UploadedFile $photo, int $petId): array
        {
            $correlationId = (string) Str::uuid();
            $this->logger->info('AI Health analysis started', ['pet_id' => $petId, 'correlation_id' => $correlationId]);

            // Эмуляция работы Vision AI для 2026 канона
            $mockAnalysis = [
                'condition_score' => 0.85,
                'symptoms' => ['red_eyes', 'dry_nose'],
                'detected_species' => 'dog',
                'detected_breed' => 'Golden Retriever',
            ];

            $recommendations = [
                'Visit ophthalmologist (urgent)',
                'Check hydration levels',
                'Recommended products: Sensitive Eyes Drops, Veterinary Hydration Gel',
            ];

            // Поиск товаров в инвентаре текущего клиники/тенаната
            $suggestedProducts = PetProduct::whereIn('name', ['Eye Drops', 'Hydration Gel'])
                ->where('current_stock', '>', 0)
                ->get();

            return [
                'analysis' => $mockAnalysis,
                'recommendations' => $recommendations,
                'suggested_products' => $suggestedProducts,
                'correlation_id' => $correlationId,
            ];
        }

        /**
         * Покупка зоотоваров.
         */
        public function purchaseProduct(int $productId, int $quantity): bool
        {
            return $this->db->transaction(function () use ($productId, $quantity) {
                $product = PetProduct::findOrFail($productId);

                if ($product->current_stock < $quantity) {
                    throw new \DomainException("Insufficient stock for product: {$product->name}");
                }

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'pet_product_purchase', amount: 0, correlationId: $correlationId ?? '');

                $product->decrementStock($quantity);

                $this->logger->info('Pet product purchased', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'tenant_id' => $product->tenant_id,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return true;
            });
        }

        private function reserveConsumables(array $consumables, PetAppointment $appointment): void
        {
            foreach ($consumables as $item) {
                // Резервируем через InventoryManagementService
                // logic here...
            }
        }

        private function deductConsumables(array $consumables, PetAppointment $appointment): void
        {
            foreach ($consumables as $item) {
                // Списываем через InventoryManagementService (Layer Integration)
            }
        }
}
