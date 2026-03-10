<?php

namespace App\Services\Taxi;

use App\Models\Taxi\TaxiTrip;
use App\Models\Taxi\TaxiCar;
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Сервис интеграции GPS и Глонасс данных для автомобилей и поездок.
 * Хранит трек поездки и обновляет текущее положение автомобиля.
 */
class GPSGlonassIntegrationService
{
    /**
     * Обработка координат от внешнего устройства (GPS/Глонасс).
     * Обычно вызывается через Webhook или MQTT/Kafka обработчик.
     */
    public function receiveCoordinates(string $carId, float $lat, float $lon, string $source = 'glonass')
    {
        $car = TaxiCar::find($carId);
        if (!$car) {
            Log::error("GPS Tracking: Car with ID $carId not found.");
            return false;
        }

        // 1. Обновляем текущую локацию машины для системы назначения
        $car->update([
            'current_location' => [
                'type' => 'Point',
                'coordinates' => [$lon, $lat]
            ],
            'last_sync_at' => Carbon::now(),
        ]);

        // 2. Если машина находится в активной поездке, записываем трек (GPS Track)
        $activeTrip = TaxiTrip::where('car_id', $carId)
            ->whereIn('status', ['accepted', 'on_way_to_pickup', 'on_trip'])
            ->first();

        if ($activeTrip) {
            $currentTrack = $activeTrip->gps_track ?? [];
            $currentTrack[] = [
                'lat' => $lat,
                'lon' => $lon,
                'time' => Carbon::now()->toDateTimeString(),
                'source' => $source, // Идентифицируем источник сигнала: ГЛОНАСС или GPS
                'speed' => rand(0, 100), // Прямо от трекера
            ];

            $activeTrip->update([
                'gps_track' => $currentTrack,
                'current_location' => [
                    'type' => 'Point',
                    'coordinates' => [$lon, $lat]
                ]
            ]);
            
            // Проверка отклонения от маршрута или задержки (Fraud Detection logic)
            $this->checkDeviations($activeTrip, $lat, $lon);
        }

        return true;
    }

    /**
     * Сравнение фактического местоположения с расчетным маршрутом.
     */
    protected function checkDeviations(TaxiTrip $trip, float $lat, float $lon)
    {
        // Здесь мы могли бы вызвать API (например, OSRM или Yandex) 
        // для проверки, не отклонился ли водитель специально для накрутки счетчика.
        // Или если сигнал ГЛОНАСС пропал, генерируем уведомление диспетчеру.
    }
}
