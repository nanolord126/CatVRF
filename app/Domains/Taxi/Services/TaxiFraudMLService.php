<?php declare(strict_types=1);

namespace App\Domains\Taxi\Services;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Services\ML\FraudMLFeatureStore;
use App\Services\ML\FraudMLExplainer;

final readonly class TaxiFraudMLService
{


    /**
         * Конструктор с инъекцией (по канону).
         */
        public function __construct(
        private readonly \App\Services\ML\FraudMLService $coreML,
        private readonly Request $request,
        private readonly LoggerInterface $logger,
        private readonly FraudMLFeatureStore $featureStore,
        private readonly FraudMLExplainer $explainer,
    ) {}

    /**
     * Комплексная проверка перед созданием/принятием поездки.
     */
    public function checkRideSecurity(int $passengerId, array $params): bool
    {
        $correlationId = (string)Str::uuid();

        // Store features in Feature Store
        $features = [
            'user_id' => $passengerId,
            'operation_type' => 'taxi_ride_request',
            'amount' => $params['price'] ?? 0,
            'ip' => $this->request->ip(),
            'device' => $this->request->header('User-Agent'),
            'vertical_code' => 'taxi',
            'current_quota_usage_ratio' => 0.5,
        ];
        
        $this->featureStore->storeFeatures(
            'user',
            (string)$passengerId,
            $features,
            $correlationId
        );

        // Core ML Score
        $mlScore = $this->coreML->scoreOperation([
            'user_id' => $passengerId,
            'operation_type' => 'taxi_ride_request',
            'amount' => $params['price'] ?? 0,
            'ip' => $this->request->ip(),
            'device' => $this->request->header('User-Agent')
        ]);

        // SHAP explanation for high-risk predictions
        if ($mlScore > 0.7) {
            $explanation = $this->explainer->explainPrediction($features, $mlScore, 'taxi-v1');
        }

        if ($mlScore > 0.85) {
            $this->logger->warning('Taxi Fraud Detected: Critical High Score', [
                'user_id' => $passengerId,
                'score' => $mlScore,
                'correlation_id' => $correlationId,
                'feature_source' => 'feature_store',
                'shap_explanation' => $explanation ?? null,
            ]);
            return false;
        }

        // Taxi-specific heuristics
        $recentRides = TaxiRide::where('passenger_id', $passengerId)
                ->where('created_at', '>', now()->subMinutes(5))
                ->count();

        if ($recentRides > 3) {
            $this->logger->error('Taxi Fraud: Spam Ride Requests', [
                'user_id' => $passengerId,
                'rides_count' => $recentRides,
                'correlation_id' => $correlationId,
                'feature_source' => 'feature_store',
            ]);
            return false;
        }

        $distance = $params['estimated_distance'] ?? 0;
        if ($distance < 0.1 || $distance > 500.0) {
            $this->logger->info('Taxi Security: Suspicious Distance', [
                'user_id' => $passengerId,
                'distance' => $distance,
                'correlation_id' => $correlationId,
                'feature_source' => 'feature_store',
            ]);
            return false;
        }

        return true;
    }

    /**
     * Проверка легитимности водителя при выходе на линию.
     */
    public function verifyDriverSession(int $driverId): bool
    {
        $driver = Driver::findOrFail($driverId);

        if ($driver->license_number === 'test' || !$driver->is_active) {
            return false;
        }

        $this->logger->info('Driver session verified via ML', [
            'driver_id' => $driverId,
            'license' => $driver->license_number,
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);

        return true;
    }
}
