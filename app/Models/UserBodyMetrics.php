<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserBodyMetrics extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use BelongsToTenant;

        protected $table = 'user_body_metrics';

        protected $fillable = [
            'user_id',
            'tenant_id',
            'clothing_size_top',
            'clothing_size_bottom',
            'clothing_size_dress',
            'shoe_size_eu',
            'shoe_size_us',
            'shoe_size_confidence',
            'height_cm',
            'weight_kg',
            'body_shape',
            'skin_tone',
            'hair_color',
            'eye_color',
            'last_updated_at',
        ];

        protected $casts = [
            'height_cm' => 'integer',
            'weight_kg' => 'decimal:2',
            'shoe_size_confidence' => 'decimal:2',
            'last_updated_at' => 'datetime',
        ];

        // ========== ОТНОШЕНИЯ ==========

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function tenant(): BelongsTo
        {
            return $this->belongsTo(Tenant::class);
        }

        // ========== HELPER METHODS ==========

        /**
         * Получить BMI (индекс массы тела)
         */
        public function getBMI(): ?float
        {
            if (!$this->height_cm || !$this->weight_kg) {
                return null;
            }

            $heightM = $this->height_cm / 100;
            return $this->weight_kg / ($heightM ** 2);
        }

        /**
         * Получить категорию BMI
         */
        public function getBMICategory(): ?string
        {
            $bmi = $this->getBMI();

            if (!$bmi) {
                return null;
            }

            return match (true) {
                $bmi < 18.5 => 'underweight',
                $bmi < 25 => 'normal',
                $bmi < 30 => 'overweight',
                default => 'obese',
            };
        }

        /**
         * Полный размерный профиль
         */
        public function getSizeProfile(): array
        {
            return [
                'clothing' => [
                    'top' => $this->clothing_size_top,
                    'bottom' => $this->clothing_size_bottom,
                    'dress' => $this->clothing_size_dress,
                ],
                'shoes' => [
                    'eu' => $this->shoe_size_eu,
                    'us' => $this->shoe_size_us,
                    'confidence' => $this->shoe_size_confidence,
                ],
            ];
        }
}
