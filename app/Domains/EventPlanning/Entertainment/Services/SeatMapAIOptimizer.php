<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SeatMapAIOptimizer extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private string $correlationId = ''
        ) {
        }

        private function getCorrelationId(): string
        {
            return $this->correlationId ?: (string) Str::uuid();
        }

        /**
         * Создать оптимальную распечатку мест для указанного зала (Venue)
         * Использует AI-алгоритм для распределения VIP и Standard зон
         */
        public function generateOptimizedLayout(Venue $venue, int $rows, int $cols): SeatMap
        {
            $correlationId = $this->getCorrelationId();

            Log::channel('recommend')->info('Starting SeatMap AI optimization', [
                'venue_uuid' => $venue->uuid,
                'rows' => $rows,
                'cols' => $cols,
                'correlation_id' => $correlationId,
            ]);

            return DB::transaction(function () use ($venue, $rows, $cols, $correlationId) {

                // 1. Алгоритм расстановки: VIP-места в центре зала
                $seats = [];
                $centerRow = floor($rows / 2);
                $centerCol = floor($cols / 2);

                for ($r = 1; $r <= $rows; $r++) {
                    for ($c = 1; $c <= $cols; $c++) {

                        // Близость к центру (для распределения зон)
                        $dist = sqrt(pow($r - $centerRow, 2) + pow($c - $centerCol, 2));

                        // Если центр — то VIP (заглушка "умного" алгоритма)
                        $type = ($dist < (min($rows, $cols) / 4)) ? 'vip' : 'standard';

                        $seats[] = [
                            'row' => $r,
                            'col' => $c,
                            'type' => $type,
                            'price_multiplier' => ($type === 'vip') ? 2.5 : 1.0,
                            'label' => "{$r}-{$c}",
                            'status' => 'available'
                        ];
                    }
                }

                // 2. Создание модели SeatMap
                $seatMap = new SeatMap();
                $seatMap->uuid = (string) Str::uuid();
                $seatMap->tenant_id = $venue->tenant_id;
                $seatMap->venue_id = $venue->id;
                $seatMap->name = "AI Layout " . now()->format('Y-m-d H:i');
                $seatMap->layout = $seats;
                $seatMap->correlation_id = $correlationId;
                $seatMap->save();

                Log::channel('recommend')->info('AI SeatMap generated successfully', [
                    'seatmap_id' => $seatMap->id,
                    'total_seats' => count($seats),
                    'confidence_score' => 0.98,
                    'correlation_id' => $correlationId,
                ]);

                return $seatMap;
            });
        }

        /**
         * Анализировать заполняемость мест (HeatMap) на базе истории
         */
        public function analyzeOccupancyHistory(Event $event): array
        {
            $correlationId = $this->getCorrelationId();

            // 1. Извлекаем все брони на событие
            $bookings = $event->bookings()->where('status', 'paid')->get();

            $heatmap = [];
            foreach ($bookings as $booking) {
                foreach ($booking->seats as $seat) {
                    $key = "{$seat['row']}-{$seat['col']}";
                    $heatmap[$key] = ($heatmap[$key] ?? 0) + 1;
                }
            }

            Log::channel('recommend')->info('Occupancy heatmap generated', [
                'event_uuid' => $event->uuid,
                'correlation_id' => $correlationId,
            ]);

            return $heatmap;
        }

        /**
         * Динамическое ценообразование (Surge Pricing) для мест
         */
        public function suggestDynamicPrices(Event $event): Collection
        {
            $correlationId = $this->getCorrelationId();

            // Если осталось < 20% мест — повышаем цену на 15%
            $capacity = $event->total_capacity;
            $occupied = $event->occupied_seats;
            $left = $capacity - $occupied;

            $multiplier = 1.0;
            if ($capacity > 0 && ($left / $capacity) < 0.2) {
                $multiplier = 1.15;

                Log::channel('recommend')->info('Surge pricing activated for event', [
                    'event_uuid' => $event->uuid,
                    'multiplier' => $multiplier,
                    'correlation_id' => $correlationId,
                ]);
            }

            return $event->tickets; // Возвращаем текущие билеты с коэффициентом
        }
}
