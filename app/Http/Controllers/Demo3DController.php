<?php declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class Demo3DController extends Controller
{
    public function index(): View
    {
        $demoProducts = [
            [
                'id' => 1,
                'name' => 'Diamond Ring - 2ct',
                'vertical' => 'Jewelry',
                'model_path' => '/storage/3d-models/Jewelry/diamond-ring.glb',
                'price' => 45000,
                'description' => 'Шикарное кольцо с бриллиантом 2 карата. 360° просмотр и AR примерка.',
                'tags' => ['jewelry', 'ring', 'diamond', '3d'],
            ],
            [
                'id' => 2,
                'name' => 'Gold Necklace',
                'vertical' => 'Jewelry',
                'model_path' => '/storage/3d-models/Jewelry/gold-necklace.glb',
                'price' => 28000,
                'description' => 'Золотое ожерелье с изумительной обработкой. Полный 3D просмотр.',
                'tags' => ['jewelry', 'necklace', 'gold', '3d'],
            ],
            [
                'id' => 3,
                'name' => 'Апартамент 1-комнатный',
                'vertical' => 'Hotels/RealEstate',
                'model_path' => '/storage/3d-models/Hotels/apartment-001.glb',
                'price' => 15000000,
                'description' => 'Современный апартамент в центре города. 3D тур по всем комнатам.',
                'tags' => ['apartment', 'realEstate', '3d-tour', 'interior'],
            ],
            [
                'id' => 4,
                'name' => 'Suite Room - 5*',
                'vertical' => 'Hotels',
                'model_path' => '/storage/3d-models/Hotels/suite-room.glb',
                'price' => 35000,
                'description' => 'Люкс номер в 5-звёздочном отеле. Полный 3D просмотр с AR.',
                'tags' => ['hotel', 'suite', 'room', '3d-tour'],
            ],
            [
                'id' => 5,
                'name' => 'Modern Sofa',
                'vertical' => 'Furniture',
                'model_path' => '/storage/3d-models/Furniture/sofa.glb',
                'price' => 89000,
                'description' => 'Стильный диван в вашу комнату. AR примерка в вашем интерьере.',
                'tags' => ['furniture', 'sofa', 'ar-placement', '3d'],
            ],
            [
                'id' => 6,
                'name' => 'Designer Chair',
                'vertical' => 'Furniture',
                'model_path' => '/storage/3d-models/Furniture/chair.glb',
                'price' => 34000,
                'description' => 'Дизайнерский стул от известного архитектора. 3D просмотр + AR.',
                'tags' => ['furniture', 'chair', '3d', 'design'],
            ],
        ];
        return view('3d-demo', [
            'products' => $demoProducts,
            'title' => 'CatVRF 3D Visualization Demo',
        ]);
    }
    public function product(int $id): View
    {
        $product = match ($id) {
            1 => ['name' => 'Diamond Ring', 'model' => '/storage/3d-models/Jewelry/diamond-ring.glb'],
            2 => ['name' => 'Gold Necklace', 'model' => '/storage/3d-models/Jewelry/gold-necklace.glb'],
            3 => ['name' => 'Apartment', 'model' => '/storage/3d-models/Hotels/apartment-001.glb'],
            4 => ['name' => 'Suite Room', 'model' => '/storage/3d-models/Hotels/suite-room.glb'],
            5 => ['name' => 'Sofa', 'model' => '/storage/3d-models/Furniture/sofa.glb'],
            6 => ['name' => 'Chair', 'model' => '/storage/3d-models/Furniture/chair.glb'],
            default => ['name' => 'Unknown', 'model' => ''],
        };
        return view('3d-product-detail', $product);
    }
}
