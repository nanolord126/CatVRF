<?php

namespace App\Services\Ecosystem;

use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Str;

class SkuGeneratorService
{
    public function generate(string $vertical, ?int $categoryId = null, ?int $brandId = null): string
    {
        $cat = $categoryId ? Category::find($categoryId)?->slug ?? 'UNC' : 'UNC';
        $brand = $brandId ? Brand::find($brandId)?->slug ?? 'GEN' : 'GEN';
        
        $prefix = strtoupper(substr($vertical, 0, 3));
        $catPrefix = strtoupper(substr($cat, 0, 3));
        $brandPrefix = strtoupper(substr($brand, 0, 3));
        
        return sprintf("%s-%s-%s-%s", $prefix, $catPrefix, $brandPrefix, strtoupper(Str::random(6)));
    }
}
