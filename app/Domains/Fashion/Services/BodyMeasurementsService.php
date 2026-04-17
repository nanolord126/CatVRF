<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use Illuminate\Support\Facades\Log;

final class BodyMeasurementsService
{
    /**
     * Рассчитывает тип фигуры на основе параметров
     */
    public function calculateFigureType(array $measurements): string
    {
        $waist = $measurements['waist'] ?? 0;
        $hips = $measurements['hips'] ?? 0;
        $bust = $measurements['bust'] ?? 0;

        if (!$waist || !$hips || !$bust) {
            return 'unknown';
        }

        $waistToHipRatio = $waist / $hips;
        $bustToWaistRatio = $bust / $waist;

        // Песочные часы
        if ($waistToHipRatio < 0.7 && $bustToWaistRatio > 1.2) {
            return 'hourglass';
        }

        // Прямоугольник
        if ($waistToHipRatio > 0.85 && $bustToWaistRatio < 1.1) {
            return 'rectangle';
        }

        // Груша
        if ($hips > $bust && $hips > $waist) {
            return 'pear';
        }

        // Треугольник
        if ($bust > $hips && $bust > $waist) {
            return 'triangle';
        }

        // Яблоко
        if (abs($bust - $hips) < 5 && abs($waist - $hips) < 5) {
            return 'apple';
        }

        return 'standard';
    }

    /**
     * Рассчитывает размер бюстгальтера
     */
    public function calculateBraSize(array $measurements): string
    {
        $bust = $measurements['bust'] ?? 0;
        $underbust = $measurements['underbust'] ?? 0;

        if (!$bust || !$underbust) {
            return 'unknown';
        }

        $bandSize = round($underbust / 5) * 5;
        $cupSize = $bust - $underbust;

        $cupLetters = ['AA', 'A', 'B', 'C', 'D', 'DD', 'E', 'F', 'G', 'H', 'I'];
        $cupIndex = min(max($cupSize - 10, 0), count($cupLetters) - 1);

        return $bandSize . $cupLetters[$cupIndex];
    }

    /**
     * Рассчитывает размер белья (панталоны)
     */
    public function calculatePantySize(array $measurements): string
    {
        $waist = $measurements['waist'] ?? 0;
        $hips = $measurements['hips'] ?? 0;

        if (!$waist || !$hips) {
            return 'unknown';
        }

        $avgSize = ($waist + $hips) / 2;

        return match (true) {
            $avgSize < 80 => 'XS',
            $avgSize < 90 => 'S',
            $avgSize < 100 => 'M',
            $avgSize < 110 => 'L',
            $avgSize < 120 => 'XL',
            default => 'XXL',
        };
    }

    /**
     * Рассчитывает размер верхней одежды
     */
    public function calculateTopSize(array $measurements): string
    {
        $bust = $measurements['bust'] ?? 0;
        $waist = $measurements['waist'] ?? 0;
        $shoulderWidth = $measurements['shoulder_width'] ?? 0;

        if (!$bust && !$shoulderWidth) {
            return 'unknown';
        }

        $size = $bust + $waist;

        if ($shoulderWidth > 0) {
            $size += $shoulderWidth * 2;
        }

        return match (true) {
            $size < 150 => 'XS',
            $size < 165 => 'S',
            $size < 180 => 'M',
            $size < 195 => 'L',
            $size < 210 => 'XL',
            default => 'XXL',
        };
    }

    /**
     * Рассчитывает размер нижней одежды (брюки, джинсы)
     */
    public function calculateBottomSize(array $measurements): string
    {
        $waist = $measurements['waist'] ?? 0;
        $hips = $measurements['hips'] ?? 0;

        if (!$waist || !$hips) {
            return 'unknown';
        }

        $size = $waist + $hips;

        return match (true) {
            $size < 150 => 'XS',
            $size < 165 => 'S',
            $size < 180 => 'M',
            $size < 195 => 'L',
            $size < 210 => 'XL',
            default => 'XXL',
        };
    }

    /**
     * Рассчитывает размер платья
     */
    public function calculateDressSize(array $measurements): string
    {
        $bust = $measurements['bust'] ?? 0;
        $waist = $measurements['waist'] ?? 0;
        $hips = $measurements['hips'] ?? 0;

        if (!$bust || !$waist || !$hips) {
            return 'unknown';
        }

        $avgSize = ($bust + $waist + $hips) / 3;

        return match (true) {
            $avgSize < 80 => 'XS',
            $avgSize < 90 => 'S',
            $avgSize < 100 => 'M',
            $avgSize < 110 => 'L',
            $avgSize < 120 => 'XL',
            default => 'XXL',
        };
    }

    /**
     * Рассчитывает размер обуви
     */
    public function calculateShoeSize(array $measurements): string
    {
        $footLength = ($measurements['leg_length'] ?? 0) * 0.15;

        if (!$footLength) {
            return 'unknown';
        }

        $euSize = round($footLength * 1.5 + 2);

        return $euSize . ' EU';
    }

    /**
     * Рассчитывает ИМТ
     */
    public function calculateBMI(array $measurements): array
    {
        $height = $measurements['height'] ?? 0;
        $weight = $measurements['weight'] ?? 0;

        if (!$height || !$weight) {
            return [
                'value' => null,
                'status' => 'unknown',
                'category' => 'unknown',
            ];
        }

        $heightInMeters = $height / 100;
        $bmi = $weight / ($heightInMeters * $heightInMeters);

        return match (true) {
            $bmi < 18.5 => [
                'value' => round($bmi, 1),
                'status' => 'underweight',
                'category' => 'Ниже нормы',
            ],
            $bmi < 25 => [
                'value' => round($bmi, 1),
                'status' => 'normal',
                'category' => 'Норма',
            ],
            $bmi < 30 => [
                'value' => round($bmi, 1),
                'status' => 'overweight',
                'category' => 'Избыточный вес',
            ],
            default => [
                'value' => round($bmi, 1),
                'status' => 'obese',
                'category' => 'Ожирение',
            ],
        };
    }

    /**
     * Генерирует стилистические рекомендации на основе типа фигуры
     */
    public function getStyleRecommendations(string $figureType): array
    {
        return match ($figureType) {
            'hourglass' => [
                'icon' => '✨',
                'text' => 'Подчеркните талию поясами и приталенными силуэтами',
            ],
            'hourglass' => [
                'icon' => '👗',
                'text' => 'Платья-футляр и A-силуэт идеально подойдут',
            ],
            'pear' => [
                'icon' => '👔',
                'text' => 'Добавляйте объем верхней части с помощью деталей',
            ],
            'pear' => [
                'icon' => '👖',
                'text' => 'Выбирайте брюки с умеренной посадкой',
            ],
            'triangle' => [
                'icon' => '👚',
                'text' => 'V-образные вырезы визуально уменьшают плечи',
            ],
            'triangle' => [
                'icon' => '👗',
                'text' => 'Платья с расклешенным низом сбалансируют фигуру',
            ],
            'rectangle' => [
                'icon' => '🎨',
                'text' => 'Создавайте иллюзию талии с помощью поясов',
            ],
            'rectangle' => [
                'icon' => '✨',
                'text' => 'Многослойность поможет добавить объем',
            ],
            'apple' => [
                'icon' => '👗',
                'text' => 'Выбирайте платья с завышенной талией',
            ],
            'apple' => [
                'icon' => '👖',
                'text' => 'Прямые брюки и юбки-карандаши подойдут',
            ],
            default => [
                'icon' => '📏',
                'text' => 'Всегда ориентируйтесь на таблицу размеров бренда',
            ],
        };
    }

    /**
     * Рассчитывает score соответствия товара параметрам
     */
    public function calculateFitScore(array $measurements, array $productSpecs): int
    {
        $score = 0;
        $maxScore = 100;

        // Проверка размера
        $recommendedSize = $this->calculateTopSize($measurements);
        if ($recommendedSize === $productSpecs['size'] ?? '') {
            $score += 40;
        } elseif ($this->isAdjacentSize($recommendedSize, $productSpecs['size'] ?? '')) {
            $score += 20;
        }

        // Проверка типа фигуры
        $figureType = $this->calculateFigureType($measurements);
        if (in_array($figureType, $productSpecs['suitable_figure_types'] ?? [])) {
            $score += 30;
        }

        // Проверка материала
        if (in_array($productSpecs['material'] ?? '', $this->getRecommendedMaterials($figureType))) {
            $score += 15;
        }

        // Проверка цвета
        if ($productSpecs['color'] ?? '') {
            $score += 15;
        }

        return min($score, $maxScore);
    }

    /**
     * Получает рекомендации по материалам для типа фигуры
     */
    private function getRecommendedMaterials(string $figureType): array
    {
        return match ($figureType) {
            'hourglass' => ['cotton', 'silk', 'viscose'],
            'pear' => ['cotton', 'linen', 'denim'],
            'triangle' => ['silk', 'chiffon', 'viscose'],
            'rectangle' => ['cotton', 'wool', 'knit'],
            'apple' => ['viscose', 'silk', 'chiffon'],
            default => ['cotton', 'polyester', 'blend'],
        };
    }

    /**
     * Проверяет, являются ли размеры соседними
     */
    private function isAdjacentSize(string $size1, string $size2): bool
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $index1 = array_search($size1, $sizes);
        $index2 = array_search($size2, $sizes);

        if ($index1 === false || $index2 === false) {
            return false;
        }

        return abs($index1 - $index2) === 1;
    }

    /**
     * Валидирует параметры измерений
     */
    public function validateMeasurements(array $measurements): array
    {
        $errors = [];

        if (isset($measurements['height'])) {
            if ($measurements['height'] < 140 || $measurements['height'] > 220) {
                $errors['height'] = 'Рост должен быть от 140 до 220 см';
            }
        }

        if (isset($measurements['weight'])) {
            if ($measurements['weight'] < 35 || $measurements['weight'] > 150) {
                $errors['weight'] = 'Вес должен быть от 35 до 150 кг';
            }
        }

        if (isset($measurements['bust'])) {
            if ($measurements['bust'] < 70 || $measurements['bust'] > 150) {
                $errors['bust'] = 'Обхват груди должен быть от 70 до 150 см';
            }
        }

        if (isset($measurements['waist'])) {
            if ($measurements['waist'] < 50 || $measurements['waist'] > 130) {
                $errors['waist'] = 'Обхват талии должен быть от 50 до 130 см';
            }
        }

        if (isset($measurements['hips'])) {
            if ($measurements['hips'] < 70 || $measurements['hips'] > 160) {
                $errors['hips'] = 'Обхват бедер должен быть от 70 до 160 см';
            }
        }

        return $errors;
    }

    /**
     * Получает полные рекомендации по размерам
     */
    public function getFullSizeRecommendations(array $measurements): array
    {
        return [
            'figure_type' => $this->calculateFigureType($measurements),
            'bra_size' => $this->calculateBraSize($measurements),
            'panty_size' => $this->calculatePantySize($measurements),
            'top_size' => $this->calculateTopSize($measurements),
            'bottom_size' => $this->calculateBottomSize($measurements),
            'dress_size' => $this->calculateDressSize($measurements),
            'shoe_size' => $this->calculateShoeSize($measurements),
            'bmi' => $this->calculateBMI($measurements),
            'style_recommendations' => $this->getStyleRecommendations(
                $this->calculateFigureType($measurements)
            ),
            'proportions' => [
                'waist_to_hip' => $measurements['waist'] && $measurements['hips'] 
                    ? round($measurements['waist'] / $measurements['hips'], 2) 
                    : null,
                'bust_to_waist' => $measurements['bust'] && $measurements['waist']
                    ? round($measurements['bust'] / $measurements['waist'], 2)
                    : null,
            ],
        ];
    }
}
