<?php

declare(strict_types=1);

namespace App\Domains\SportsNutrition\DTOs;

final readonly class SportsNutritionProductDto
{


    public function __construct(
            public int $store_id,
            public int $category_id,
            public string $name,
            public string $sku,
            public string $brand,
            public int $price_b2c, // in kopecks
            public int $price_b2b, // in kopecks
            public int $stock_quantity,
            public string $form_factor, // powder, caps
            public int $servings_count,
            public array $nutrition_facts, // [protein => 25, carbs => 2]
            public array $allergens, // ['milk', 'soy']
            public string $expiry_date, // 'YYYY-MM-DD'
            private bool $is_vegan = false,
            private bool $is_gmo_free = true,
            private bool $is_published = false,
            private array $tags = []
        ) {}

        public static function fromArray(array $data): self
        {
            return new self(
                store_id: (int) $data['store_id'],
                category_id: (int) $data['category_id'],
                name: (string) $data['name'],
                sku: (string) $data['sku'],
                brand: (string) $data['brand'],
                price_b2c: (int) $data['price_b2c'],
                price_b2b: (int) $data['price_b2b'],
                stock_quantity: (int) ($data['stock_quantity'] ?? 0),
                form_factor: (string) $data['form_factor'],
                servings_count: (int) ($data['servings_count'] ?? 1),
                nutrition_facts: (array) ($data['nutrition_facts'] ?? []),
                allergens: (array) ($data['allergens'] ?? []),
                expiry_date: (string) $data['expiry_date'],
                is_vegan: (bool) ($data['is_vegan'] ?? false),
                is_gmo_free: (bool) ($data['is_gmo_free'] ?? true),
                is_published: (bool) ($data['is_published'] ?? false),
                tags: (array) ($data['tags'] ?? [])
            );
        }
    }
