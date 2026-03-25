declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Services\ThreeD;

use Illuminate\Support\Str;

final /**
 * ClothingARService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ClothingARService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function generateClothingARModel(array $clothingData): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'product_id' => $clothingData['product_id'],
            'type' => $clothingData['type'] ?? 'shirt', // shirt, pants, dress, shoes
            'size_variants' => $this->generateSizeVariants($clothingData),
            'color_variants' => $clothingData['colors'] ?? ['black', 'white', 'blue'],
            'ar_model_url' => $this->getARModelPath($clothingData),
            'try_on_enabled' => true,
            'body_type_recommendations' => ['slim', 'regular', 'plus'],
        ];
    }

    public function generateSizeVariants(array $clothingData): array
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        return collect($sizes)
            ->map(fn ($size) => [
                'size' => $size,
                'scale' => match ($size) {
                    'XS' => 0.85,
                    'S' => 0.90,
                    'M' => 1.0,
                    'L' => 1.05,
                    'XL' => 1.10,
                    'XXL' => 1.15,
                    default => 1.0,
                },
            ])
            ->all();
    }

    private function getARModelPath(array $clothingData): string
    {
        return "/3d-models/clothing/{$clothingData['sku']}.gltf";
    }
}

