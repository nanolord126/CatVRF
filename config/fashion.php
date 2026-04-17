<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Fashion Photo Quality Criteria
    |--------------------------------------------------------------------------
    |
    | Критерии качества фото для Fashion вертикали.
    | Определяют минимальные и рекомендуемые параметры изображений.
    |
    */

    'photo_quality' => [
        // Минимальное разрешение (ширина x высота в пикселях)
        'min_resolution' => [
            'width' => 800,
            'height' => 800,
        ],

        // Рекомендуемое разрешение
        'recommended_resolution' => [
            'width' => 1200,
            'height' => 1200,
        ],

        // Оптимальное разрешение для максимального качества
        'optimal_resolution' => [
            'width' => 1500,
            'height' => 1500,
        ],

        // Допустимые соотношения сторон
        'aspect_ratios' => [
            'ideal' => 1.0, // Квадрат 1:1
            'acceptable_min' => 0.8, // 4:5
            'acceptable_max' => 1.2, // 5:4
            'min' => 0.6, // 2:3
            'max' => 1.5, // 3:2
        ],

        // Размер файла в килобайтах
        'file_size' => [
            'min' => 50, // KB
            'recommended_min' => 100,
            'recommended_max' => 500,
            'max' => 2048, // 2MB
            'hard_max' => 10240, // 10MB
        ],

        // Поддерживаемые форматы с приоритетами
        'formats' => [
            'webp' => [
                'priority' => 'excellent',
                'description' => 'Современный формат с хорошей компрессией',
            ],
            'jpeg' => [
                'priority' => 'good',
                'description' => 'Стандартный формат для фото',
            ],
            'jpg' => [
                'priority' => 'good',
                'description' => 'Стандартный формат для фото',
            ],
            'png' => [
                'priority' => 'acceptable',
                'description' => 'Формат без потерь, но большой размер',
            ],
        ],

        // Запрещенные форматы
        'forbidden_formats' => ['bmp', 'tiff', 'gif', 'ico'],

        // Весовые коэффициенты для расчета общего качества (сумма = 100)
        'quality_weights' => [
            'resolution' => 35, // Разрешение
            'aspect_ratio' => 30, // Пропорции
            'file_size' => 20, // Размер файла
            'format' => 15, // Формат
        ],

        // Пороговые значения для уровней качества
        'quality_thresholds' => [
            'excellent' => 80,
            'good' => 60,
            'acceptable' => 40,
        ],

        // Специальные критерии для разных категорий Fashion
        'category_specific' => [
            'clothing' => [
                'min_resolution' => ['width' => 800, 'height' => 800],
                'recommended_resolution' => ['width' => 1200, 'height' => 1200],
                'aspect_ratio' => ['ideal' => 1.0, 'min' => 0.7, 'max' => 1.3],
            ],
            'shoes' => [
                'min_resolution' => ['width' => 600, 'height' => 600],
                'recommended_resolution' => ['width' => 1000, 'height' => 1000],
                'aspect_ratio' => ['ideal' => 1.0, 'min' => 0.6, 'max' => 1.5],
            ],
            'accessories' => [
                'min_resolution' => ['width' => 500, 'height' => 500],
                'recommended_resolution' => ['width' => 800, 'height' => 800],
                'aspect_ratio' => ['ideal' => 1.0, 'min' => 0.5, 'max' => 2.0],
            ],
            'underwear' => [
                'min_resolution' => ['width' => 600, 'height' => 600],
                'recommended_resolution' => ['width' => 1000, 'height' => 1000],
                'aspect_ratio' => ['ideal' => 0.75, 'min' => 0.5, 'max' => 1.5],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fashion Product Card Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки отображения карточек товаров Fashion вертикали.
    |
    */

    'product_card' => [
        // Показывать качество фото
        'show_quality_badge' => true,

        // Показывать скидки
        'show_discount' => true,

        // Показывать наличие на складе
        'show_stock' => true,

        // Показывать бренд
        'show_brand' => true,

        // Количество изображений в галерее
        'max_gallery_images' => 5,

        // Размеры изображений
        'image_sizes' => [
            'thumbnail' => ['width' => 80, 'height' => 80],
            'card' => ['width' => 400, 'height' => 400],
            'detail' => ['width' => 800, 'height' => 800],
            'zoom' => ['width' => 1500, 'height' => 1500],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fashion Validation Messages
    |--------------------------------------------------------------------------
    |
    | Сообщения для валидации качества фото.
    |
    */

    'validation_messages' => [
        'resolution_too_low' => 'Разрешение слишком низкое. Минимум :min_width x :min_height px',
        'resolution_good' => 'Разрешение соответствует стандартам',
        'resolution_excellent' => 'Отличное разрешение для детализации',

        'aspect_ratio_poor' => 'Пропорции изображения требуют улучшения',
        'aspect_ratio_good' => 'Хорошие пропорции изображения',
        'aspect_ratio_excellent' => 'Идеальные пропорции (квадрат)',

        'file_size_too_large' => 'Размер файла слишком большой. Максимум :max KB',
        'file_size_too_small' => 'Файл слишком маленький, возможна потеря качества',
        'file_size_optimal' => 'Оптимальный размер файла',

        'format_not_supported' => 'Формат :format не поддерживается. Используйте WEBP, JPG или PNG',
        'format_recommended' => 'Рекомендуется использовать WEBP формат',
    ],
];
