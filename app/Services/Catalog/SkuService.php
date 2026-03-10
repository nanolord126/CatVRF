<?php

namespace App\Services\Catalog;

use Illuminate\Support\Str;

class SkuService
{
    /**
     * Generate a unique SKU for a product based on its hierarchy.
     * Format: VERTICAL-CATEGORY-BRAND-RANDOM_HEX
     */
    public static function generate(string $vertical, string $categoryName, string $brandName): string
    {
        $v = strtoupper(substr($vertical, 0, 3));
        $c = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $categoryName), 0, 3));
        $b = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $brandName), 0, 3));
        $r = strtoupper(Str::random(6));

        return sprintf("%s-%s-%s-%s", $v, $c, $b, $r);
    }
}
