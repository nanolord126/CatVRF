<?php declare(strict_types=1);

namespace Database\Factories;

use App\Models\FraudModelVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

final class FraudModelVersionFactory extends Factory
{
    protected $model = FraudModelVersion::class;

    public function definition(): array
    {
        $version = now()->format('Y-m-d') . '-v' . rand(1, 10);
        
        return [
            'version' => $version,
            'model_type' => $this->faker->randomElement(['lightgbm', 'xgboost']),
            'trained_at' => now(),
            'shadow_started_at' => null,
            'promoted_at' => null,
            'is_shadow' => false,
            'is_active' => false,
            'is_rollback_candidate' => false,
            'accuracy' => $this->faker->randomFloat(4, 0.85, 0.95),
            'precision' => $this->faker->randomFloat(4, 0.85, 0.95),
            'recall' => $this->faker->randomFloat(4, 0.80, 0.92),
            'f1_score' => $this->faker->randomFloat(4, 0.82, 0.93),
            'auc_roc' => $this->faker->randomFloat(4, 0.90, 0.96),
            'shadow_auc_roc' => null,
            'shadow_predictions_count' => 0,
            'shadow_drift_score' => null,
            'file_path' => "storage/models/fraud/{$version}.joblib",
            'file_hash' => $this->faker->sha256,
            'is_encrypted' => false,
            'feature_importance' => [
                'amount_log' => $this->faker->randomFloat(4, 0.1, 0.3),
                'hour_of_day' => $this->faker->randomFloat(4, 0.05, 0.15),
            ],
            'training_metadata' => [
                'dataset_size' => $this->faker->numberBetween(1000, 10000),
                'training_time_seconds' => $this->faker->numberBetween(300, 1800),
            ],
            'comment' => $this->faker->sentence,
            'trained_by' => 'system',
            'correlation_id' => $this->faker->uuid,
        ];
    }

    public function shadow(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_shadow' => true,
            'is_active' => false,
            'shadow_started_at' => now()->subHours(rand(25, 48)),
            'shadow_auc_roc' => $this->faker->randomFloat(4, 0.90, 0.96),
            'shadow_predictions_count' => $this->faker->numberBetween(100, 5000),
            'shadow_drift_score' => $this->faker->numberBetween(0, 20),
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_shadow' => false,
            'is_active' => true,
            'promoted_at' => now()->subHours(rand(1, 24)),
        ]);
    }

    public function rollbackCandidate(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_shadow' => false,
            'is_active' => false,
            'is_rollback_candidate' => true,
            'promoted_at' => now()->subDays(rand(1, 7)),
        ]);
    }
}
