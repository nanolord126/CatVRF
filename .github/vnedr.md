### 1. Цель
Реализовать полноценную виртуальную примерку для вертикалей Fashion (Одежда) и Footwear (Обувь), которая:

Позволяет покупателю увидеть товар на себе в реальном времени.
Учитывает реальные размеры, тип фигуры, цвет кожи, освещение.
Интегрируется с системой размеров, аллергиями на материалы и ML-персонализацией.
Работает как на веб, так и в мобильном приложении.
Даёт значительное снижение возвратов (цель — -25–40 % по категориям).

2. Архитектура решения
Технологический стек (2026):

Frontend: AR.js + Model Viewer (WebXR) + custom Three.js слой
AI-модель примерки: Self-hosted TryOnDiffusion / Stable Diffusion Inpainting + IP-Adapter (для точности)
Body Detection: MediaPipe + MoveNet / BlazePose
3D-модели товаров: GLB/GLTF (автоматическая конвертация из 2D-фото)
Хранение: CDN + S3 (tenant-isolated)

Два режима работы:

Быстрый 2D-режим (рекомендуется по умолчанию) — наложение на фото/видео пользователя.
AR-режим (WebXR) — примерка в реальном времени через камеру.

3. Основные сущности
Модель VirtualTryOnAsset
PHPclass VirtualTryOnAsset extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'type', // 'clothing_top', 'clothing_bottom', 'full_body', 'shoes'
        'model_3d_url',           // GLB
        'segmentation_mask',
        'category_mask',          // для inpainting
        'body_type_compatibility' // slim, regular, plus-size и т.д.
    ];
}
Модель UserBodyProfile (анонимизированные данные)

Рост, обхваты (грудь, талия, бёдра), тип фигуры, цвет кожи
Фото в полный рост (опционально, с согласия)

4. Интеграция в пользовательский путь
На странице товара:

Кнопка «Примерить виртуально»
Выбор варианта (цвет/размер) → мгновенная примерка
Переключение между 2D и AR
Сохранение фото примерки в личный кабинет

В корзине и оформлении заказа:

Показ миниатюр примерок выбранных товаров
Предупреждение, если размер выбран неверно (на основе истории)

В личном кабинете:

«Мои примерки» — галерея сохранённых фото
Рекомендации «Похоже на то, что вы примеряли»

5. Техническая реализация (ключевые компоненты)
Сервис: VirtualTryOnService.php
PHPclass VirtualTryOnService
{
    public function generateTryOn(User $user, ProductVariant $variant): TryOnResult
    {
        $bodyProfile = $user->bodyProfile;
        
        // Проверка совместимости (аллергии, размер)
        $this->contraindicationService->checkCompatibility($variant, $user);
        
        // Генерация через ML-модель
        $result = $this->tryOnModel->generate([
            'user_body' => $bodyProfile->getProcessedImage(),
            'garment'   => $variant->getTryOnAsset(),
            'pose'      => 'default'
        ]);

        return new TryOnResult($result->image_url, $result->confidence);
    }
}
Livewire компонент: VirtualTryOnWidget

Камера / загрузка фото
Выбор товара
Реал-тайм предпросмотр
Кнопка «Сохранить фото примерки»

6. Что должен вернуть ИИ

Полный код для вертикалей Fashion и Footwear:
VirtualTryOnService.php
Модели VirtualTryOnAsset и UserBodyProfile
Livewire компонент VirtualTryOnWidget
Filament-расширения для загрузки 3D-моделей товаров

Интеграция с:
Системой размеров
Аллергиями на материалы
ML-персонализацией
CDN (оптимизация 3D и изображений примерок)

Тесты Pest (симуляция примерки, проверка аллергий, производительность)
README с инструкцией по загрузке 3D-моделей и настройке ML-модели.

Критерии приёмки (я проверяю лично):

Виртуальная примерка работает быстро и с приемлемым качеством
Учитываются размеры, тип фигуры и аллергии на материалы
Работает как для одежды, так и для обуви
Полная интеграция с существующими доменами
Безопасность и согласие пользователя на обработку фото

### 1. Цель
Реализовать полноценную 3D-виртуальную примерку на базе реальных 3D-моделей товаров.
Пользователь должен видеть одежду и обувь в 3D на своей 3D-аватарке (или в AR через камеру) с возможностью вращения, смены цвета/размера и освещения.
2. Архитектура 3D-примерки
Технологический стек:

3D-движок: Three.js + React Three Fiber (для веб) + Model Viewer (WebXR для AR)
Формат моделей: GLB/GLTF 2.0 (с PBR-материалами)
Генерация 3D-моделей:
Автоматическая конвертация из высококачественных 2D-фото (TripoSR / Luma AI / Meshy)
Ручная/студийная подготовка для топовых брендов

Body Avatar: Ready Player Me или custom rigged model (с Blendshapes для разных типов фигуры)

Два режима:

3D Web-режим (рекомендуемый по умолчанию) — 3D-аватарка пользователя + 3D-модель товара
AR-режим — примерка через камеру телефона (WebXR)

3. Модели и хранение
Модель Product3DAsset
PHPclass Product3DAsset extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'file_url',           // GLB на CDN
        'preview_image',
        'low_poly_url',       // для быстрой загрузки
        'material_maps',      // albedo, normal, roughness, metallic
        'rigged',             // bool — адаптировано под аватар
        'body_type_compatibility', // slim, regular, plus, athletic
        'polygon_count'
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class); }
}
Хранение:

Raw GLB → S3 (tenant-isolated)
Оптимизированные версии → Bunny.net / Cloudflare Stream (CDN)
Автоматическая оптимизация (Draco compression + KTX2 textures)

4. Основной сервис
VirtualTryOn3DService.php
PHPclass VirtualTryOn3DService
{
    public function getTryOnScene(User $user, ProductVariant $variant): array
    {
        $avatar = $user->get3DAvatar();           // Ready Player Me URL или custom
        $asset = $variant->threeDAsset;

        return [
            'avatar_url' => $avatar,
            'garment_glb' => $asset->file_url,
            'garment_low_poly' => $asset->low_poly_url,
            'compatible' => $this->checkCompatibility($user, $variant),
            'recommended_size' => $this->sizeService->recommendSize($user, $variant),
            'allergy_warnings' => $this->contraindicationService->getWarnings($variant, $user)
        ];
    }

    public function generateFromPhotos(Product $product, array $photos): Product3DAsset
    {
        // Запуск генерации через TripoSR / Meshy API или self-hosted модель
        $result = $this->modelGenerator->createFromImages($photos);
        
        return $this->save3DAsset($product, $result);
    }
}
5. Интеграция в интерфейс
Livewire компонент: VirtualTryOn3D

Загрузка 3D-сцены (Three.js)
Выбор варианта (цвет/размер)
Управление освещением и позой
Кнопки «Сохранить фото», «Поделиться», «Добавить в корзину»

На странице товара:

Вкладка «3D Примерка» (основная)
Кнопка «AR Примерка» (для мобильных)

В личном кабинете:

«Мои 3D-примерки» — галерея сохранённых сцен

6. Автоматизация и пайплайн

При загрузке нового товара продавцом:
Автоматический запуск генерации 3D-модели (если есть 8+ качественных фото)
Очередь Generate3DModelJob

После генерации — оптимизация (Draco + KTX2) и заливка на CDN

7. Что должен вернуть ИИ

Полный код:
Product3DAsset модель + миграция
VirtualTryOn3DService.php
Livewire компонент VirtualTryOn3D (Three.js интеграция)
Generate3DModelJob

Интеграция с:
Системой размеров
Аллергиями на материалы
ML-персонализацией
CDN (Bunny/Cloudflare)

Тесты Pest — загрузка 3D, рендер сцены, проверка совместимости
Рекомендации по подготовке 3D-моделей и настройке генерации.

Критерии приёмки (я проверяю лично):

3D-примерка работает плавно (60 fps на средних устройствах)
Качество моделей высокое (PBR-материалы)
Учитываются размеры, тип фигуры и аллергии
Автоматическая генерация 3D из фото работает
Полная интеграция с вертикалями Одежда и Обувь

### 1. Цель
Реализовать полностью автоматический пайплайн генерации 3D-моделей товаров из набора 2D-фотографий, чтобы продавцы могли загружать обычные фото (8–12 ракурсов), а система сама создавала качественную GLB-модель для виртуальной примерки.
Это критически важно для масштабирования вертикалей Одежда и Обувь.
2. Требования к входным данным
Минимальный набор:

8–12 фото в высоком разрешении (минимум 2048px)
Ракурсы: front, back, left, right, 3/4 left, 3/4 right, top, bottom + детали (ткань, фурнитура)
Чистый фон (или с возможностью автоматического удаления)
Для обуви — обязательно фото подошвы и вида сверху

3. Пайплайн генерации 3D (2026 стек)
Основной движок:

TripoSR (быстрый) + Luma AI Dream Machine / Meshy.ai (высокое качество) как fallback
Self-hosted вариант: InstantMesh + Zero123++ + fine-tuning на fashion-датасете

Этапы обработки:

Background Removal — Remove.bg + Segment Anything 2 (SAM2)
Multi-view Reconstruction — TripoSR / InstantMesh
Texture Generation — IP-Adapter + ControlNet
PBR Materials — автоматическое создание albedo, normal, roughness, metallic карт
Optimization — Draco compression + KTX2 textures + LOD
Rigging — автоматическая адаптация под Ready Player Me аватарки

4. Основной сервис
Generate3DFromPhotosService.php
PHPclass Generate3DFromPhotosService
{
    public function generateFromPhotos(Product $product, array $photos, string $type = 'clothing'): Product3DAsset
    {
        // 1. Валидация количества и качества фото
        $this->validateInputPhotos($photos);

        // 2. Предобработка (удаление фона, выравнивание)
        $processed = $this->preprocessPhotos($photos);

        // 3. Генерация 3D
        $result = $this->tripoSR->generate($processed);

        if ($result->confidence < 0.85) {
            $result = $this->meshyFallback->generate($processed);
        }

        // 4. Оптимизация и подготовка для Web
        $optimized = $this->optimizeForWeb($result->glb_path);

        // 5. Сохранение
        return $this->save3DAsset($product, $optimized, $result);
    }

    public function queueGeneration(Product $product, array $photoIds): void
    {
        dispatch(new Generate3DModelJob($product, $photoIds))
            ->onQueue('3d-generation')
            ->delay(10); // небольшая задержка для пакетной загрузки
    }
}
5. Интеграция в пользовательский путь
Для продавца (Tenant Panel):

При создании товара → вкладка «3D-модель»
Кнопка «Сгенерировать из фото» → drag & drop 8–12 изображений
Прогресс-бар + уведомление по окончании
Возможность ручной доработки (если качество низкое)

Для покупателя:

На странице товара автоматически показывается сгенерированная 3D-модель
Кнопка «Примерить в 3D»

6. Что должен вернуть ИИ

Полный код:
Generate3DFromPhotosService.php
Generate3DModelJob.php
Модель Product3DAsset с миграцией
Livewire компонент Generate3DFromPhotos (для Filament)

Интеграция с:
Вертикалями Одежда и Обувь
Системой размеров
CDN (Bunny/Cloudflare)
VirtualTryOn3DService

Тесты Pest (симуляция генерации, проверка качества, обработка ошибок)
Рекомендации по минимальному количеству фото и лучшим ракурсам для одежды и обуви.

Критерии приёмки (я проверяю лично):

Генерация 3D из 8–12 фото занимает менее 3–5 минут (в среднем)
Качество модели достаточное для реалистичной примерки
Автоматическая оптимизация под веб (Draco + LOD)
Работает стабильно для разных типов товаров (футболки, куртки, кроссовки, ботинки)
Полная интеграция с виртуальной примеркой

### 1. Цель
Реализовать продвинутый пайплайн генерации PBR-текстур (Albedo, Normal, Roughness, Metallic, AO) с использованием ControlNet + Stable Diffusion для автоматического создания реалистичных материалов 3D-моделей одежды и обуви из обычных 2D-фотографий.
Это позволит:

Получать фотореалистичное качество текстур даже из средних фото.
Сохранять детали ткани, швов, фурнитуры, кожи, замши и т.д.
Автоматически адаптировать текстуры под 3D-геометрию.

2. Архитектура пайплайна ControlNet
Основной стек:

Stable Diffusion XL или Flux.1 (base model)
ControlNet (Multiple ControlNets):
Canny / Lineart (для структуры)
Depth / Normal (для объёма)
OpenPose + DensePose (для одежды на теле)
IP-Adapter + Reference Only (для точного переноса стиля и цвета)

LoRA — fine-tuned на fashion-датасете (ткани, кожа, denim, knitwear и т.д.)

Этапы генерации:

Input — 8–12 фото товара (ракурсы + детали)
Multi-View Consistency — используем Multi-ControlNet
Генерация Albedo (основная цветовая карта)
Генерация Normal Map (рельеф ткани)
Roughness + Metallic (блеск, материал)
Displacement / AO (для объёма)
UV Unwrapping + baking на 3D-модель

3. Основной сервис
TextureGenerationService.php
PHPclass TextureGenerationService
{
    public function generatePBRTextures(Product $product, array $photos, string $materialType = 'fabric'): TexturePack
    {
        $prompt = $this->buildFashionPrompt($product, $materialType);
        $negativePrompt = "low quality, blurry, deformed, artifacts, plastic look";

        $controlImages = $this->prepareControlImages($photos); // Canny, Depth, OpenPose

        $result = $this->stableDiffusion->txt2img([
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt,
            'controlnet' => [
                ['image' => $controlImages['canny'], 'model' => 'control_v11p_sd15_canny'],
                ['image' => $controlImages['depth'], 'model' => 'control_v11f1p_sd15_depth'],
                ['image' => $controlImages['openpose'], 'model' => 'control_v11p_sd15_openpose'],
            ],
            'ip_adapter' => $photos[0], // Reference image
            'steps' => 50,
            'cfg_scale' => 7.5,
        ]);

        // Пост-обработка
        $textures = $this->postProcessTextures($result->images);

        return $this->saveTexturePack($product, $textures);
    }

    private function buildFashionPrompt(Product $product, string $materialType): string
    {
        return "high quality product photography of {$product->name}, {$materialType} texture, detailed fabric weave, realistic material, studio lighting, 8k, product shot";
    }
}
4. Интеграция в общий пайплайн
Generate3DFromPhotosService (обновлённый):

Генерация базовой геометрии (TripoSR / InstantMesh)
Генерация текстур через ControlNet (этот сервис)
Baking текстур на UV-развёртку
Оптимизация (Draco + KTX2)
Сохранение готового GLB

5. Что должен вернуть ИИ

Полный код:
TextureGenerationService.php с ControlNet + IP-Adapter
Обновлённый Generate3DFromPhotosService с интеграцией текстур
TexturePack модель + миграция
Livewire компонент для мониторинга генерации текстур в Filament

Настройки ControlNet (LoRA, ControlNet units, prompts для fashion)
Тесты Pest — генерация текстур, проверка качества PBR-карт, интеграция с 3D
Рекомендации по количеству фото и лучшим промптам для разных материалов (denim, leather, knit, silk и т.д.)

Критерии приёмки (я проверяю лично):

Текстуры выглядят фотореалистично (детали ткани, блики, складки)
Генерация работает стабильно из 8–12 фото
Сохранение брендового стиля и цвета
Готовый GLB-модель с PBR-материалами подходит для WebGL/Three.js
Время генерации одной модели < 4–6 минут (на хорошем GPU)

### 1. Цель
Настроить специализированные LoRA для Stable Diffusion / Flux, чтобы максимально качественно генерировать текстуры одежды и обуви: реалистичные ткани, кожу, швы, фурнитуру, драпировку и материал.
2. Рекомендуемые LoRA (готовые настройки)
Основные LoRA (обязательно использовать вместе)
1. Fashion Realism LoRA (главный)

Strength: 0.75 – 0.85
Trigger words: fashion product photography, studio lighting, detailed fabric, realistic texture
Лучшие версии: FashionRealism_v2.safetensors, RealisticFashion_v3

2. Fabric & Textile LoRA

Strength: 0.65 – 0.8
Trigger words: detailed weave, knit texture, denim, cotton, silk, linen, wool
Специально заточен под драпировку и микродетали ткани

3. Leather & Materials LoRA (для обуви и курток)

Strength: 0.7 – 0.82
Trigger words: genuine leather texture, suede, patent leather, stitching details

4. Garment Construction LoRA

Strength: 0.6 – 0.75
Trigger words: seams, stitching, buttons, zippers, hems, tailoring

5. IP-Adapter + Reference LoRA (для сохранения точного цвета и стиля)

Strength: 0.8 – 1.0
Используется вместе с reference image (первое фото товара)

3. Оптимальные настройки ControlNet + LoRA (рекомендуемые)
Для генерации текстур одежды:
YAML- ControlNet 1: Canny → weight 0.85–1.0
- ControlNet 2: Depth → weight 0.65–0.75
- ControlNet 3: OpenPose / DensePose → weight 0.7 (для одежды на теле)
- IP-Adapter → weight 0.75–0.9
- LoRA stack:
   - FashionRealism_v2: 0.78
   - FabricTextile: 0.72
   - GarmentConstruction: 0.68
Sampler & Параметры:

Sampler: DPM++ 2M Karras или Euler a
Steps: 35–50
CFG Scale: 6.5–7.5
Resolution: 1024x1024 или 768x1024 (зависит от ракурса)
Clip Skip: 2

Negative Prompt (универсальный):
textlowres, bad anatomy, bad hands, text, error, missing fingers, extra digit, fewer digits, cropped, worst quality, low quality, normal quality, jpeg artifacts, signature, watermark, username, blurry, deformed, ugly, plastic skin, doll-like
4. Специальные настройки по типам товаров
Джинсовая одежда / Denim:

Fabric LoRA strength: 0.85
Добавить в prompt: detailed denim weave, realistic cotton texture

Кожаная обувь / куртки:

Leather LoRA strength: 0.82
Добавить: genuine leather texture, visible pores, natural wrinkles

Трикотаж / Свитера:

Fabric LoRA + Knitwear specific LoRA strength 0.78
Prompt: cable knit, ribbed texture, soft wool

Спортивная одежда:

Technical Fabric LoRA (если есть) + 0.7
Prompt: breathable fabric, mesh panels, technical sportswear

5. Что должен вернуть ИИ

Готовый конфиг-файл fashion_lora_config.json со всеми LoRA + весами
Оптимальные промпты для разных категорий (одежда, обувь, верх/низ)
Обновлённый TextureGenerationService.php с поддержкой нескольких LoRA
Рекомендации по обучению собственного Fashion LoRA на данных CatVRF
Тесты для проверки качества текстур

### 1. Цель
Обучить собственный высококачественный Fashion LoRA, заточенный именно под CatVRF:

Реалистичные ткани, кожу, фурнитуру, швы, драпировку
Сохранение брендового стиля и цвета товара
Хорошую работу с одеждой и обувью одновременно
Минимальное количество артефактов

Этот LoRA будет использоваться в TextureGenerationService вместе с ControlNet.
2. Рекомендуемый базовый модель
Лучший выбор 2026:

Flux.1-dev (или Flux.1-schnell для скорости) — лучший результат для fashion
Альтернатива: SDXL 1.0 + Pony Diffusion V6 (если Flux недоступен)

3. Подготовка датасета (самое важное)
Минимальные требования:

800–1500 высококачественных изображений
Разрешение: минимум 1024×1024 (лучше 1344×768 или 1024×1536)
Только product photography (чистый фон, студийное освещение)
Ракурсы: front, back, side, 3/4, detail shots, flat lay
Разнообразие: одежда, обувь, разные материалы (денім, кожа, трикотаж, шёлк, мембрана и т.д.)

Структура папок датасета:
textfashion_dataset/
├── 1_kitten/          # caption: "kitten, white t-shirt, studio lighting"
├── 2_jeans/
├── 3_leather_jacket/
└── captions.txt       # или .txt файлы рядом с изображениями
Качество caption (очень важно!):
Используем формат:
masterpiece, best quality, product photography, [описание товара], detailed fabric texture, studio lighting, white background
4. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 32                    # или 64 для лучшего качества
alpha: 16
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 1e-4
lr_scheduler: cosine_with_restarts
max_train_steps: 4500–6500
save_every_n_steps: 500
mixed_precision: bf16
resolution: 1024
Для SDXL (если Flux нет):
YAMLrank: 64–128
alpha: 32
batch_size: 4
learning_rate: 5e-5
max_train_steps: 8000–12000
Trigger words (рекомендую):

catvrf fashion product, studio product shot, detailed clothing texture

5. Процесс обучения (пошагово)

Подготовить датасет + captions (использовать Florence-2 или GPT-4o для автогенерации)
Запуск обучения через Kohya_ss или Axolotl
Проверка на тестовом сете каждые 1000 шагов
Финальная конвертация в .safetensors
Тестирование в TextureGenerationService

6. Что должен вернуть ИИ

Полный конфигурационный файл (fashion_lora_train.toml или train.json)
Рекомендованный датасет (структура + примеры caption)
Обновлённый TextureGenerationService.php с использованием кастомного LoRA
Скрипт для автоматической подготовки датасета
Лучшие триггер-слова и negative prompt
Рекомендации по количеству эпох и LoRA rank

### 1. Цель
Создать специализированный LoRA для аксессуаров, который отлично работает с:

Сумками (кожаными, тканевыми, рюкзаками)
Ремнями и поясами
Украшениями (серьги, колье, браслеты)
Головными уборами (шляпы, кепки, бейсболки)
Шарфами, платками, перчатками
Очками, часами и мелкими аксессуарами

Этот LoRA будет использоваться вместе с основным Fashion LoRA для генерации текстур 3D-моделей.
2. Рекомендуемый подход
Название LoRA: CatVRF_Accessories_V1
Базовая модель:

Flux.1-dev (лучший результат)
Альтернатива: SDXL или Pony Diffusion V6

3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 48                    # оптимально для аксессуаров
alpha: 24
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 8e-5
max_train_steps: 5500 - 7500
resolution: 1024
mixed_precision: bf16
Trigger words (главные):

catvrf accessory, product photography, studio lighting, detailed texture, luxury accessory

Дополнительные триггеры по категориям:

Сумки: leather handbag, tote bag, shoulder bag, crossbody bag, detailed stitching
Ремни: leather belt, buckle details, high quality leather texture
Украшения: gold jewelry, silver necklace, elegant earrings, diamond details
Головные уборы: baseball cap, fedora hat, beanie, detailed knit texture

4. Структура датасета (очень важно!)
Рекомендуемый объём: 1200–2000 изображений
Структура папок:
textaccessories_dataset/
├── 1_leather_bag/          # caption: "catvrf accessory, luxury leather handbag, studio lighting, detailed stitching"
├── 2_silk_scarf/
├── 3_gold_necklace/
├── 4_baseball_cap/
├── 5_sunglasses/
└── captions.txt
Требования к фото:

Чистый белый/серый фон
Много деталей крупным планом (швы, фурнитура, текстура материала)
Разные углы (front, side, top, detail shots)
Реалистичное студийное освещение

5. Оптимальные настройки использования в генерации
В TextureGenerationService:
PHP$loras = [
    'fashion_realism' => 0.78,
    'fabric_textile'  => 0.65,
    'accessories_v1'  => 0.82,        // наш новый LoRA
    'leather_detail'  => 0.55,        // если нужно усилить кожу
];
Prompt пример для сумки:
textcatvrf accessory, luxury black leather handbag, detailed stitching, high quality texture, studio lighting, product photography, realistic material
Negative Prompt:
textlow quality, blurry, deformed, plastic look, cartoon, toy, bad anatomy, watermark
6. Что должен вернуть ИИ

Полный конфиг обучения (accessories_lora_train.toml или .json)
Структура датасета + примеры качественных caption
Рекомендованные веса LoRA при использовании вместе с Fashion LoRA
Обновлённый TextureGenerationService.php с поддержкой Accessories LoRA
Лучшие промпты для разных типов аксессуаров
Тестовые сценарии для проверки качества

### 1. Цель
Создать высококачественный специализированный LoRA для обуви, который отлично справляется с:

Кожей, замшей, нубуком, текстилем, синтетикой, сеткой
Деталями подошвы, швов, фурнитуры, шнурков, молний
Разными типами обуви (кроссовки, ботинки, туфли, сапоги, сандалии, лоферы и т.д.)
Реалистичными бликами, складками и текстурами материалов

Этот LoRA будет работать вместе с основным Fashion LoRA и Accessories LoRA.
2. Рекомендуемый базовый модель
Лучший выбор:

Flux.1-dev (максимальное качество)
Альтернатива: SDXL + Realistic Vision или Pony Diffusion V6

Название LoRA: CatVRF_Footwear_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 64                    # обувь требует больше деталей
alpha: 32
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 7e-5
max_train_steps: 6500 - 8500
resolution: 1024x1024 или 1152x768
mixed_precision: bf16
save_every_n_steps: 600
Trigger words (основные):

catvrf footwear, detailed shoe photography, studio lighting, realistic leather texture, product shot

Дополнительные триггеры:

sneakers, running shoes, leather boots, suede loafers, high heels, detailed sole, stitching, laces, rubber outsole

4. Структура датасета (критически важно)
Рекомендуемый объём: 1400–2200 изображений
Структура папок:
textfootwear_dataset/
├── 1_leather_boots/
├── 2_white_sneakers/
├── 3_suede_loafers/
├── 4_high_heels/
├── 5_sport_shoes/
└── captions.txt
Требования к фото:

Обязательно вид снизу (подошва) — очень важно для обуви
10–14 ракурсов на пару: front, side, back, 3/4, top view, sole view, detail (швы, логотип, фурнитура)
Чистый фон, студийное освещение
Разные материалы и сезоны

Примеры caption:
textcatvrf footwear, premium white leather sneakers, detailed stitching, realistic rubber sole, studio lighting, product photography, high quality texture
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'fashion_realism'    => 0.75,
    'fabric_textile'     => 0.55,
    'accessories_v1'     => 0.45,
    'footwear_v1'        => 0.85,     // наш новый LoRA — высокий вес
    'leather_detail'     => 0.70,
];
Prompt примеры:
Кроссовки:
textcatvrf footwear, premium running sneakers, breathable mesh and leather panels, detailed sole pattern, realistic texture, studio lighting
Кожаные ботинки:
textcatvrf footwear, luxury brown leather chelsea boots, detailed stitching and pull tabs, high quality leather texture, realistic wrinkles
Туфли на каблуке:
textcatvrf footwear, elegant black stiletto heels, glossy patent leather, detailed heel and sole, studio product photography
Negative Prompt:
textlow quality, blurry, deformed shoes, bad anatomy, plastic look, cartoon, extra laces, watermark, text
6. Что должен вернуть ИИ

Полный конфиг обучения (footwear_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании с другими LoRA
Обновлённый TextureGenerationService.php с примерами промптов для обуви
Тестовые сценарии проверки качества текстур подошвы, кожи и швов

### 1. Цель
Создать мощный специализированный LoRA для верхней одежды, который отлично справляется с:

Пуховиками, куртками, пальто, тренчами, парками, бомберами, дублёнками
Тяжёлыми и сложными материалами (пух, мембрана, шерсть, кожа, мех, стёжка)
Реалистичной драпировкой, складками, объёмом, капюшонами, молниями, карманами
Сезонными особенностями (зимняя, демисезонная, осенняя)

Этот LoRA будет использоваться совместно с Fashion LoRA, Footwear LoRA и Accessories LoRA.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (максимальное качество драпировки и текстур)
Альтернатива: SDXL + Realistic Vision / EpicRealism
Название LoRA: CatVRF_Outerwear_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 64                    # верхняя одежда требует высокой детализации
alpha: 32
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 6.5e-5
max_train_steps: 7200 - 9500
resolution: 1024x1024 или 1152x768
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf outerwear, detailed winter jacket, studio product photography, realistic fabric texture, high quality

Дополнительные триггеры:

puffer jacket, down coat, wool coat, trench coat, bomber jacket, parka, leather jacket
detailed stitching, quilted pattern, fur hood, heavy fabric, warm winter clothing
dramatic folds, realistic drape, visible seams, zipper details

4. Структура датасета
Рекомендуемый объём: 1600–2500 изображений высокого качества
Структура папок:
textouterwear_dataset/
├── 1_black_puffer_jacket/
├── 2_beige_wool_coat/
├── 3_leather_bomber/
├── 4_long_trench_coat/
├── 5_fur_parka/
└── captions.txt
Требования к фото:

Обязательно фото на модели + flat lay + детали (карманы, молнии, подкладка, капюшон)
Разные углы: front, side, back, 3/4, movement shots (для драпировки)
Студийное освещение + чистый фон
Разные материалы и сезоны

Примеры caption:
textcatvrf outerwear, premium black puffer jacket, detailed quilted pattern, realistic down filling, studio lighting, product photography, high quality texture
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'fashion_realism'     => 0.72,
    'fabric_textile'      => 0.68,
    'outerwear_v1'        => 0.88,        // высокий вес — основной LoRA
    'leather_detail'      => 0.60,
    'accessories_v1'      => 0.45,
];
Примеры промптов:
Пуховик:
textcatvrf outerwear, luxury black puffer jacket, thick down filling, detailed quilted stitching, realistic volume and drape, studio product photography
Шерстяное пальто:
textcatvrf outerwear, elegant beige wool coat, heavy wool texture, tailored fit, visible weave, dramatic folds, high quality fashion photography
Кожаная куртка:
textcatvrf outerwear, premium brown leather bomber jacket, natural leather texture, detailed stitching and ribbed cuffs, realistic wrinkles
Negative Prompt:
textlow quality, blurry, deformed clothing, bad anatomy, plastic look, cartoon, flat lighting, extra buttons, watermark
6. Что должен вернуть ИИ

Полный конфиг обучения (outerwear_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании с другими LoRA
Обновлённый TextureGenerationService.php с примерами промптов для верхней одежды
Тестовые сценарии для проверки качества драпировки и текстур

### 1. Цель
Создать высококачественный специализированный LoRA для нижней одежды (мужской, женской и unisex), который отлично передаёт:

Тонкие ткани (шёлк, кружево, микрофибра, хлопок, модал)
Полупрозрачность и текстуру кружева
Точное прилегание к телу, складки, эластичность
Детали (бретели, застёжки, кружевные вставки, швы)
Разные стили (классика, спортивное бельё, соблазнительное, корректирующее)

Этот LoRA будет использоваться совместно с Fashion LoRA, Outerwear LoRA и Accessories LoRA.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация тканей и тела)
Альтернатива: SDXL + Realistic Vision или Pony Diffusion V6 XL
Название LoRA: CatVRF_Lingerie_Underwear_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 48                    # баланс между детализацией и обобщением
alpha: 24
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 7e-5
max_train_steps: 5800 - 7800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 600
Trigger words (основные):

catvrf lingerie, detailed underwear, studio product photography, realistic fabric texture, delicate lace

Дополнительные триггеры:

lace bra and panty set, silk lingerie, cotton briefs, seamless underwear, high waist panties
transparent lace, embroidered details, soft fabric, elegant lingerie, seductive pose
men's boxer briefs, sport underwear, compression shorts

4. Структура датасета
Рекомендуемый объём: 1300–2200 изображений высокого качества
Структура папок:
textlingerie_dataset/
├── 1_lace_bra_set/
├── 2_silk_nightdress/
├── 3_cotton_panties/
├── 4_mens_boxer_briefs/
├── 5_sport_underwear/
└── captions.txt
Требования к фото:

Чистый фон, студийное мягкое освещение
Много close-up деталей (кружево, швы, текстура ткани)
Фото на модели + flat lay
Разные углы и варианты (front, side, back, detail)

Примеры caption:
textcatvrf lingerie, elegant black lace bra and panty set, delicate transparent lace, soft lighting, detailed fabric texture, studio product photography
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'fashion_realism'     => 0.70,
    'fabric_textile'      => 0.75,
    'outerwear_v1'        => 0.35,   // низкий вес, если комбинируем
    'lingerie_underwear_v1' => 0.85, // высокий вес — основной для этой категории
    'accessories_v1'      => 0.40,
];
Примеры промптов:
Женское кружевное бельё:
textcatvrf lingerie, seductive red lace bra and thong set, delicate floral lace pattern, transparent details, soft studio lighting, realistic fabric texture
Мужские боксеры:
textcatvrf underwear, premium black men's boxer briefs, breathable cotton fabric, detailed waistband and seams, studio product photography
Шёлковая ночная рубашка:
textcatvrf lingerie, elegant silk nightdress, flowing drape, soft sheen fabric, detailed straps and neckline, luxurious studio lighting
Negative Prompt:
textlow quality, blurry, deformed body, bad anatomy, extra limbs, plastic skin, cartoon, text, watermark, oversaturated
6. Что должен вернуть ИИ

Полный конфиг обучения (lingerie_lora_train.toml)
Структура датасета + примеры caption
Рекомендованные веса при совместном использовании с другими LoRA
Обновлённый TextureGenerationService.php с примерами промптов для нижней одежды
Тестовые сценарии для проверки кружева, прозрачности и прилегания

### 1. Цель
Создать высококачественный LoRA для генерации интерьеров, который отлично работает с:

Интерьерами салонов красоты, груминг-студий, ветеринарных клиник
Магазинами одежды и обуви (showroom)
Фитнес-залами и спа-зонами
Кабинетами врачей и процедурными
Современными минималистичными и премиум интерьерами

Этот LoRA будет использоваться для генерации фонов, виртуальных туров по помещениям и реалистичных 3D-интерьеров в маркетплейсе.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация пространства и материалов)
Альтернатива: SDXL + Realistic Vision / EpicRealism
Название LoRA: CatVRF_Interior_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 80                    # интерьеры требуют высокой детализации пространства
alpha: 40
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.5e-5
max_train_steps: 8500 - 11000
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf interior, modern beauty salon interior, professional veterinary clinic, detailed showroom, studio lighting, realistic

4. Структура датасета
Рекомендуемый объём: 1800–2800 изображений высокого качества
Структура папок:
textinterior_dataset/
├── 1_beauty_salon/
├── 2_vet_clinic/
├── 3_fashion_showroom/
├── 4_grooming_studio/
├── 5_fitness_hall/
└── captions.txt
Требования к фото:

Профессиональные интерьерные фотографии (журнальное качество)
Разные ракурсы: общий вид, детали (зона ресепшн, процедурный кабинет, витрины)
Разное освещение (дневное, вечернее, акцентное)
Чистые, современные интерьеры премиум и средний+ сегмент

Примеры caption:
textcatvrf interior, modern luxury beauty salon, white and gold color scheme, marble floor, professional lighting, detailed interior design, realistic
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'fashion_realism'     => 0.45,
    'interior_v1'         => 0.92,     // очень высокий вес
    'architectural_detail'=> 0.65,
];
Примеры промптов:
Салон красоты:
textcatvrf interior, elegant modern beauty salon, white marble walls, gold accents, professional lighting, large mirrors, comfortable chairs, luxury interior design, realistic, 8k
Ветеринарная клиника:
textcatvrf interior, modern veterinary clinic waiting room, clean white design, comfortable seating, reception desk, soft lighting, professional medical interior, realistic
Магазин одежды:
textcatvrf interior, luxury fashion clothing showroom, minimalist design, wooden floors, clothing racks, spot lighting, elegant retail interior, realistic
Negative Prompt:
textlow quality, blurry, deformed architecture, bad composition, empty room, cartoon, overexposed, underexposed, text, watermark
6. Что должен вернуть ИИ

Полный конфиг обучения (interior_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании с другими LoRA
Новый сервис InteriorGenerationService.php (или обновление TextureGenerationService)
Лучшие промпты для разных типов помещений (салон, клиника, магазин, груминг-зал)
Рекомендации по обучению и использованию

### 1. Цель
Создать высокоспециализированный LoRA, который отлично контролирует освещение интерьеров:

Студийное мягкое освещение
Драматическое и акцентное освещение
Естественный дневной свет
Теплый/холодный свет, объёмный свет, блик и тени
Профессиональное освещение салонов красоты, ветклиник, магазинов одежды, груминг-залов и фитнес-студий

Этот LoRA будет работать вместе с Interior LoRA для создания атмосферных и продающих визуализаций.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всего понимает свет и тени)
Альтернатива: SDXL + Realistic Vision / EpicRealism
Название LoRA: CatVRF_Lighting_Atmosphere_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 72                    # освещение требует высокой детализации света и теней
alpha: 36
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5e-5
max_train_steps: 7800 - 10500
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf lighting, professional studio lighting, soft diffused light, dramatic lighting, realistic atmosphere

Дополнительные триггеры:

warm lighting, cold lighting, volumetric light, god rays, soft shadows, accent lighting
beauty salon lighting, veterinary clinic lighting, fashion showroom lighting, cinematic interior lighting

4. Структура датасета
Рекомендуемый объём: 1600–2600 изображений
Структура папок:
textlighting_dataset/
├── 1_soft_studio_lighting/
├── 2_dramatic_warm_lighting/
├── 3_natural_daylight/
├── 4_beauty_salon_lighting/
├── 5_vet_clinic_ambience/
├── 6_fashion_showroom_spotlights/
└── captions.txt
Требования к фото:

Профессиональные интерьерные фотографии с акцентом на свет
Разные типы освещения в одном помещении
Видны блики, мягкие тени, отражения, объём
Чистые, современные интерьеры

Примеры caption:
textcatvrf lighting, luxury beauty salon interior, soft diffused warm lighting, gentle shadows, professional studio lighting, realistic atmosphere, high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'           => 0.88,
    'lighting_atmosphere_v1'=> 0.92,     // высокий вес — главный для освещения
    'fashion_realism'       => 0.45,
];
Примеры промптов:
Салон красоты:
textcatvrf lighting, modern beauty salon interior, soft warm diffused lighting, elegant atmosphere, gentle shadows on marble floor, professional studio lighting, realistic, 8k
Ветеринарная клиника:
textcatvrf lighting, clean modern veterinary clinic, bright cool daylight lighting, soft shadows, professional medical interior, calming atmosphere, realistic
Магазин одежды:
textcatvrf lighting, luxury fashion showroom, dramatic spotlight lighting on clothing racks, soft ambient light, high-end retail atmosphere, realistic product photography
Negative Prompt:
textflat lighting, harsh shadows, overexposed, underexposed, bad lighting, cartoonish, low quality, blurry, no depth, unrealistic shadows
6. Что должен вернуть ИИ

Полный конфиг обучения (lighting_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый сервис с примерами промптов для разных типов помещений
Рекомендации по комбинации с Interior LoRA

### 1. Цель
Создать фундаментальный, высококачественный LoRA, специализирующийся именно на реалистичных текстурах материалов.
Он будет базовым для всех вертикалей (одежда, обувь, аксессуары, интерьеры, груминг-продукты, мебель и т.д.).
Этот LoRA должен отлично передавать:

Ткани (хлопок, шёлк, шерсть, кашемир, деним, трикотаж, мембрана)
Кожу (натуральная, экокожа, замша, нубук)
Металлы и фурнитуру
Дерево, мрамор, бетон, керамику
Пластик, резину, стекло и т.д.

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация текстур)
Альтернатива: SDXL + Realistic Vision / Juggernaut XL
Название LoRA: CatVRF_Material_Texture_Master_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 96                    # высокий rank — для максимальной детализации текстур
alpha: 48
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9500 - 13500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf material texture, ultra realistic fabric, detailed material surface, studio product photography, high quality texture

Дополнительные триггеры:

detailed cotton fabric, realistic leather texture, soft cashmere, denim weave, silk sheen, wool knit, suede texture
visible stitching, natural wrinkles, micro details, pore texture, reflective surface

4. Структура датасета
Рекомендуемый объём: 2200–3500 изображений (чем больше — тем лучше)
Структура папок:
textmaterial_texture_dataset/
├── 1_cotton_fabric/
├── 2_leather/
├── 3_denim/
├── 4_silk/
├── 5_wool_knit/
├── 6_suede/
├── 7_marble/
├── 8_metal_furniture/
└── captions.txt
Требования к датасету:

Макро-снимки текстур (close-up)
Разные углы освещения (чтобы модель училась бликам и теням)
Реальные материалы в студийных условиях
Изображения без текста и watermark

Примеры caption:
textcatvrf material texture, ultra detailed blue denim fabric, visible weave pattern, realistic cotton texture, studio lighting, high quality
5. Оптимальные настройки использования
В TextureGenerationService (рекомендуемая комбинация):
PHP$loras = [
    'material_texture_master_v1' => 0.90,   // основной и самый сильный
    'fashion_realism'            => 0.65,
    'fabric_textile'             => 0.70,
    'leather_detail'             => 0.75,
    'interior_v1'                => 0.55,
];
Примеры промптов:
Деним:
textcatvrf material texture, detailed blue denim fabric, realistic weave and texture, visible threads, studio lighting, product photography
Кожа:
textcatvrf material texture, premium brown genuine leather, natural pore texture, realistic wrinkles and folds, high quality material surface
Шёлк:
textcatvrf material texture, luxurious silk fabric, soft sheen and drape, delicate weave, elegant material, studio lighting
Мрамор (для интерьеров):
textcatvrf material texture, white carrara marble surface, realistic veining and polish, detailed stone texture, studio lighting
6. Что должен вернуть ИИ

Полный конфиг обучения (material_texture_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании с другими LoRA
Обновлённый TextureGenerationService.php с примерами промптов
Тестовые сценарии для проверки разных материалов

### 1. Цель
Создать высокоспециализированный LoRA, который отлично контролирует отражения, блики, глянец и отражения окружения на различных поверхностях.
Этот LoRA критически важен для:

Глянцевой кожи, лакированной обуви, металлической фурнитуры
Зеркальных и стеклянных поверхностей в интерьерах
Глянцевых тканей, атласа, кожи в одежде
Косметики, упаковки, ювелирных изделий
Металлических элементов в груминге и ветеринарии

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с отражениями и светом)
Альтернатива: SDXL + Realistic Vision / Juggernaut XL
Название LoRA: CatVRF_Reflections_Specular_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 80                    # высокий rank для точной работы со светом и отражениями
alpha: 40
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5e-5
max_train_steps: 8200 - 12000
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf reflections, specular highlights, glossy surface, realistic reflections, studio lighting, detailed material

Дополнительные триггеры:

shiny leather, glossy patent leather, metallic reflections, glass surface reflection, chrome details
strong specular highlight, environment reflection, realistic light bounce, wet surface look

4. Структура датасета
Рекомендуемый объём: 1500–2600 изображений
Структура папок:
textreflections_dataset/
├── 1_glossy_leather_shoes/
├── 2_patent_bag/
├── 3_chrome_furniture/
├── 4_glass_cosmetics/
├── 5_wet_surface/
├── 6_mirror_reflections/
└── captions.txt
Требования к фото:

Сильные отражения и блики (студийное освещение с софтбоксами и рефлекторами)
Видны отражения окружающей среды на поверхности
Макро-снимки + общие планы
Разные материалы: глянцевая кожа, лак, металл, стекло, мокрые поверхности

Примеры caption:
textcatvrf reflections, glossy black patent leather shoes, strong specular highlights, realistic environment reflection, detailed shine, studio lighting, high quality
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'material_texture_master_v1' => 0.85,
    'reflections_specular_v1'    => 0.88,     // очень высокий вес
    'fashion_realism'            => 0.60,
    'interior_v1'                => 0.55,
];
Примеры промптов:
Лакированная обувь:
textcatvrf reflections, shiny black patent leather high heels, strong specular highlights, realistic mirror-like reflection, detailed surface shine, studio product photography
Металлическая фурнитура:
textcatvrf reflections, polished chrome metal buckle on leather bag, crisp specular highlights, environment reflection on metal, realistic metallic texture
Интерьер (стекло и мрамор):
textcatvrf reflections, luxury beauty salon interior, glossy marble floor with realistic reflections, glass surfaces with light bounce, high-end atmosphere
Negative Prompt:
textmatte surface, flat lighting, no reflections, dull material, low contrast, bad specular, plastic look, cartoon
6. Что должен вернуть ИИ

Полный конфиг обучения (reflections_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Material Texture LoRA

### 1. Цель
Создать высокоспециализированный LoRA, который превосходно контролирует тени, объёмный свет, мягкие переходы, god rays, ambient occlusion и общее освещение сцен.
Этот LoRA особенно важен для:

Реалистичных интерьеров (салоны, клиники, магазины)
Продуктовой съёмки одежды и обуви
Атмосферных 3D-примерок
Виртуальных туров по помещениям

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает со светом и тенями)
Альтернатива: SDXL + EpicRealism / Realistic Vision
Название LoRA: CatVRF_Shadows_Lighting_Master_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92                    # высокий rank для точной работы со светотенью
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 9200 - 12800
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf advanced lighting, realistic shadows, soft volumetric light, dramatic lighting, cinematic atmosphere, detailed light and shadow

Дополнительные триггеры:

soft diffused shadows, god rays, subtle ambient occlusion, realistic light falloff
dramatic side lighting, warm golden hour lighting, cool studio lighting, soft beauty lighting

4. Структура датасета
Рекомендуемый объём: 1800–3000 изображений
Структура папок:
textshadows_lighting_dataset/
├── 1_soft_diffused_lighting/
├── 2_dramatic_shadows/
├── 3_volumetric_god_rays/
├── 4_beauty_salon_lighting/
├── 5_fashion_product_lighting/
├── 6_interior_ambient_occlusion/
└── captions.txt
Требования к фото:

Профессиональные фотографии с акцентом на свет и тени
Видны мягкие переходы, объём, отражения света
Разные настроения: мягкое, драматическое, студийное, естественное

Примеры caption:
textcatvrf advanced lighting, soft diffused shadows on luxury clothing, realistic light falloff, gentle volumetric light, studio product photography, high quality atmosphere
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'material_texture_master_v1'   => 0.82,
    'reflections_specular_v1'      => 0.78,
    'shadows_lighting_master_v1'   => 0.94,     // самый высокий вес
    'interior_v1'                  => 0.85,
    'fashion_realism'              => 0.55,
];
Примеры промптов:
Интерьер салона:
textcatvrf advanced lighting, modern beauty salon interior, soft diffused lighting with realistic shadows, gentle volumetric god rays, warm atmosphere, detailed light falloff, high quality
Продуктовая съёмка обуви:
textcatvrf advanced lighting, luxury leather shoes, dramatic side lighting with soft shadows, realistic specular highlights and subtle reflections, studio product photography
3D-примерка одежды:
textcatvrf advanced lighting, elegant coat on model, soft natural window lighting, realistic fabric shadows and folds, cinematic atmosphere
Negative Prompt:
textflat lighting, no shadows, harsh shadows, overexposed, underexposed, bad lighting, cartoonish, no depth, unrealistic light
6. Что должен вернуть ИИ

Полный конфиг обучения (shadows_lighting_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Reflections и Interior LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично контролирует объёмный свет (volumetric lighting) — god rays, световые конусы, туман/дым с подсветкой, мягкие лучи через окна, атмосферный объём в интерьерах и продуктовой съёмке.
Этот LoRA особенно важен для:

Атмосферных интерьеров салонов, клиник, магазинов
Драматичных 3D-примерок одежды и обуви
Виртуальных туров по помещениям
Продуктовых фото с премиальным ощущением

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с объёмным светом)
Альтернатива: SDXL + EpicRealism / Realistic Vision
Название LoRA: CatVRF_Volumetric_Lighting_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 88                    # высокий rank для сложных световых эффектов
alpha: 44
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 8800 - 12500
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf volumetric lighting, god rays, volumetric fog, atmospheric light shafts, realistic light scattering

Дополнительные триггеры:

soft god rays through window, dramatic volumetric lighting, cinematic atmosphere, dust particles in light beam
warm volumetric light, cool morning god rays, soft haze lighting, beautiful light volume

4. Структура датасета
Рекомендуемый объём: 1700–2800 изображений
Структура папок:
textvolumetric_lighting_dataset/
├── 1_god_rays_window/
├── 2_dramatic_volumetric_fog/
├── 3_soft_atmospheric_lighting/
├── 4_beauty_salon_god_rays/
├── 5_fashion_showroom_volumetric/
├── 6_interior_light_shafts/
└── captions.txt
Требования к фото:

Ярко выраженный объёмный свет (god rays, световые конусы)
Видимые частицы пыли/дыма в лучах
Разные типы освещения: через окна, акцентные источники, мягкий haze
Высокое качество, профессиональная съёмка

Примеры caption:
textcatvrf volumetric lighting, beautiful god rays streaming through window in modern interior, soft atmospheric haze, realistic light scattering, cinematic lighting, high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'material_texture_master_v1'   => 0.80,
    'reflections_specular_v1'      => 0.75,
    'shadows_lighting_master_v1'   => 0.82,
    'volumetric_lighting_v1'       => 0.93,     // самый высокий вес
    'interior_v1'                  => 0.88,
];
Примеры промптов:
Интерьер салона:
textcatvrf volumetric lighting, luxury beauty salon interior, dramatic god rays through large windows, soft atmospheric haze, beautiful light shafts, realistic volumetric lighting, high quality
Магазин одежды:
textcatvrf volumetric lighting, elegant fashion showroom, soft volumetric god rays highlighting clothing racks, cinematic atmosphere, realistic light scattering
3D-примерка:
textcatvrf volumetric lighting, model wearing coat in studio with beautiful god rays and soft volumetric light, dramatic yet soft atmosphere, realistic
Negative Prompt:
textflat lighting, no god rays, no volume, harsh light, cartoonish, bad atmosphere, overexposed, no depth
6. Что должен вернуть ИИ

Полный конфиг обучения (volumetric_lighting_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании с другими LoRA
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Shadows, Reflections и Interior LoRA

### 1. Цель
Создать высококачественный LoRA, который отлично контролирует цветокоррекцию, цветовой тон, контраст, насыщенность и общее настроение изображения.
Этот LoRA особенно важен для:

Красивой и一致ной цветопередачи в 3D-примерках одежды и обуви
Премиум-визуализации интерьеров
Продуктовой съёмки (чтобы цвета товаров выглядели точно и аппетитно)
Создания единого фирменного стиля всех визуалов CatVRF

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с цветом)
Альтернатива: SDXL + Film Photography / Cinematic LoRA base
Название LoRA: CatVRF_ColorGrading_Master_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 64
alpha: 32
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 6e-5
max_train_steps: 6800 - 9200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 650
Trigger words (основные):

catvrf color grading, professional color correction, cinematic color palette, accurate colors, beautiful color grading

Дополнительные триггеры:

warm tone, cool tone, vibrant colors, soft pastel, luxury fashion color grading, natural skin tones
high contrast, soft contrast, film look, teal and orange, golden hour grading

4. Структура датасета
Рекомендуемый объём: 1400–2400 изображений
Структура папок:
textcolor_grading_dataset/
├── 1_warm_fashion_grading/
├── 2_cool_minimal_grading/
├── 3_luxury_beauty_tone/
├── 4_natural_product_colors/
├── 5_cinematic_interior/
├── 6_vibrant_summer_palette/
└── captions.txt
Требования к датасету:

Парные изображения: "до цветокоррекции" и "после" (желательно)
Профессиональные фото с разными стилями цветокоррекции
Много fashion, product, interior снимков

Примеры caption:
textcatvrf color grading, luxury fashion product photography, warm elegant color correction, accurate skin tones, beautiful cinematic grading, high quality
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'material_texture_master_v1'   => 0.82,
    'reflections_specular_v1'      => 0.75,
    'volumetric_lighting_v1'       => 0.88,
    'color_grading_master_v1'      => 0.90,     // очень высокий вес
    'interior_v1'                  => 0.70,
];
Примеры промптов:
Одежда:
textcatvrf color grading, premium denim jacket, professional fashion color correction, accurate vibrant blue tones, soft natural lighting, luxury product photography
Интерьер:
textcatvrf color grading, modern beauty salon interior, warm elegant color grading, soft beige and gold tones, realistic atmosphere, high quality
Обувь:
textcatvrf color grading, white leather sneakers, clean bright color correction, accurate white tones with subtle shadows, studio product shot
Negative Prompt:
textbad color grading, oversaturated, washed out colors, wrong color tone, unnatural colors, color artifacts, low contrast
6. Что должен вернуть ИИ

Полный конфиг обучения (color_grading_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по стилям цветокоррекции (warm luxury, cool minimal, vibrant и т.д.)

### 1. Цель
Создать специализированный LoRA, который отлично контролирует цветовые профили, фирменные цветовые схемы и консистентность цветов по всему проекту.
Этот LoRA должен обеспечивать:

Точное попадание в фирменные цвета брендов
Единый цветовой стиль для всех товаров одного продавца/тенанта
Красивые, гармоничные и продающие цветовые палитры
Сохранение точности цвета при генерации текстур и 3D-моделей

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая точность цвета)
Альтернатива: SDXL + Film Photography styles
Название LoRA: CatVRF_ColorProfile_Consistency_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 64
alpha: 32
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.5e-5
max_train_steps: 7200 - 9800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf color profile, brand color consistency, accurate brand colors, professional color palette, consistent color grading

Дополнительные триггеры:

signature brand colors, luxury color scheme, minimalist neutral palette, vibrant fashion palette
cohesive color story, accurate hex color reproduction, brand identity colors

4. Структура датасета
Рекомендуемый объём: 1400–2600 изображений
Структура папок:
textcolor_profile_dataset/
├── 1_luxury_brand_palette/      # Chanel, Dior style
├── 2_minimal_neutral_scheme/
├── 3_vibrant_fashion_palette/
├── 4_beauty_salon_branding/
├── 5_vet_clinic_calm_tones/
├── 6_fashion_retail_identity/
└── captions.txt
Требования к датасету:

Изображения с чётко выраженной цветовой палитрой
Парные: "оригинал" и "с применённой цветокоррекцией"
Фото товаров, интерьеров, брендовых съёмок
Разные настроения (luxury, minimal, vibrant, calm medical и т.д.)

Примеры caption:
textcatvrf color profile, luxury fashion brand color consistency, signature beige and black palette, accurate brand colors, professional product photography
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'material_texture_master_v1'     => 0.80,
    'color_grading_master_v1'        => 0.85,
    'color_profile_consistency_v1'   => 0.92,     // очень высокий вес
    'reflections_specular_v1'        => 0.70,
    'volumetric_lighting_v1'         => 0.75,
];
Примеры промптов:
Одежда:
textcatvrf color profile, luxury clothing collection, signature brand color consistency, accurate deep burgundy and gold palette, professional studio photography
Интерьер:
textcatvrf color profile, modern veterinary clinic interior, calm medical color scheme, accurate soft blue and white palette, clean professional atmosphere
Обувь:
textcatvrf color profile, premium sneakers, brand color consistency, accurate white with red accents, clean product photography
Negative Prompt:
textcolor shift, wrong colors, inaccurate brand palette, oversaturated, washed out, color artifacts, inconsistent tones
6. Что должен вернуть ИИ

Полный конфиг обучения (color_profile_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по созданию брендовых цветовых профилей

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт реалистичные текстуры и свойства строительных материалов:

Бетон, кирпич, штукатурка, цемент
Дерево (массив, ДСП, фанера, брус)
Металл (сталь, алюминий, профиль, ржавчина)
Стекло, плитка, керамогранит, мрамор, гранит
Кровельные материалы, утеплители, гипсокартон, краски и т.д.

Этот LoRA будет использоваться для генерации реалистичных 3D-интерьеров, экстерьеров, визуализаций ремонта и строительных проектов.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация материалов)
Альтернатива: SDXL + Realistic Vision / Juggernaut XL
Название LoRA: CatVRF_Construction_Materials_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 96                    # высокий rank — строительные материалы требуют максимальной детализации текстуры
alpha: 48
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.2e-5
max_train_steps: 9200 - 13800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf construction material, ultra realistic building texture, detailed surface, professional material photography

Дополнительные триггеры:

raw concrete texture, red brick wall, natural wood grain, polished marble, rusty metal, ceramic tiles, plaster wall
visible material imperfections, realistic roughness, detailed seams, construction site material

4. Структура датасета
Рекомендуемый объём: 2000–3500 изображений высокого качества
Структура папок:
textconstruction_materials_dataset/
├── 1_concrete/
├── 2_brick/
├── 3_wood/
├── 4_marble_granite/
├── 5_metal_steel/
├── 6_ceramic_tiles/
├── 7_plaster/
├── 8_roofing/
└── captions.txt
Требования к фото:

Макро-снимки текстур (close-up)
Разные состояния (новый, состаренный, мокрый, грязный)
Разные углы освещения (чтобы модель училась отражениям и теням)
Реальные строительные материалы в студийных и реальных условиях

Примеры caption:
textcatvrf construction material, ultra detailed gray concrete texture with realistic pores and micro cracks, studio lighting, high quality material photography
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'material_texture_master_v1'     => 0.85,
    'construction_materials_v1'      => 0.94,     // очень высокий вес
    'reflections_specular_v1'        => 0.72,
    'shadows_lighting_master_v1'     => 0.78,
    'interior_v1'                    => 0.88,
];
Примеры промптов:
Бетон:
textcatvrf construction material, realistic gray concrete wall, detailed surface texture with micro cracks and pores, soft studio lighting, high quality
Кирпич:
textcatvrf construction material, red brick wall with visible mortar, realistic texture and weathering, detailed masonry, natural lighting
Дерево:
textcatvrf construction material, natural oak wood texture, visible grain and knots, realistic wood surface, warm lighting
Мрамор:
textcatvrf construction material, polished white carrara marble, realistic veining and glossy surface, high-end material photography
Negative Prompt:
textcartoon, plastic look, low detail, blurry texture, fake material, bad surface, oversimplified
6. Что должен вернуть ИИ

Полный конфиг обучения (construction_materials_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для строительных материалов
Рекомендации по комбинации с другими LoRA (Interior, Reflections, Volumetric Lighting)

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт текстуры и свойства:

Обои (бумажные, виниловые, флизелиновые, текстильные, 3D, фотообои)
Краски (матовые, глянцевые, сатиновые, фактурные, с эффектами)
Декоративные штукатурки (венецианская, марокканская, травертин, шёлк и т.д.)
Панели, декоративный камень, жидкие обои, микроцемент

Этот LoRA будет особенно полезен для генерации интерьеров салонов красоты, ветклиник, магазинов одежды и жилых пространств.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Wall_Finishes_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 88
alpha: 44
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5e-5
max_train_steps: 8500 - 11800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf wall finish, detailed wallpaper texture, realistic paint surface, decorative plaster, interior wall material

Дополнительные триггеры:

elegant floral wallpaper, textured vinyl wallpaper, smooth matte paint, glossy accent wall, venetian plaster
microcement wall, liquid wallpaper, 3d wall panel, brick effect wallpaper, silk plaster

4. Структура датасета
Рекомендуемый объём: 1600–2800 изображений
Структура папок:
textwall_finishes_dataset/
├── 1_floral_wallpaper/
├── 2_geometric_wallpaper/
├── 3_matte_paint/
├── 4_glossy_paint/
├── 5_venetian_plaster/
├── 6_microcement/
├── 7_textured_decorative/
├── 8_3d_wall_panels/
└── captions.txt
Требования к фото:

Макро-снимки текстуры + общие виды стен
Разные углы освещения (чтобы модель училась бликам и теням)
Реальные интерьеры с качественной отделкой

Примеры caption:
textcatvrf wall finish, elegant blue floral wallpaper with delicate pattern, realistic paper texture, soft studio lighting, detailed interior material
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'material_texture_master_v1'   => 0.82,
    'interior_v1'                  => 0.88,
    'wall_finishes_v1'             => 0.94,     // очень высокий вес
    'shadows_lighting_master_v1'   => 0.78,
    'volumetric_lighting_v1'       => 0.72,
];
Примеры промптов:
Обои:
textcatvrf wall finish, luxury floral wallpaper on accent wall, realistic paper texture with delicate pattern, soft natural lighting, high quality interior
Краска:
textcatvrf wall finish, smooth matte deep green paint on wall, realistic paint surface with subtle texture, clean modern interior, studio lighting
Декоративная штукатурка:
textcatvrf wall finish, venetian plaster wall with beautiful texture and depth, elegant marble-like effect, warm lighting, luxury interior design
Negative Prompt:
textflat wall, blurry texture, bad pattern, plastic look, low detail, cartoon, oversimplified surface
6. Что должен вернуть ИИ

Полный конфиг обучения (wall_finishes_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для стеновых материалов
Рекомендации по комбинации с Interior и Lighting LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт текстуры и свойства напольных покрытий:

Паркет, инженерная доска, массив
Ламинат, виниловая плитка (LVT, SPC)
Керамогранит, плитка, мозаика
Наливные полы, микроцемент, бетонные полы
Ковролин, ковровая плитка, натуральный камень
Пробковые покрытия, линолеум и т.д.

Этот LoRA особенно важен для реалистичных интерьеров салонов красоты, ветклиник, магазинов одежды, фитнес-залов и жилых пространств.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Flooring_Materials_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92                    # высокий rank — напольные покрытия требуют детальной текстуры и отражений
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 8800 - 13200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf flooring, realistic floor texture, detailed floor material, interior floor surface, studio lighting

Дополнительные триггеры:

natural oak parquet, herringbone pattern, matte laminate floor, glossy porcelain tile, microcement floor
visible wood grain, tile grout lines, realistic reflections on floor, detailed texture

4. Структура датасета
Рекомендуемый объём: 1800–3200 изображений
Структура папок:
textflooring_dataset/
├── 1_oak_parquet/
├── 2_herringbone_floor/
├── 3_laminate_planks/
├── 4_porcelain_tile/
├── 5_microcement_floor/
├── 6_natural_stone/
├── 7_vinyl_plank/
├── 8_carpet_tiles/
└── captions.txt
Требования к фото:

Макро-снимки текстуры пола + общие интерьерные планы
Разные углы освещения (чтобы модель училась бликам и отражениям)
Видимые стыки, швы, структура материала
Реальные помещения с качественной отделкой

Примеры caption:
textcatvrf flooring, natural oak herringbone parquet floor, detailed wood grain and realistic reflections, warm studio lighting, high quality interior material
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'material_texture_master_v1'   => 0.82,
    'interior_v1'                  => 0.88,
    'flooring_materials_v1'        => 0.95,     // самый высокий вес
    'reflections_specular_v1'      => 0.78,
    'shadows_lighting_master_v1'   => 0.80,
];
Примеры промптов:
Паркет:
textcatvrf flooring, elegant oak herringbone parquet floor, realistic wood grain and natural reflections, warm lighting, luxury interior
Плитка:
textcatvrf flooring, large format porcelain tile with subtle veining, realistic grout lines and glossy surface, modern clinic interior
Микроцемент:
textcatvrf flooring, seamless microcement floor, industrial concrete texture with soft matte finish, realistic surface details
Negative Prompt:
textblurry floor, fake texture, bad perspective, cartoon floor, low detail, plastic look, repeating pattern errors
6. Что должен вернуть ИИ

Полный конфиг обучения (flooring_materials_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для напольных покрытий
Рекомендации по комбинации с Reflections, Volumetric Lighting и Interior LoRA

### 1. Цель
Создать высококачественный специализированный LoRA для генерации реалистичных потолочных покрытий и конструкций, который отлично передаёт:

Натяжные потолки (глянцевые, матовые, сатиновые, с фотопечатью)
Гипсокартонные потолки (многоуровневые, с подсветкой, нишами)
Реечные, кассетные, грильято, армстронг
Деревянные, балочные, кофферные потолки
Лепнину, молдинги, розетки
Промышленные потолки, бетон, открытые коммуникации

Этот LoRA критически важен для реалистичных интерьеров салонов красоты, ветклиник, магазинов одежды, фитнес-залов и жилых пространств.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Ceiling_Finishes_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 84
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5e-5
max_train_steps: 7800 - 11500
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf ceiling finish, realistic ceiling texture, detailed ceiling design, interior ceiling surface

Дополнительные триггеры:

glossy stretch ceiling, matte tension ceiling, multi-level gypsum ceiling, coffered ceiling, wooden beam ceiling
recessed lighting, LED cove lighting, decorative molding, plaster rosette, industrial exposed ceiling

4. Структура датасета
Рекомендуемый объём: 1600–2700 изображений
Структура папок:
textceiling_finishes_dataset/
├── 1_glossy_stretch_ceiling/
├── 2_matte_tension_ceiling/
├── 3_multi_level_gypsum/
├── 4_coffered_wooden_ceiling/
├── 5_recessed_lighting_design/
├── 6_industrial_exposed_ceiling/
├── 7_decorative_plaster_molding/
└── captions.txt
Требования к фото:

Снимки снизу вверх (low angle) + общие интерьерные планы
Чётко видно структуру потолка, освещение, тени и отражения
Разные типы помещений (салон, клиника, магазин, жилое)

Примеры caption:
textcatvrf ceiling finish, modern glossy white stretch ceiling with hidden LED lighting, perfect reflections, realistic tension ceiling texture, luxury interior
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.88,
    'ceiling_finishes_v1'          => 0.94,     // очень высокий вес
    'shadows_lighting_master_v1'   => 0.82,
    'volumetric_lighting_v1'       => 0.78,
    'wall_finishes_v1'             => 0.75,
];
Примеры промптов:
Натяжной потолок:
textcatvrf ceiling finish, luxurious glossy white stretch ceiling with perfect mirror reflections, hidden LED cove lighting, modern beauty salon interior, realistic
Многоуровневый гипсокартон:
textcatvrf ceiling finish, elegant multi-level gypsum ceiling with recessed lighting, soft shadows and beautiful depth, modern veterinary clinic interior
Деревянный балочный потолок:
textcatvrf ceiling finish, rustic wooden beam ceiling with visible grain and texture, warm ambient lighting, cozy fashion showroom
Negative Prompt:
textflat ceiling, blurry texture, bad perspective, deformed ceiling, low detail, cartoon, unrealistic lighting
6. Что должен вернуть ИИ

Полный конфиг обучения (ceiling_finishes_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для потолков
Рекомендации по комбинации с Interior, Wall Finishes и Lighting LoRA

### 1. Цель
Создать мощный, детализированный LoRA, специализирующийся именно на стеновых покрытиях, который отлично передаёт:

Обои всех типов (бумажные, виниловые, флизелиновые, текстильные, 3D, фотообои)
Краски (матовые, глянцевые, сатиновые, фактурные, с эффектами)
Декоративные штукатурки (венецианская, марокканская, травертин, шёлк, бетон)
Стеновые панели, декоративный камень, кирпичная кладка, микроцемент
Тканевые и кожаные стеновые покрытия

Этот LoRA будет ключевым для генерации реалистичных интерьеров салонов красоты, ветклиник, магазинов одежды, груминг-залов и т.д.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Wall_Coverings_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 96                    # высокий rank — стеновые покрытия требуют максимальной детализации текстуры
alpha: 48
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9200 - 13500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf wall covering, detailed wall texture, realistic wall finish, interior wall surface, professional material

Дополнительные триггеры:

floral wallpaper, geometric wallpaper, textured vinyl wall, venetian plaster wall, microcement wall
matte paint wall, glossy accent wall, brick wall, decorative molding, fabric wall covering

4. Структура датасета
Рекомендуемый объём: 2000–3400 изображений
Структура папок:
textwall_coverings_dataset/
├── 1_floral_wallpaper/
├── 2_textured_vinyl/
├── 3_venetian_plaster/
├── 4_microcement_wall/
├── 5_brick_wall/
├── 6_fabric_wall_panel/
├── 7_matte_paint/
├── 8_glossy_accent_wall/
└── captions.txt
Требования к фото:

Макро-снимки текстуры + общие виды стен в интерьере
Разные освещения (чтобы модель училась отражениям и теням)
Реальные помещения с качественной отделкой стен

Примеры caption:
textcatvrf wall covering, elegant floral wallpaper with delicate pattern and realistic texture, soft studio lighting, detailed interior material, high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'material_texture_master_v1'   => 0.82,
    'wall_coverings_v1'            => 0.95,     // самый высокий вес
    'interior_v1'                  => 0.88,
    'shadows_lighting_master_v1'   => 0.80,
    'volumetric_lighting_v1'       => 0.75,
];
Примеры промптов:
Обои:
textcatvrf wall covering, luxury floral wallpaper on feature wall, realistic paper texture with delicate pattern, soft natural lighting, high quality interior
Декоративная штукатурка:
textcatvrf wall covering, beautiful venetian plaster wall with depth and texture, elegant marble-like effect, warm lighting, luxury beauty salon interior
Микроцемент:
textcatvrf wall covering, seamless microcement wall, industrial concrete texture with matte finish, realistic surface details, modern veterinary clinic
Negative Prompt:
textflat wall, blurry texture, bad pattern, plastic look, low detail, cartoon, repeating pattern error, oversimplified
6. Что должен вернуть ИИ

Полный конфиг обучения (wall_coverings_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для стеновых покрытий
Рекомендации по комбинации с другими LoRA (Interior, Flooring, Ceiling)

### 1. Цель
Создать высококачественный специализированный LoRA для детской одежды, обуви, аксессуаров и товаров для детей, который отлично передаёт:

Мягкость, миловидность и безопасность детских вещей
Яркие, но не кричащие цвета
Текстуры детских тканей (хлопок, флис, велюр, трикотаж, органический хлопок)
Детали: принты, аппликации, вышивки, рюши, кнопки, молнии
Возрастные категории (baby, toddler, kids, teen)

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision или Pony Diffusion V6
Название LoRA: CatVRF_Kids_Fashion_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 72
alpha: 36
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.8e-5
max_train_steps: 6800 - 9200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 650
Trigger words (основные):

catvrf kids fashion, cute children clothing, adorable kids wear, soft fabric texture, studio lighting

Дополнительные триггеры:

baby onesie, toddler dress, kids hoodie, children sneakers, cartoon print t-shirt
soft cotton fabric, pastel colors, cute animal print, comfortable kids wear

4. Структура датасета
Рекомендуемый объём: 1600–2800 изображений
Структура папок:
textkids_fashion_dataset/
├── 1_baby_clothes/
├── 2_toddler_outfits/
├── 3_kids_casual_wear/
├── 4_children_sportswear/
├── 5_girls_dresses/
├── 6_boys_sets/
├── 7_kids_shoes/
├── 8_accessories/
└── captions.txt
Требования к фото:

Чистый фон, студийное освещение
Фото на детях + flat lay
Чёткие детали (принты, текстуры, фурнитура)
Разные возрастные группы и сезоны

Примеры caption:
textcatvrf kids fashion, adorable pink baby onesie with cute bear print, soft organic cotton texture, studio lighting, high quality children clothing
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'fashion_realism'           => 0.65,
    'kids_fashion_v1'           => 0.90,     // высокий вес
    'material_texture_master_v1'=> 0.78,
    'color_grading_master_v1'   => 0.75,
];
Примеры промптов:
Детская одежда:
textcatvrf kids fashion, cute blue toddler hoodie with dinosaur print, soft fleece fabric, comfortable fit, studio product photography, bright and cheerful
Детская обувь:
textcatvrf kids fashion, adorable white children sneakers with velcro, breathable mesh and soft sole, cute design, studio lighting
Комплект:
textcatvrf kids fashion, stylish girl summer set with floral pattern, lightweight cotton fabric, vibrant colors, realistic children clothing
Negative Prompt:
textadult clothing, mature style, dark tones, scary print, low quality, blurry, deformed child clothing
6. Что должен вернуть ИИ

Полный конфиг обучения (kids_fashion_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с другими LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт текстуры, материалы и атмосферу детских игрушек:

Плюшевые игрушки (мягкие мишки, зайцы, динозавры)
Пластиковые игрушки (конструкторы, машинки, куклы)
Деревянные игрушки (экологичные, развивающие)
Обучающие игрушки, пазлы, мягкие книжки
Интерактивные игрушки с деталями (колёса, кнопки, глаза)

Этот LoRA должен подчёркивать милоту, безопасность и яркость, типичные для детских товаров.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация мягких форм и текстур)
Альтернатива: SDXL + Realistic Vision или Pony Diffusion V6
Название LoRA: CatVRF_Kids_Toys_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 68
alpha: 34
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.5e-5
max_train_steps: 6500 - 9200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 650
Trigger words (основные):

catvrf kids toy, cute children's toy, adorable plush toy, studio product photography, soft lighting

Дополнительные триггеры:

soft plush teddy bear, wooden educational toy, colorful plastic building blocks, realistic toy texture
big sparkling eyes, fluffy fur, smooth plastic surface, safe rounded edges

4. Структура датасета
Рекомендуемый объём: 1500–2600 изображений высокого качества
Структура папок:
textkids_toys_dataset/
├── 1_plush_toys/
├── 2_wooden_toys/
├── 3_plastic_toys/
├── 4_educational_toys/
├── 5_dolls_and_action_figures/
├── 6_building_blocks/
├── 7_soft_books/
└── captions.txt
Требования к фото:

Чистый белый/светлый фон
Студийное мягкое освещение
Много close-up деталей (ткань, швы, глаза, текстура дерева, пластик)
Фото на белом фоне + lifestyle (игрушка в руках ребёнка — опционально)

Примеры caption:
textcatvrf kids toy, adorable brown plush teddy bear with soft fur and big sparkling eyes, cute children's toy, studio lighting, high quality product photography
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'kids_fashion_v1'            => 0.65,
    'kids_toys_v1'               => 0.92,     // высокий вес — основной
    'material_texture_master_v1' => 0.78,
    'color_grading_master_v1'    => 0.75,
];
Примеры промптов:
Плюшевая игрушка:
textcatvrf kids toy, cute gray plush elephant with soft fluffy fur, big sparkling eyes, detailed stitching, adorable children's toy, studio lighting
Деревянная игрушка:
textcatvrf kids toy, natural wooden stacking toy with smooth finish and bright colors, educational wooden blocks, realistic wood grain, high quality
Пластиковый конструктор:
textcatvrf kids toy, colorful plastic building blocks set, glossy plastic texture, bright primary colors, safe rounded edges, studio product photography
Negative Prompt:
textscary toy, dark colors, broken toy, low quality, blurry, deformed, adult toy, creepy eyes
6. Что должен вернуть ИИ

Полный конфиг обучения (kids_toys_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании с Kids Fashion LoRA
Обновлённый TextureGenerationService.php с примерами промптов для игрушек
Рекомендации по съёмке и обучению

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт:

Детскую мебель (кроватки, комоды, столы, стульчики, шкафы, полки, игровые зоны)
Безопасный дизайн (закруглённые углы, мягкие формы, яркие цвета)
Материалы (дерево, МДФ, пластик, ткань, экокожа)
Атмосферу детской комнаты (мило, ярко, функционально, безопасно)

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Kids_Furniture_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 80
alpha: 40
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.2e-5
max_train_steps: 7500 - 10800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf kids furniture, adorable children's furniture, safe baby room, colorful kids interior, studio lighting

Дополнительные триггеры:

wooden baby crib, toddler bed with safety rails, colorful kids desk and chair, Montessori furniture
soft play mat, cute storage cabinet, rounded edges, bright pastel colors, safe design

4. Структура датасета
Рекомендуемый объём: 1700–2900 изображений
Структура папок:
textkids_furniture_dataset/
├── 1_baby_cribs_and_beds/
├── 2_toddler_furniture/
├── 3_kids_desks_and_chairs/
├── 4_storage_and_shelves/
├── 5_play_furniture/
├── 6_soft_seating_poufs/
├── 7_montessori_furniture/
└── captions.txt
Требования к фото:

Чистый фон или красивые детские комнаты
Много деталей (закруглённые углы, яркие акценты, текстуры дерева/ткани)
Фото с детьми для масштаба + flat lay

Примеры caption:
textcatvrf kids furniture, adorable white wooden baby crib with soft pastel bedding, safe rounded edges, cute children's room, studio lighting, high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.85,
    'kids_furniture_v1'            => 0.93,     // высокий вес
    'kids_toys_v1'                 => 0.60,
    'color_grading_master_v1'      => 0.78,
    'volumetric_lighting_v1'       => 0.75,
];
Примеры промптов:
Кроватка:
textcatvrf kids furniture, beautiful wooden toddler bed with safety rails and cute animal print, soft pastel colors, safe rounded design, children's room interior
Стол и стул:
textcatvrf kids furniture, colorful ergonomic children's desk and chair set, bright educational design, safe rounded corners, modern kids room
Игровой домик/шкаф:
textcatvrf kids furniture, adorable wooden playhouse storage cabinet for children, bright colors, fun and safe design, children's play area
Negative Prompt:
textadult furniture, sharp edges, dark scary colors, unsafe design, low quality, blurry, deformed furniture
6. Что должен вернуть ИИ

Полный конфиг обучения (kids_furniture_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с другими LoRA (Interior, Kids Toys, Color Grading)

### 1. Цель
Создать высококачественный специализированный LoRA для генерации детской одежды, который отлично передаёт:

Миловидность, мягкость и безопасность детских вещей
Яркие, но приятные цвета и принты
Реалистичные текстуры тканей (органический хлопок, флис, велюр, трикотаж, деним)
Возрастные особенности (baby 0-2, toddler 2-5, kids 5-12)
Детали: аппликации, вышивки, рюши, кнопки, молнии, удобные крои

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация мягких тканей и милых форм)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Kids_Clothing_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 68
alpha: 34
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.8e-5
max_train_steps: 6800 - 9500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 650
Trigger words (основные):

catvrf kids clothing, cute children's outfit, adorable baby clothes, soft fabric texture, studio lighting

Дополнительные триггеры:

baby onesie, toddler hoodie, kids t-shirt with animal print, comfortable cotton dress
pastel colors, cute cartoon print, soft fleece, organic cotton, cozy children's wear

4. Структура датасета
Рекомендуемый объём: 1800–3000 изображений
Структура папок:
textkids_clothing_dataset/
├── 1_baby_clothes_0-2/
├── 2_toddler_outfits_2-5/
├── 3_kids_casual_5-12/
├── 4_girls_dresses/
├── 5_boys_sets/
├── 6_seasonal_kids_wear/
├── 7_sport_kids_clothing/
└── captions.txt
Требования к фото:

Чистый фон + lifestyle (на ребёнке)
Чёткие детали (принты, текстура ткани, фурнитура)
Разные сезоны и стили

Примеры caption:
textcatvrf kids clothing, adorable pink baby onesie with cute bunny print, soft organic cotton fabric, studio lighting, high quality children's wear
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'kids_fashion_v1'           => 0.88,   // основной
    'material_texture_master_v1'=> 0.75,
    'color_grading_master_v1'   => 0.78,
    'kids_toys_v1'              => 0.45,   // если есть игрушки на фото
];
Примеры промптов:
Боди для малышей:
textcatvrf kids clothing, cute white baby bodysuit with colorful animal print, soft breathable cotton, studio product photography, adorable children's wear
Комплект для девочки:
textcatvrf kids clothing, beautiful pink floral dress for toddler girl, soft fabric with delicate details, cute and comfortable, bright studio lighting
Одежда для мальчика:
textcatvrf kids clothing, cool blue hoodie and pants set for kids, comfortable sport style, realistic fabric texture, high quality
Negative Prompt:
textadult clothing, sexy style, dark scary colors, low quality, blurry, deformed child clothing, inappropriate design
6. Что должен вернуть ИИ

Полный конфиг обучения (kids_clothing_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по возрастным группам и стилям

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт атмосферу и детали спален:

Кровати, изголовья, постельное бельё, покрывала, подушки
Спальные гарнитуры, прикроватные тумбочки, комоды, шкафы
Мягкое уютное освещение (ночники, бра, потолочные светильники)
Текстиль, ковры, шторы, балдахины
Разные стили: минимализм, сканди, классика, luxury, детская спальня

Этот LoRA будет идеально работать вместе с Interior, Lighting, Volumetric Lighting и Wall/Flooring LoRA.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая атмосфера и мягкость)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Bedroom_Interior_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 85
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5e-5
max_train_steps: 8200 - 11800
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf bedroom interior, cozy bedroom, warm soft lighting, comfortable bed, luxury bedroom atmosphere

Дополнительные триггеры:

king size bed with soft bedding, minimalist scandinavian bedroom, luxury hotel suite bedroom
soft night lighting, canopy bed, reading nook, warm neutral tones, peaceful atmosphere

4. Структура датасета
Рекомендуемый объём: 1800–3000 изображений
Структура папок:
textbedroom_dataset/
├── 1_cozy_minimal_bedroom/
├── 2_luxury_master_bedroom/
├── 3_scandinavian_bedroom/
├── 4_kids_bedroom/
├── 5_hotel_style_bedroom/
├── 6_warm_tones_bedroom/
├── 7_canopy_bed/
└── captions.txt
Требования к фото:

Красивые интерьерные фотографии спален
Разные ракурсы: общий вид, кровать крупно, детали (текстиль, освещение)
Мягкое, уютное освещение

Примеры caption:
textcatvrf bedroom interior, cozy modern bedroom with large comfortable bed, soft warm lighting, neutral tones, luxury bedding, peaceful atmosphere, high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.85,
    'bedroom_interior_v1'          => 0.94,     // высокий вес
    'volumetric_lighting_v1'       => 0.82,
    'shadows_lighting_master_v1'   => 0.78,
    'wall_coverings_v1'            => 0.70,
    'flooring_materials_v1'        => 0.68,
];
Примеры промптов:
Современная спальня:
textcatvrf bedroom interior, cozy minimalist bedroom with large bed and soft neutral bedding, warm ambient lighting, peaceful atmosphere, realistic interior design
Роскошная спальня:
textcatvrf bedroom interior, luxury master bedroom with king size bed, elegant canopy and rich textiles, soft dramatic lighting, high-end hotel style
Детская спальня:
textcatvrf bedroom interior, adorable children's bedroom with cute bed and soft pastel colors, warm gentle lighting, playful yet cozy atmosphere
Negative Prompt:
textempty room, harsh lighting, hospital style, dark scary bedroom, low quality, blurry, bad composition
6. Что должен вернуть ИИ

Полный конфиг обучения (bedroom_interior_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для спален
Рекомендации по комбинации с другими LoRA (Lighting, Volumetric, Wall, Flooring)

### 1. Цель
Создать высококачественный специализированный LoRA для генерации реалистичных и атмосферных гостиных (living rooms / lounges), который отлично передаёт:

Мягкую мебель (диваны, кресла, пуфы)
Журнальные столы, ТВ-зоны, стеллажи, консоли
Ковры, шторы, декоративный текстиль
Разные стили: современный минимализм, сканди, классика, лофт, luxury
Уютную и продающую атмосферу жилых и коммерческих пространств

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая атмосфера и детализация пространства)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Living_Room_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 88
alpha: 44
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 8500 - 12500
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf living room, cozy modern living room, comfortable lounge interior, warm inviting atmosphere

Дополнительные триггеры:

large sectional sofa, minimalist living room, luxury living room with marble coffee table
soft ambient lighting, layered lighting, cozy rug, open plan living room

4. Структура датасета
Рекомендуемый объём: 1900–3200 изображений
Структура папок:
textliving_room_dataset/
├── 1_modern_minimal_living/
├── 2_cozy_scandi_living/
├── 3_luxury_classic_living/
├── 4_loft_industrial_living/
├── 5_family_living_room/
├── 6_open_plan_living/
├── 7_tv_wall_living/
└── captions.txt
Требования к фото:

Красивые интерьерные фотографии гостиных
Разные ракурсы: общий план, зона дивана, ТВ-зона, детали
Разное освещение (дневное, вечернее, тёплое)

Примеры caption:
textcatvrf living room, cozy modern living room with large beige sectional sofa, soft warm lighting, wooden coffee table, stylish rug, inviting atmosphere, high quality interior
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.85,
    'living_room_v1'               => 0.94,     // высокий вес
    'volumetric_lighting_v1'       => 0.82,
    'shadows_lighting_master_v1'   => 0.80,
    'wall_coverings_v1'            => 0.72,
    'flooring_materials_v1'        => 0.75,
];
Примеры промптов:
Современная гостиная:
textcatvrf living room, spacious modern living room with comfortable gray sectional sofa, wooden coffee table, large windows with natural light, stylish rug, cozy and elegant atmosphere
Роскошная гостиная:
textcatvrf living room, luxury classic living room with velvet sofas, marble coffee table, warm ambient lighting, elegant chandelier, high-end interior design
Семейная гостиная:
textcatvrf living room, cozy family living room with large sofa, colorful cushions, soft lighting, children's toys neatly arranged, warm and inviting home atmosphere
Negative Prompt:
textempty room, cold atmosphere, harsh lighting, hospital style, low quality, blurry, bad composition
6. Что должен вернуть ИИ

Полный конфиг обучения (living_room_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для гостиных
Рекомендации по комбинации с другими LoRA (Lighting, Volumetric, Wall, Flooring)

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт реалистичные и атмосферные кухни:

Современные кухни (минимализм, hi-tech)
Классические кухни (дерево, резьба)
Индустриальные и лофт-кухни
Кухни в сканди и прованс стилях
Кухонные острова, фасады, столешницы, фартуки, бытовая техника

Этот LoRA будет особенно полезен для визуализации кухонь в салонах, магазинах мебели и жилых интерьерах.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация материалов и освещения кухни)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Kitchen_Interior_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 90
alpha: 45
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.7e-5
max_train_steps: 8800 - 13000
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf kitchen interior, modern kitchen, detailed kitchen design, realistic kitchen materials

Дополнительные триггеры:

luxury kitchen with island, minimalist white kitchen, wooden classic kitchen, industrial loft kitchen
quartz countertop, marble backsplash, stainless steel appliances, warm pendant lighting

4. Структура датасета
Рекомендуемый объём: 1900–3200 изображений
Структура папок:
textkitchen_dataset/
├── 1_modern_minimal_kitchen/
├── 2_luxury_kitchen_with_island/
├── 3_classic_wooden_kitchen/
├── 4_industrial_loft_kitchen/
├── 5_scandi_kitchen/
├── 6_small_apartment_kitchen/
├── 7_white_kitchen_with_marble/
└── captions.txt
Требования к фото:

Высококачественные интерьерные фотографии кухонь
Разные ракурсы: общий вид, зона готовки, остров, столешница крупно
Видимые материалы (фасады, столешницы, фартук, пол)

Примеры caption:
textcatvrf kitchen interior, modern luxury kitchen with large island and white cabinets, marble countertop, warm pendant lighting, realistic materials, high quality interior design
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.85,
    'kitchen_interior_v1'          => 0.95,     // самый высокий вес
    'wall_coverings_v1'            => 0.72,
    'flooring_materials_v1'        => 0.78,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Современная кухня:
textcatvrf kitchen interior, sleek modern kitchen with white matte cabinets and quartz countertop, stainless steel appliances, warm ambient lighting, clean minimalist design, realistic
Классическая кухня:
textcatvrf kitchen interior, elegant classic wooden kitchen with natural oak cabinets, marble backsplash, warm cozy lighting, traditional luxury style
Маленькая кухня:
textcatvrf kitchen interior, functional small apartment kitchen with smart storage, light colors, soft natural lighting, realistic and cozy
Negative Prompt:
textempty kitchen, dirty kitchen, bad lighting, cartoonish, low detail, unrealistic materials, cluttered mess
6. Что должен вернуть ИИ

Полный конфиг обучения (kitchen_interior_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для кухонь
Рекомендации по комбинации с другими LoRA (Lighting, Volumetric, Wall, Flooring)

### 1. Цель
Создать высококачественный специализированный LoRA для генерации реалистичных и функциональных прихожих (входных зон, холлов, коридоров), который отлично передаёт:

Мебель для прихожей (шкафы, комоды, вешалки, банкетки, зеркала)
Освещение (бра, потолочные светильники, LED-подсветка)
Напольные и стеновые покрытия в зоне входа
Декор (коврики, подставки для обуви, органайзеры)
Разные стили: минимализм, классика, сканди, лофт, luxury

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Entryway_Hallway_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 82
alpha: 41
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.1e-5
max_train_steps: 7600 - 11200
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf entryway, modern hallway interior, functional entrance hall, cozy foyer, detailed hallway design

Дополнительные триггеры:

minimalist entryway with built-in closet, luxury foyer with mirror and console table
scandi hallway with bench and hooks, practical shoe storage area, warm welcoming atmosphere

4. Структура датасета
Рекомендуемый объём: 1600–2800 изображений
Структура папок:
textentryway_dataset/
├── 1_minimal_hallway/
├── 2_luxury_foyer/
├── 3_scandi_entryway/
├── 4_practical_small_hallway/
├── 5_modern_closet_system/
├── 6_warm_classic_foyer/
└── captions.txt
Требования к фото:

Ракурсы от входной двери + общий вид
Чётко видно хранение (шкафы, вешалки, полки для обуви)
Разное освещение (дневное, вечернее, акцентное)

Примеры caption:
textcatvrf entryway, modern minimalist hallway with built-in wardrobe and large mirror, warm lighting, functional and stylish entrance, high quality interior
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.85,
    'entryway_hallway_v1'          => 0.94,     // высокий вес
    'flooring_materials_v1'        => 0.78,
    'wall_coverings_v1'            => 0.75,
    'volumetric_lighting_v1'       => 0.80,
];
Примеры промптов:
Минималистичная прихожая:
textcatvrf entryway, clean modern minimalist hallway with white built-in closet and large mirror, soft warm lighting, functional design, realistic interior
Роскошная прихожая:
textcatvrf entryway, elegant luxury foyer with marble console table and gold accents, dramatic lighting, sophisticated entrance hall, high-end design
Маленькая практичная прихожая:
textcatvrf entryway, practical small apartment hallway with shoe storage bench and coat hooks, cozy lighting, smart organization, realistic
Negative Prompt:
textempty hallway, cluttered mess, bad lighting, hospital style, low quality, blurry, unrealistic proportions
6. Что должен вернуть ИИ

Полный конфиг обучения (entryway_hallway_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для прихожих
Рекомендации по комбинации с другими LoRA (Interior, Lighting, Flooring, Wall)

### 1. Цель
Создать высококачественный специализированный LoRA для генерации реалистичных и атмосферных ванных комнат, который отлично передаёт:

Современные и luxury ванные (минимализм, spa-стиль)
Классические и неоклассические ванные
Материалы: керамогранит, мрамор, стекло, дерево, металл (хром, матовый чёрный)
Сантехнику (ванны, душевые, раковины, унитазы, смесители)
Освещение (бра, потолочные светильники, зеркала с подсветкой)
Атмосферу (спа, уют, чистота, релаксация)

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (отлично работает с мокрыми поверхностями и отражениями)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Bathroom_Interior_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 86
alpha: 43
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.9e-5
max_train_steps: 8400 - 12200
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf bathroom interior, modern luxury bathroom, spa-like bathroom, detailed sanitary ware

Дополнительные триггеры:

freestanding bathtub, walk-in shower, marble bathroom, floating vanity, large mirror with lighting
warm neutral tones, cool minimalist bathroom, wet room style, soft ambient lighting

4. Структура датасета
Рекомендуемый объём: 1800–3100 изображений
Структура папок:
textbathroom_dataset/
├── 1_modern_minimal_bathroom/
├── 2_luxury_marble_bathroom/
├── 3_spa_style_bathroom/
├── 4_small_apartment_bathroom/
├── 5_black_and_white_bathroom/
├── 6_wood_and_stone_bathroom/
├── 7_walk_in_shower/
└── captions.txt
Требования к фото:

Высококачественные интерьерные снимки ванных комнат
Разные ракурсы: общий вид, зона ванны/душа, раковина крупно
Видимые материалы, отражения, блики на мокрых поверхностях

Примеры caption:
textcatvrf bathroom interior, luxurious modern bathroom with freestanding white bathtub and marble walls, soft warm lighting, large mirror, spa atmosphere, realistic high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.85,
    'bathroom_interior_v1'         => 0.94,     // высокий вес
    'wall_coverings_v1'            => 0.78,
    'flooring_materials_v1'        => 0.80,
    'reflections_specular_v1'      => 0.85,     // особенно важен для мокрых поверхностей
    'volumetric_lighting_v1'       => 0.78,
];
Примеры промптов:
Современная ванная:
textcatvrf bathroom interior, sleek modern bathroom with walk-in shower and floating vanity, large format tiles, soft natural lighting, minimalist design, realistic
Люксовая ванная:
textcatvrf bathroom interior, luxury spa bathroom with freestanding marble bathtub, gold accents, warm ambient lighting, elegant atmosphere, high-end interior
Маленькая ванная:
textcatvrf bathroom interior, functional small apartment bathroom with smart storage, light colors, clean design, soft lighting, realistic
Negative Prompt:
textdirty bathroom, mold, broken tiles, bad lighting, cluttered, low quality, cartoonish, unrealistic reflections
6. Что должен вернуть ИИ

Полный конфиг обучения (bathroom_interior_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для ванных комнат
Рекомендации по комбинации с Reflections, Lighting и Wall/Flooring LoRA

### 1. Цель
Создать высококачественный специализированный LoRA для генерации реалистичных фасадов зданий, который отлично передаёт:

Современные фасады (минимализм, стекло, бетон, композит)
Классические фасады (кирпич, штукатурка, лепнина)
Деревянные, каркасные, вентилируемые фасады
Фасады коммерческих объектов (салоны красоты, ветклиники, магазины одежды)
Разные материалы: кирпич, клинкер, сайдинг, HPL-панели, стекло, камень, дерево

Этот LoRA будет использоваться для генерации экстерьеров зданий, визуализаций фасадов магазинов/клиник и общего вида объектов на маркетплейсе.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с архитектурой и материалами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Building_Facade_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92                    # высокий rank — фасады требуют детализации материалов и архитектуры
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.6e-5
max_train_steps: 9200 - 13800
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf building facade, realistic exterior architecture, detailed building front, professional architectural photography

Дополнительные триггеры:

modern glass facade, brick building exterior, wooden cladded facade, minimalist commercial front
luxury beauty salon facade, veterinary clinic exterior, fashion store storefront

4. Структура датасета
Рекомендуемый объём: 2000–3500 изображений
Структура папок:
textfacade_dataset/
├── 1_modern_minimal_facade/
├── 2_brick_classic_facade/
├── 3_glass_commercial_facade/
├── 4_wooden_cladding/
├── 5_stone_natural_facade/
├── 6_beauty_salon_exterior/
├── 7_vet_clinic_facade/
├── 8_fashion_storefront/
└── captions.txt
Требования к фото:

Фасады зданий в хорошем освещении (дневное, золотой час)
Разные ракурсы: фронтальный, 3/4, детали (окна, двери, отделка)
Реальные коммерческие и жилые объекты

Примеры caption:
textcatvrf building facade, modern minimalist commercial building with large glass windows and white panels, clean architectural lines, natural daylight, realistic exterior
5. Оптимальные настройки использования
В TextureGenerationService / ExteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.45,   // низкий вес
    'building_facade_v1'           => 0.95,   // доминирующий
    'construction_materials_v1'    => 0.82,
    'shadows_lighting_master_v1'   => 0.78,
    'volumetric_lighting_v1'       => 0.70,
];
Примеры промптов:
Современный фасад:
textcatvrf building facade, sleek modern beauty salon exterior with large glass windows and white composite panels, clean minimalist design, natural daylight, realistic architectural photography
Классический фасад:
textcatvrf building facade, elegant brick veterinary clinic building with wooden entrance and large windows, warm natural lighting, traditional yet modern exterior
Магазин одежды:
textcatvrf building facade, stylish fashion store storefront with large display windows and elegant signage, inviting entrance, realistic daylight
Negative Prompt:
textdeformed building, bad perspective, low quality facade, cartoonish, blurry architecture, unrealistic proportions
6. Что должен вернуть ИИ

Полный конфиг обучения (building_facade_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для фасадов
Рекомендации по комбинации с другими LoRA (Construction Materials, Lighting, Interior)

### 1. Цель
Создать универсальный, высококачественный базовый LoRA для интерьеров зданий в целом.
Он должен хорошо работать как основа для всех типов помещений (гостиные, кухни, спальни, ванные, прихожие, офисы, салоны красоты, ветклиники, магазины и т.д.).
Этот LoRA станет фундаментом для остальных интерьерных LoRA (Kitchen, Bedroom, Bathroom и т.д.).
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучший баланс архитектуры, пространства и материалов)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_General_Interior_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 96                    # высокий rank — интерьеры требуют детализации пространства и материалов
alpha: 48
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.3e-5
max_train_steps: 10500 - 14800
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf interior, realistic building interior, detailed architectural interior, professional interior design, studio lighting

Дополнительные триггеры:

modern minimalist interior, luxury residential interior, commercial interior design, warm cozy atmosphere
open space layout, natural daylight, layered lighting, realistic materials and textures

4. Структура датасета
Рекомендуемый объём: 2800–4500 изображений (больше — лучше)
Структура папок:
textgeneral_interior_dataset/
├── 1_modern_minimal_interiors/
├── 2_luxury_residential/
├── 3_commercial_interiors/
├── 4_scandi_style/
├── 5_loft_industrial/
├── 6_warm_cozy_homes/
├── 7_beauty_salon_interior/
├── 8_vet_clinic_interior/
└── captions.txt
Требования к фото:

Высококачественные профессиональные интерьерные фотографии
Разные типы зданий и помещений
Хорошее освещение, видно материалы стен, пола, потолка, мебели

Примеры caption:
textcatvrf interior, spacious modern minimalist living room with clean lines and natural materials, soft natural daylight, realistic architectural interior, high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'general_interior_v1'          => 0.92,     // базовый, высокий вес
    'kitchen_interior_v1'          => 0.75,
    'bedroom_interior_v1'          => 0.70,
    'bathroom_interior_v1'         => 0.68,
    'living_room_v1'               => 0.72,
    'volumetric_lighting_v1'       => 0.85,
    'shadows_lighting_master_v1'   => 0.80,
];
Примеры промптов:
Общий интерьер:
textcatvrf interior, bright modern open-plan apartment interior, natural daylight, warm wooden floors, clean minimalist design, realistic architectural photography
Коммерческое помещение:
textcatvrf interior, professional beauty salon interior with elegant design and soft lighting, clean surfaces, welcoming atmosphere, high quality
Negative Prompt:
textempty room, bad composition, low quality, blurry, cartoonish, unrealistic proportions, deformed architecture
6. Что должен вернуть ИИ

Полный конфиг обучения (general_interior_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании с room-specific LoRA
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по использованию как базового LoRA

### 1. Цель
Создать высококачественный специализированный LoRA для генерации реалистичных придомовых участков, дворов, ландшафтного дизайна и территорий вокруг зданий.
LoRA должен отлично работать с:

Частными домами, коттеджами, таунхаусами
Придомовыми территориями коммерческих объектов (салоны, клиники, магазины)
Ландшафтным дизайном (газоны, клумбы, дорожки, альпийские горки, водоёмы)
Сезонностью (лето, осень, зима, весна)
Разными стилями (современный минимализм, английский сад, японский минимализм, русский усадебный стиль)

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_House_Territory_Landscape_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 90
alpha: 45
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.6e-5
max_train_steps: 9200 - 13500
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf house territory, realistic landscape design, beautiful backyard, detailed yard around house

Дополнительные триггеры:

modern landscaped courtyard, cozy private garden, english style backyard, minimalist front yard
green lawn with flower beds, stone pathway, wooden terrace, outdoor seating area

4. Структура датасета
Рекомендуемый объём: 2000–3500 изображений
Структура папок:
texthouse_territory_dataset/
├── 1_modern_minimal_yard/
├── 2_cozy_english_garden/
├── 3_luxury_landscape/
├── 4_small_courtyard/
├── 5_front_yard_design/
├── 6_backyard_with_terrace/
├── 7_winter_snowy_yard/
├── 8_autumn_garden/
└── captions.txt
Требования к фото:

Общие планы участка + детальные зоны (дорожки, клумбы, зона отдыха)
Разные сезоны и время суток
Реалистичные жилые и коммерческие объекты

Примеры caption:
textcatvrf house territory, beautiful modern landscaped backyard with green lawn, stone pathway and wooden terrace, natural daylight, realistic landscape design
5. Оптимальные настройки использования
В TextureGenerationService / ExteriorGenerationService:
PHP$loras = [
    'building_facade_v1'           => 0.88,
    'house_territory_landscape_v1' => 0.94,     // высокий вес
    'construction_materials_v1'    => 0.75,
    'volumetric_lighting_v1'       => 0.82,
    'shadows_lighting_master_v1'   => 0.78,
];
Примеры промптов:
Современный участок:
textcatvrf house territory, modern minimalist backyard with geometric lawn and concrete pathway, stylish outdoor furniture, natural daylight, realistic landscape
Классический сад:
textcatvrf house territory, cozy english style garden around private house, flower beds and green lawn, wooden bench, warm evening lighting, realistic
Придомовая территория клиники/салона:
textcatvrf house territory, well-maintained front yard of beauty salon, decorative plants and stone path, welcoming entrance area, professional landscape design
Negative Prompt:
textempty yard, dead grass, bad landscaping, cluttered messy territory, low quality, unrealistic plants, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (house_territory_landscape_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для придомовых участков
Рекомендации по комбинации с Building Facade, Interior и Lighting LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично контролирует уличное освещение, ночные сцены и световые эффекты на открытых пространствах:

Фонари, уличные светильники, торшеры, прожекторы
Фасадная и ландшафтная подсветка
Ночное освещение зданий, придомовых территорий, парковок, входных зон
Атмосферные эффекты (световые конусы, блики на мокром асфальте, мягкие тени, блендинг света)

Этот LoRA особенно важен для визуализации фасадов, придомовых участков, входных групп и вечерних экстерьеров.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с ночным светом и отражениями)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Outdoor_Street_Lighting_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 88
alpha: 44
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.7e-5
max_train_steps: 8500 - 12800
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf outdoor lighting, realistic street lighting, night scene with beautiful illumination, detailed exterior lighting

Дополнительные триггеры:

warm street lamp glow, modern LED facade lighting, dramatic night lighting, light pools on pavement
wet asphalt reflections, cozy entrance lighting, landscape spotlights, golden hour to night transition

4. Структура датасета
Рекомендуемый объём: 1700–2900 изображений
Структура папок:
textoutdoor_lighting_dataset/
├── 1_street_lamps_night/
├── 2_facade_lighting/
├── 3_landscape_spotlights/
├── 4_modern_outdoor_lighting/
├── 5_warm_entrance_lighting/
├── 6_wet_surface_reflections/
├── 7_commercial_building_night/
└── captions.txt
Требования к фото:

Ночные и вечерние снимки с акцентом на источники света
Видимые световые конусы, блики, отражения на мокрых поверхностях
Разные типы освещения (тёплое/холодное, акцентное, заливающее)

Примеры caption:
textcatvrf outdoor lighting, modern street with warm glowing lanterns at night, realistic light pools on pavement, soft volumetric glow, detailed night scene
5. Оптимальные настройки использования
В TextureGenerationService / ExteriorGenerationService:
PHP$loras = [
    'building_facade_v1'              => 0.88,
    'house_territory_landscape_v1'    => 0.82,
    'outdoor_street_lighting_v1'      => 0.94,     // высокий вес
    'reflections_specular_v1'         => 0.80,
    'volumetric_lighting_v1'          => 0.85,
];
Примеры промптов:
Уличное освещение:
textcatvrf outdoor lighting, modern residential street at night with warm LED street lamps, realistic light cones and reflections on wet asphalt, cozy atmosphere
Фасадная подсветка:
textcatvrf outdoor lighting, beautiful illuminated building facade at night with accent lighting, dramatic yet warm glow, realistic architectural night scene
Придомовая территория:
textcatvrf outdoor lighting, cozy private house territory at night with garden spotlights and entrance lighting, soft volumetric light, realistic landscape
Negative Prompt:
textdaytime scene, flat lighting, no light sources, overexposed, underexposed, bad night lighting, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (outdoor_street_lighting_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для уличного освещения
Рекомендации по комбинации с Building Facade, Landscape и Reflections LoRA

### 1. Цель
Создать мощный, универсальный LoRA, который качественно и реалистично генерирует все основные погодные эффекты для экстерьеров, фасадов, придомовых участков и интерьеров с видом из окна.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Weather_Effects_V2
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9800 - 14200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf weather effect, realistic weather conditions, atmospheric scene, detailed environmental effects

Дополнительные триггеры по погоде:

Дождь: heavy rain, light drizzle, raindrops on surface, wet reflections
Снег: falling snow, thick snowfall, snow covered ground, blizzard
Туман/дымка: dense fog, morning mist, atmospheric haze
Солнечный свет: golden hour sunlight, bright midday sun, dramatic sun rays
Гроза: thunderstorm, lightning, dark stormy sky
Ветер: windy day, blowing leaves, moving grass and trees

4. Структура датасета
Рекомендуемый объём: 2200–3800 изображений
Структура папок:
textweather_effects_dataset/
├── 1_rain_variations/
├── 2_snow_conditions/
├── 3_fog_and_mist/
├── 4_golden_hour_and_sunlight/
├── 5_storm_and_lightning/
├── 6_windy_and_autumn/
├── 7_spring_and_summer/
└── captions.txt
Примеры caption:
textcatvrf weather effect, realistic heavy rain on modern building facade, wet surfaces with strong reflections, dramatic atmosphere, high quality
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'building_facade_v1'              => 0.82,
    'house_territory_landscape_v1'    => 0.85,
    'weather_effects_v2'              => 0.96,     // доминирующий
    'volumetric_lighting_v1'          => 0.88,
    'reflections_specular_v1'         => 0.82,
];
Примеры промптов:
Дождь:
textcatvrf weather effect, heavy rain on luxury beauty salon facade, wet pavement with realistic reflections, dramatic yet atmospheric scene
Снег:
textcatvrf weather effect, gentle snowfall covering veterinary clinic territory, soft snow on roof and trees, warm window lights, peaceful winter mood
Golden Hour:
textcatvrf weather effect, beautiful golden hour sunlight on fashion store facade, warm glowing light and long soft shadows, realistic
Туман:
textcatvrf weather effect, thick morning fog around house territory, soft diffused light, mysterious and calm atmosphere
Negative Prompt:
textdry scene, no weather, fake rain, cartoon snow, bad lighting, low quality, unrealistic effects
6. Что должен вернуть ИИ

Полный конфиг обучения (weather_effects_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с другими LoRA (Facade, Landscape, Lighting, Reflections)

### 1. Цель
Создать специализированный LoRA, который качественно и реалистично генерирует сцены полива придомовых участков, включая:

Автоматические дождеватели (роторные, статические, импульсные)
Капельный полив, ленточный полив
Шланги, поливочные пистолеты, бочки, насосы
Мокрые поверхности после полива (блестящая трава, отражения на дорожках)
Влажность воздуха, капли воды, радуга от брызг
Разные времена суток и сезоны (утренний полив, вечерний, лето, весна)

Этот LoRA будет работать вместе с House Territory Landscape и Weather Effects LoRA.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Irrigation_Watering_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 78
alpha: 39
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.2e-5
max_train_steps: 7200 - 10500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf irrigation, realistic garden watering, automatic sprinkler system, detailed water spray

Дополнительные триггеры:

rotating sprinkler, drip irrigation system, wet lawn after watering, water droplets on grass
evening garden watering, morning mist from sprinklers, realistic water reflections

4. Структура датасета
Рекомендуемый объём: 1400–2600 изображений
Структура папок:
textirrigation_dataset/
├── 1_rotating_sprinklers/
├── 2_drip_irrigation/
├── 3_hose_watering/
├── 4_wet_grass_and_plants/
├── 5_automatic_systems/
├── 6_water_droplets_details/
├── 7_rain_like_spraying/
└── captions.txt
Требования к фото:

Реалистичные сцены полива (вода в движении, мокрые поверхности)
Макро-снимки капель + общие планы
Разное время суток (утро, вечер, золотой час)

Примеры caption:
textcatvrf irrigation, realistic garden watering with rotating sprinklers, fresh wet green lawn, water droplets flying, natural daylight, detailed scene
5. Оптимальные настройки использования
В TextureGenerationService / ExteriorGenerationService:
PHP$loras = [
    'house_territory_landscape_v1' => 0.88,
    'irrigation_watering_v1'       => 0.93,     // высокий вес
    'weather_effects_v2'           => 0.78,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Автоматический полив:
textcatvrf irrigation, beautiful garden with active rotating sprinklers watering green lawn, fresh water droplets in air, realistic wet grass reflections, sunny morning
Капельный полив:
textcatvrf irrigation, precise drip irrigation system on flower beds, slow water drops, moist soil, realistic garden maintenance scene
Ручной полив:
textcatvrf irrigation, person watering garden with hose and spray gun, realistic water stream, wet plants and soil, natural daylight
Negative Prompt:
textdry garden, no water, dead grass, unrealistic water, bad droplets, cartoonish, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (irrigation_watering_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Landscape и Weather Effects LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт уличную мебель и её взаимодействие с окружающей средой:

Садовые и террасные наборы (столы, стулья, диваны, кресла)
Шезлонги, качели, гамаки, пуфы
Материалы: дерево (тик, сосна), ротанг, алюминий, искусственный ротанг, текстиль, металл
Подушки, чехлы, зонты, садовые светильники
Разные стили: современный минимализм, классика, бохо, лофт, прованс

Этот LoRA будет работать совместно с House Territory Landscape, Outdoor Lighting и Weather Effects.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Outdoor_Furniture_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 78
alpha: 39
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.3e-5
max_train_steps: 7200 - 10800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf outdoor furniture, realistic garden furniture, comfortable terrace set, detailed outdoor seating

Дополнительные триггеры:

teak wooden dining set, rattan lounge chair, modern aluminum patio furniture, outdoor sofa with cushions
sun lounger by pool, hanging egg chair, garden swing, elegant bistro set

4. Структура датасета
Рекомендуемый объём: 1600–2800 изображений
Структура папок:
textoutdoor_furniture_dataset/
├── 1_garden_dining_sets/
├── 2_lounge_chairs_and_sofas/
├── 3_sun_loungers/
├── 4_hanging_chairs_and_swings/
├── 5_rattan_and_wicker/
├── 6_modern_aluminum_furniture/
├── 7_wooden_outdoor_sets/
└── captions.txt
Требования к фото:

Фото на открытом воздухе (терраса, сад, у бассейна)
Разные ракурсы и освещение (дневное, золотой час, вечернее)
Чёткие детали материалов и текстур

Примеры caption:
textcatvrf outdoor furniture, luxurious teak wooden garden dining set with comfortable cushions, realistic wood texture, sunny terrace, high quality product photography
5. Оптимальные настройки использования
В TextureGenerationService / ExteriorGenerationService:
PHP$loras = [
    'house_territory_landscape_v1' => 0.85,
    'outdoor_furniture_v1'         => 0.93,     // высокий вес
    'material_texture_master_v1'   => 0.80,
    'volumetric_lighting_v1'       => 0.82,
    'weather_effects_v2'           => 0.65,
];
Примеры промптов:
Обеденная группа:
textcatvrf outdoor furniture, elegant outdoor dining set with teak table and chairs, soft cushions, realistic wood and fabric texture, sunny garden terrace
Зона отдыха:
textcatvrf outdoor furniture, comfortable rattan lounge sofa and armchairs with large pillows, cozy patio setting, warm afternoon lighting, realistic
У бассейна:
textcatvrf outdoor furniture, modern sun loungers by swimming pool, sleek design with soft mattresses, bright daylight, luxury resort style
Negative Prompt:
textindoor furniture, broken furniture, cheap plastic look, bad lighting, low quality, deformed objects, unrealistic scale
6. Что должен вернуть ИИ

Полный конфиг обучения (outdoor_furniture_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Landscape, Lighting и Weather Effects LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично контролирует садовое и ландшафтное освещение:

Декоративная подсветка растений, деревьев, клумб, дорожек
Функциональное освещение (подсветка зон отдыха, входа, парковки)
Разные типы светильников (наземные, настенные, столбиковые, гирлянды, прожекторы)
Эффекты: мягкое рассеянное освещение, акцентные лучи, световые конусы, отражения в воде
Атмосферу вечернего сада, подсветку в сумерках и ночью

Этот LoRA будет работать совместно с House Territory Landscape, Outdoor Lighting и Weather Effects.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с ночным и декоративным освещением)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Garden_Landscape_Lighting_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 85
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5e-5
max_train_steps: 7800 - 11800
resolution: 1024x1024 или 1280x720
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf garden lighting, landscape illumination, beautiful outdoor night lighting, decorative garden lights

Дополнительные триггеры:

soft path lighting, uplighting trees, warm garden lanterns, LED strip lighting on terrace
dramatic landscape spotlights, fairy lights in garden, cozy evening garden atmosphere

4. Структура датасета
Рекомендуемый объём: 1600–2800 изображений
Структура папок:
textgarden_lighting_dataset/
├── 1_path_and_walkway_lighting/
├── 2_tree_uplighting/
├── 3_terrace_and_patio_lights/
├── 4_decorative_garden_lanterns/
├── 5_fairy_string_lights/
├── 6_spotlight_accents/
├── 7_evening_garden_ambience/
└── captions.txt
Требования к фото:

Вечерние и ночные снимки с акцентом на источники света
Видимые световые эффекты (лучи, блики, мягкие тени)
Разные типы садов и ландшафтов

Примеры caption:
textcatvrf garden lighting, beautiful evening garden with warm path lights and tree uplighting, soft volumetric glow, cozy and magical atmosphere, realistic
5. Оптимальные настройки использования
В TextureGenerationService / ExteriorGenerationService:
PHP$loras = [
    'house_territory_landscape_v1'   => 0.88,
    'garden_landscape_lighting_v1'   => 0.94,     // высокий вес
    'outdoor_street_lighting_v1'     => 0.75,
    'volumetric_lighting_v1'         => 0.85,
    'weather_effects_v2'             => 0.65,
];
Примеры промптов:
Дорожки и акценты:
textcatvrf garden lighting, beautiful backyard at night with warm LED path lights and tree uplighting, soft glow on plants, cozy evening atmosphere, realistic
Терраса:
textcatvrf garden lighting, elegant terrace with string lights and lanterns, warm inviting illumination, comfortable outdoor seating, magical night scene
Ландшафтная подсветка:
textcatvrf garden lighting, dramatic landscape lighting highlighting trees and flower beds, soft volumetric light rays, peaceful garden at dusk
Negative Prompt:
textdaytime scene, no lights, flat lighting, harsh shadows, low quality, unrealistic glow, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (garden_landscape_lighting_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Landscape и Outdoor Lighting LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт водные элементы в ландшафте и интерьерах:

Бассейны (открытые, infinity, с подсветкой)
Фонтаны, водопады, каскады
Пруды, ручьи, декоративные водоёмы
Мокрые поверхности, отражения воды, блики
Капли, рябь, движение воды
Взаимодействие воды с освещением (днём, на закате, ночью)

Этот LoRA будет идеально работать вместе с House Territory Landscape, Garden Lighting и Reflections LoRA.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с отражениями и прозрачностью воды)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Water_Elements_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 85
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 7800 - 11800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf water element, realistic water feature, detailed pool and fountain, beautiful water reflections

Дополнительные триггеры:

infinity pool, cascading waterfall, garden pond with lily pads, illuminated fountain at night
rippling water surface, realistic water caustics, wet stone reflections, calm pond

4. Структура датасета
Рекомендуемый объём: 1600–2900 изображений
Структура папок:
textwater_elements_dataset/
├── 1_infinity_pools/
├── 2_garden_ponds_and_streams/
├── 3_fountains_and_cascades/
├── 4_water_features_with_lighting/
├── 5_wet_surfaces_and_reflections/
├── 6_night_illuminated_water/
├── 7_natural_water_bodies/
└── captions.txt
Требования к фото:

Высокое качество воды (прозрачность, отражения, блики, движение)
Разные времена суток (особенно вечер и ночь с подсветкой)
Реалистичные материалы вокруг воды (камень, дерево, плитка)

Примеры caption:
textcatvrf water element, luxury infinity pool with crystal clear water and dramatic reflections at sunset, realistic water surface, beautiful landscape
5. Оптимальные настройки использования
В TextureGenerationService / ExteriorGenerationService:
PHP$loras = [
    'house_territory_landscape_v1' => 0.88,
    'water_elements_v1'            => 0.95,     // высокий вес
    'reflections_specular_v1'      => 0.88,
    'garden_landscape_lighting_v1' => 0.82,
    'volumetric_lighting_v1'       => 0.78,
];
Примеры промптов:
Бассейн:
textcatvrf water element, stunning infinity pool with turquoise clear water, realistic reflections of sky and landscape, soft evening lighting, luxury backyard
Фонтан:
textcatvrf water element, elegant illuminated fountain with cascading water, beautiful water droplets and light reflections, nighttime garden scene
Пруд:
textcatvrf water element, calm garden pond with water lilies and soft ripples, natural reflections of trees, peaceful morning atmosphere
Negative Prompt:
textdry pool, frozen water, bad reflections, cartoon water, unrealistic waves, low quality, blurry surface
6. Что должен вернуть ИИ

Полный конфиг обучения (water_elements_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Landscape, Lighting и Reflections LoRA

### 1. Цель
Создать высокоспециализированный LoRA, который идеально передаёт реалистичные отражения в воде:

Отражения неба, деревьев, зданий, мебели и людей в бассейнах, прудах, фонтанах
Рябь и искажения отражений
Блики и caustics (световые узоры под водой)
Разные состояния воды (спокойная, с лёгкой рябью, после дождя)
Взаимодействие отражений с освещением (день, закат, ночь с подсветкой)

Этот LoRA будет работать совместно с Water Elements, Reflections Specular и Volumetric Lighting.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех справляется с отражениями и прозрачностью воды)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Water_Reflections_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92                    # высокий rank — отражения требуют точности
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.4e-5
max_train_steps: 8800 - 13200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf water reflections, realistic water mirror effect, detailed reflections in pool, beautiful water surface

Дополнительные триггеры:

perfect sky reflection in infinity pool, rippling reflections of trees, golden hour reflection on water
night pool with illuminated reflections, calm pond mirror effect, subtle water caustics

4. Структура датасета
Рекомендуемый объём: 1700–3000 изображений
Структура папок:
textwater_reflections_dataset/
├── 1_infinity_pool_reflections/
├── 2_pond_and_lake_reflections/
├── 3_fountain_water_reflections/
├── 4_golden_hour_reflections/
├── 5_night_illuminated_reflections/
├── 6_rippling_water_surface/
├── 7_caustics_and_light_patterns/
└── captions.txt
Требования к фото:

Высокое качество отражений (чёткие + с естественной рябью)
Разные углы и освещение
Видимые отражения объектов и неба

Примеры caption:
textcatvrf water reflections, stunning infinity pool with perfect mirror reflection of blue sky and mountains, calm water surface, realistic and beautiful
5. Оптимальные настройки использования
В TextureGenerationService / ExteriorGenerationService:
PHP$loras = [
    'water_elements_v1'            => 0.88,
    'water_reflections_v1'         => 0.96,     // самый высокий вес
    'reflections_specular_v1'      => 0.82,
    'volumetric_lighting_v1'       => 0.85,
    'garden_landscape_lighting_v1' => 0.70,
];
Примеры промптов:
Бассейн:
textcatvrf water reflections, luxury infinity pool with crystal clear mirror reflection of sky and palm trees, soft ripples, golden hour lighting, ultra realistic
Пруд:
textcatvrf water reflections, calm garden pond with perfect reflection of surrounding trees and house, subtle water movement, peaceful morning atmosphere
Фонтан ночью:
textcatvrf water reflections, illuminated fountain with dramatic water reflections and light caustics at night, magical glowing effect, realistic
Negative Prompt:
textflat water, no reflections, blurry surface, bad mirror effect, cartoon water, unrealistic caustics, dry surface
6. Что должен вернуть ИИ

Полный конфиг обучения (water_reflections_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Water Elements, Reflections и Lighting LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт атмосферу праздничных интерьеров:

День рождения, юбилеи, свадьбы, Новый год, корпоративы, детские праздники
Праздничный декор (шары, гирлянды, цветочные композиции, баннеры, конфетти)
Праздничные столы, фотозоны, освещение (тёплые гирлянды, свечи, неоновые вывески)
Разные настроения: яркий детский праздник, элегантная свадьба, уютный Новый год, стильный корпоратив

Этот LoRA будет идеально работать вместе с Interior, Lighting, Color Grading и Volumetric Lighting LoRA.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая атмосфера и детализация декора)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Festive_Interior_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 85
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.1e-5
max_train_steps: 8200 - 12500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf festive interior, beautiful party decoration, celebratory atmosphere, detailed festive setup

Дополнительные триггеры:

birthday party decoration, elegant wedding reception, cozy New Year interior, bright kids party
balloons and garlands, candlelit dinner, fairy lights, flower arrangements, festive table setting

4. Структура датасета
Рекомендуемый объём: 1800–3200 изображений
Структура папок:
textfestive_interior_dataset/
├── 1_birthday_party/
├── 2_wedding_reception/
├── 3_new_year_interior/
├── 4_kids_party_decoration/
├── 5_corporate_event/
├── 6_romantic_dinner_setup/
├── 7_festive_table_arrangement/
└── captions.txt
Требования к фото:

Празднично украшенные помещения (реальные мероприятия)
Разные стили и настроения
Хорошее освещение (тёплые гирлянды, свечи, акцентный свет)

Примеры caption:
textcatvrf festive interior, beautiful birthday party decoration with colorful balloons, elegant table setting and fairy lights, warm cozy atmosphere, high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.82,
    'festive_interior_v1'          => 0.94,     // высокий вес
    'volumetric_lighting_v1'       => 0.85,
    'color_grading_master_v1'      => 0.80,
    'lighting_atmosphere_v1'       => 0.78,
];
Примеры промптов:
День рождения:
textcatvrf festive interior, joyful birthday party room with colorful balloons and beautiful cake table, warm fairy lights, happy celebratory atmosphere, realistic
Свадьба:
textcatvrf festive interior, elegant wedding reception hall with white flowers and soft candle lighting, romantic atmosphere, luxury decoration
Новый год:
textcatvrf festive interior, cozy New Year living room with Christmas tree and warm lights, festive table setting, magical holiday atmosphere
Детский праздник:
textcatvrf festive interior, bright and fun kids birthday party with cartoon decorations and colorful balloons, joyful children's atmosphere
Negative Prompt:
textdark gloomy room, sad atmosphere, empty party, bad decoration, low quality, blurry, unrealistic lighting
6. Что должен вернуть ИИ

Полный конфиг обучения (festive_interior_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с другими LoRA (Lighting, Interior, Color Grading)

### 1. Цель
Создать высококачественный, универсальный Food LoRA, который отлично генерирует:

Реалистичные блюда всех кухонь мира
Детализированные текстуры продуктов (мясо, рыба, овощи, соусы, выпечка, десерты)
Красивую подачу и плейтинг
Горячие и холодные блюда (пар, конденсат, глянец)
Упаковку для доставки, ресторанный сервиз, бокалы, приборы
Интерьеры кафе, ресторанов и кухонь с едой

Этот LoRA станет ключевым для вертикалей: рестораны, доставка еды, кафе, кондитерские, фуд-корт.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация еды и текстур)
Альтернатива: SDXL + Realistic Vision / EpicRealism
Название LoRA: CatVRF_Food_Culinary_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 96                    # высокий rank — еда требует максимальной детализации текстуры
alpha: 48
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.2e-5
max_train_steps: 9800 - 14500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf food, realistic culinary photography, detailed food texture, appetizing dish, studio food shot

Дополнительные триггеры:

juicy steak, creamy pasta, fresh sushi, golden crispy fried food, glossy sauce, steam rising
restaurant plating, fine dining presentation, street food, homemade meal

4. Структура датасета (очень важно!)
Рекомендуемый объём: 2800–4500 изображений высокого качества
Структура папок:
textfood_culinary_dataset/
├── 1_meat_dishes/
├── 2_seafood/
├── 3_pasta_and_rice/
├── 4_salads_and_vegetables/
├── 5_desserts_and_pastries/
├── 6_sushi_and_asian/
├── 7_fast_food_and_street_food/
├── 8_beverages_and_drinks/
├── 9_food_packaging_delivery/
├── 10_restaurant_plating/
└── captions.txt
Требования к датасету:

Только профессиональные food-фотографии (студийное освещение)
Много close-up текстур (сок мяса, глянец соуса, хрустящая корочка)
Разные углы и стили подачи
Фото горячих блюд с паром

Примеры caption:
textcatvrf food, perfectly cooked juicy ribeye steak with fresh herbs and glossy sauce, realistic meat texture, steam rising, appetizing studio food photography
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'food_culinary_v1'             => 0.94,     // очень высокий вес
    'material_texture_master_v1'   => 0.78,
    'color_grading_master_v1'      => 0.85,
    'volumetric_lighting_v1'       => 0.80,
];
Примеры промптов:
Горячее блюдо:
textcatvrf food, appetizing grilled salmon with asparagus and creamy sauce, realistic texture and steam, professional culinary photography
Десерт:
textcatvrf food, luxurious chocolate lava cake with melting center and fresh berries, glossy texture, studio food shot, high quality
Упаковка для доставки:
textcatvrf food, fresh sushi set in elegant delivery box, realistic packaging, appetizing presentation
Negative Prompt:
textburned food, dry food, bad plating, cartoon food, plastic look, low quality, blurry texture, artificial colors
6. Что должен вернуть ИИ

Полный конфиг обучения (food_culinary_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для еды
Рекомендации по комбинации с другими LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт:

Напитки всех типов (коктейли, кофе, чай, лимонады, крафтовое пиво, вино, крепкий алкоголь)
Реалистичную текстуру жидкостей, пузырьки, конденсат, блики на стекле
Подачу напитков (бокалы, шейкеры, гарниры, лёд, дым от коктейлей)
Интерьеры баров, кофеен, винных погребов, лаунж-зон
Атмосферу (вечерний бар, утреннее кафе, ночной клуб)

Этот LoRA будет критически важен для вертикалей: рестораны, бары, доставка напитков, кофейни.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с жидкостями, бликами и прозрачностью)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Drinks_Bar_Culture_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 82
alpha: 41
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.2e-5
max_train_steps: 7600 - 11500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf drinks, realistic cocktail photography, detailed beverage, bar culture, appetizing drink

Дополнительные триггеры:

craft cocktail with smoke, latte art coffee, fresh lemonade with ice, whiskey on the rocks
luxury bar interior, moody cocktail bar, cozy coffee shop, professional mixology

4. Структура датасета
Рекомендуемый объём: 1900–3200 изображений
Структура папок:
textdrinks_bar_dataset/
├── 1_cocktails_and_mixology/
├── 2_coffee_and_latte_art/
├── 3_tea_and_herbal_drinks/
├── 4_beer_and_craft_brew/
├── 5_wine_and_spirits/
├── 6_non_alcoholic_drinks/
├── 7_bar_interiors/
├── 8_coffee_shop_ambience/
└── captions.txt
Требования к фото:

Профессиональная food & drink съёмка
Close-up текстур жидкостей, капель конденсата, пузырьков
Разные ракурсы и освещение (мягкое, драматическое, неоновое)

Примеры caption:
textcatvrf drinks, stunning smoked old fashioned cocktail with orange peel and ice, detailed glass texture and smoke, moody bar lighting, high quality
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'food_culinary_v1'             => 0.75,
    'drinks_bar_culture_v1'        => 0.94,     // высокий вес
    'reflections_specular_v1'      => 0.88,     // критично для стекла и жидкостей
    'color_grading_master_v1'      => 0.82,
    'volumetric_lighting_v1'       => 0.78,
];
Примеры промптов:
Коктейль:
textcatvrf drinks, beautiful layered cocktail with fresh fruits and herbs, realistic liquid texture and condensation on glass, professional mixology photography
Кофе:
textcatvrf drinks, perfect flat white coffee with intricate latte art, creamy texture, soft morning light in cozy cafe, high quality
Интерьер бара:
textcatvrf drinks, moody luxury cocktail bar interior at night with elegant bottles and warm lighting, realistic glass reflections, atmospheric scene
Negative Prompt:
textbad liquid, deformed glass, plastic look, flat colors, low detail, blurry drink, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (drinks_bar_culture_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для напитков и баров
Рекомендации по комбинации с Food Culinary, Reflections и Lighting LoRA

1. Общие требования к датасету

Объём: 2200–3800 изображений высокого качества (минимум 2800 рекомендуется)
Разрешение: минимум 1024×1024, желательно 1344×768 или 1536×1024
Формат: JPEG или PNG (без сжатия)
Стиль: профессиональная food & drink photography (студийное освещение)
Фон: преимущественно чистый (белый/серый/тёмный), иногда lifestyle

2. Структура датасета (рекомендуемая)
textdrinks_bar_dataset/
├── 01_classic_cocktails/              # Мартини, Old Fashioned, Negroni и т.д.
├── 02_signature_cocktails/            # Авторские, сложные коктейли
├── 03_mocktails_non_alcoholic/        # Безалкогольные коктейли
├── 04_coffee_drinks/                  # Эспрессо, латте, капучино, flat white
├── 05_tea_and_herbal/                 # Чай, матча, травяные напитки
├── 06_lemonades_and_fresh/            # Лимонады, фреши, детокс-напитки
├── 07_craft_beer_and_brew/            # Крафтовое пиво, стауты, IPA
├── 08_wine_and_spirits/               # Вино, виски, ром, текила
├── 09_milkshakes_and_dessert_drinks/  # Милкшейки, смузи, фраппе
├── 10_hot_drinks/                     # Горячий шоколад, глинтвейн, пуэр
├── 11_bar_interiors_ambience/         # Интерьеры баров, стоек, полки с бутылками
├── 12_bartender_action/               # Процесс приготовления (шейкер, налив и т.д.)
├── 13_glassware_and_garnish/          # Бокалы, декор, гарниры крупным планом
└── captions.txt
3. Детальные требования по категориям
01–02 Коктейли (классика + signature)

Обязательно: капли конденсата, лёд, пузырьки, дым (для smoked cocktails)
Ракурсы: 45°, top view, side view, close-up garnish
Примеры caption:
catvrf drinks, classic Negroni cocktail in rocks glass with orange peel, perfect ice, realistic liquid texture, moody bar lighting

03 Mocktails

Яркие цвета, свежие фрукты, травы
Фокус на "аппетитности" без алкоголя

04 Кофе

Латте-арт (очень важно!)
Пар от горячего кофе
Разные виды подачи (в турке, чашке, бумажном стакане)

07 Крафтовое пиво

Пена, пузырьки, капли на бокале
Разные стили пива (светлое, тёмное, IPA)

11 Интерьеры баров

Атмосферные ночные снимки стоек, полок с бутылками, освещение
Тёплый и холодный свет

4. Общие правила съёмки датасета

Освещение: мягкое студийное + акцентное (backlight для бликов)
Композиция: правило третей, negative space
Детализация: macro-снимки текстуры жидкости, капель, пузырьков
Разнообразие: разная температура напитков, разное время суток, разные бокалы

5. Рекомендации по caption (очень важно!)
Каждое изображение должно содержать:

catvrf drinks — триггер
Описание напитка + ключевые визуальные детали
Тип освещения
Настроение

Пример хорошего caption:
textcatvrf drinks, smoky mezcal cocktail with rosemary garnish in crystal glass, thick smoke rising, realistic liquid texture and condensation, dramatic moody bar lighting, high quality
6. Что должен вернуть ИИ

Полную структуру папок датасета (как выше)
Примеры caption для каждой категории (10–15 штук)
Рекомендации по съёмке и обработке фото
Обновлённый TextureGenerationService.php с примерами промптов для напитков
Советы по балансу датасета (сколько % коктейлей, кофе, пива и т.д.)

от качественные, детализированные примеры caption для каждой категории. Они оптимизированы под Flux.1-dev и содержат необходимые триггеры.
01. Classic Cocktails

catvrf drinks, classic Old Fashioned cocktail in crystal rocks glass with large ice cube and orange twist, rich amber color, realistic liquid texture, moody bar lighting
catvrf drinks, perfect Negroni with campari and gin, bright red hue, orange garnish, condensation on glass, professional cocktail photography
catvrf drinks, elegant Dry Martini with olive, crystal clear liquid, classic coupe glass, soft studio lighting

02. Signature Cocktails

catvrf drinks, smoky mezcal cocktail with rosemary and charred orange, thick smoke rising, dramatic lighting, signature mixology
catvrf drinks, layered rainbow cocktail with fresh fruits and edible flowers, vibrant colors, detailed glass texture, luxury bar presentation
catvrf drinks, molecular cocktail with foam and sphere garnish, modern mixology, artistic plating, high quality

03. Mocktails (Non-Alcoholic)

catvrf drinks, refreshing virgin mojito with fresh mint and lime, sparkling water, condensation droplets, bright summer lighting
catvrf drinks, colorful tropical mocktail with passionfruit and edible flowers, vibrant presentation, clean studio shot
catvrf drinks, healthy berry detox mocktail in tall glass, fresh ingredients, natural lighting

04. Coffee Drinks

catvrf drinks, perfect flat white coffee with intricate latte art, creamy microfoam, soft morning light in cozy cafe
catvrf drinks, iced caramel latte with whipped cream and caramel drizzle, condensation on glass, appetizing detail shot
catvrf drinks, espresso shot in white ceramic cup with crema, minimalist composition, warm lighting

05. Tea & Herbal

catvrf drinks, elegant loose leaf tea in glass teapot with blooming flowers, soft steam rising, serene atmosphere
catvrf drinks, matcha latte with beautiful latte art, vibrant green color, creamy texture, studio lighting
catvrf drinks, herbal chamomile tea with fresh flowers, calming warm tones, cozy evening shot

06. Lemonades & Fresh

catvrf drinks, fresh strawberry lemonade with mint and ice, bright pink color, condensation, sunny summer vibe
catvrf drinks, detox cucumber mint lemonade in mason jar, fresh ingredients, natural daylight
catvrf drinks, sparkling blood orange lemonade with rosemary, vibrant color, detailed glass texture

07. Craft Beer & Brew

catvrf drinks, hazy IPA craft beer in glass with thick foam, golden color, detailed bubbles, bar setting
catvrf drinks, dark stout beer with creamy head, rich chocolate notes, moody lighting, realistic texture
catvrf drinks, sour beer with fruit garnish, vibrant pink color, professional beer photography

08. Wine & Spirits

catvrf drinks, aged whiskey in crystal glass with large ice sphere, rich amber color, dramatic side lighting
catvrf drinks, elegant red wine in burgundy glass, detailed reflections, soft warm lighting
catvrf drinks, premium gin and tonic with botanicals and cucumber, clear liquid, fresh garnish

09. Milkshakes & Dessert Drinks

catvrf drinks, thick chocolate milkshake with whipped cream and cherry, dripping chocolate, appetizing dessert drink
catvrf drinks, strawberry milkshake in tall glass with fresh berries, pink color, studio food photography

10. Hot Drinks

catvrf drinks, steaming hot chocolate with marshmallows and cinnamon, warm cozy atmosphere, detailed steam
catvrf drinks, mulled wine with spices and orange slices, warm glow, winter holiday mood

11. Bar Interiors & Ambience

catvrf drinks, moody luxury cocktail bar interior at night with elegant bottle shelves and warm lighting, atmospheric scene
catvrf drinks, cozy coffee shop interior with wooden tables and soft natural light, inviting atmosphere

12. Bartender Action

catvrf drinks, professional bartender shaking cocktail, motion blur on shaker, dynamic bar scene
catvrf drinks, bartender pouring drink with perfect stream, detailed hands and glassware, action shot

13. Glassware & Garnish

catvrf drinks, detailed crystal coupe glass with lemon twist garnish, macro shot, studio lighting
catvrf drinks, beautiful garnished cocktail with edible flowers and herbs, extreme close-up, high detail

### 1. Цель
Создать универсальный и высококачественный LoRA для генерации реалистичной мебели всех типов, который отлично передаёт:

Мягкую мебель (диваны, кресла, пуфы, кровати)
Корпусную мебель (шкафы, комоды, столы, стеллажи)
Офисную и коммерческую мебель
Детскую и кухонную мебель
Материалы (дерево, ткань, кожа, металл, стекло, ротанг, пластик)
Разные стили (минимализм, сканди, классика, лофт, luxury, mid-century)

Этот LoRA станет одним из самых важных для вертикалей мебель, интерьеры, офисы, HoReCa.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация материалов и форм)
Альтернатива: SDXL + Realistic Vision / Juggernaut XL
Название LoRA: CatVRF_Furniture_Master_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 96                    # высокий rank — мебель требует точной геометрии и текстур
alpha: 48
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.4e-5
max_train_steps: 9800 - 14800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf furniture, realistic modern furniture, detailed interior furniture, high quality product photography

Дополнительные триггеры:

comfortable sectional sofa, elegant dining table, minimalist wooden cabinet, luxury leather armchair
mid century modern chair, scandinavian oak bed, industrial metal shelving

4. Структура датасета
Рекомендуемый объём: 2800–4500 изображений
Структура папок:
textfurniture_dataset/
├── 01_sofas_and_sectionals/
├── 02_armchairs_and_chairs/
├── 03_beds_and_bedframes/
├── 04_dining_tables_and_sets/
├── 05_cabinets_and_storage/
├── 06_desks_and_office_furniture/
├── 07_kitchen_furniture/
├── 08_children_furniture/
├── 09_outdoor_furniture/
├── 10_coffee_tables_and_consoles/
└── captions.txt
Требования к фото:

Профессиональная студийная съёмка мебели
Много ракурсов (front, 3/4, side, detail)
Видимые текстуры материалов (дерево, ткань, кожа, металл)
Чистый фон + lifestyle в интерьере

Примеры caption:
textcatvrf furniture, luxurious gray sectional sofa with soft velvet texture and wooden legs, detailed stitching, studio lighting, high quality product photography
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'material_texture_master_v1'   => 0.85,
    'furniture_master_v1'          => 0.94,     // высокий вес
    'interior_v1'                  => 0.82,
    'color_grading_master_v1'      => 0.78,
    'shadows_lighting_master_v1'   => 0.75,
];
Примеры промптов:
Диван:
textcatvrf furniture, comfortable large sectional sofa in beige fabric with wooden base, soft cushions, realistic texture, modern living room setting
Стол:
textcatvrf furniture, elegant oak dining table with marble top, detailed wood grain, luxury interior, studio lighting
Кровать:
textcatvrf furniture, modern minimalist bed with upholstered headboard and soft bedding, clean scandinavian style, high quality
Negative Prompt:
textdeformed furniture, bad proportions, low quality, blurry texture, plastic look, cartoonish, broken furniture
6. Что должен вернуть ИИ

Полный конфиг обучения (furniture_master_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для мебели
Рекомендации по комбинации с Interior, Material Texture и Lighting LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт мягкую мебель (upholstered furniture):

Диваны (прямые, угловые, модульные, секционные)
Кресла (классические, lounge, акцентные)
Пуфы, банкетки, оттоманки
Кровати с мягким изголовьем
Мягкие элементы интерьера (валики, подушки, чехлы)

Особое внимание уделить:

Реалистичным тканям (велюр, бархат, буклированная ткань, лён, кожа, экокожа)
Складкам, драпировке, пуговицам, стёжке
Мягкости и объёму наполнителя
Светотеневым переходам на ткани

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с тканями и мягкими формами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Soft_Furniture_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 88
alpha: 44
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 8500 - 12800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf soft furniture, realistic upholstered sofa, comfortable fabric texture, detailed upholstery

Дополнительные триггеры:

plush velvet sofa, bouclé fabric armchair, modular sectional couch, tufted headboard bed
deep seating, soft cushions, realistic folds and wrinkles on fabric, luxury upholstery

4. Структура датасета
Рекомендуемый объём: 2000–3400 изображений
Структура папок:
textsoft_furniture_dataset/
├── 01_sectional_sofas/
├── 02_classic_sofas/
├── 03_armchairs_and_accents/
├── 04_beds_with_upholstered_headboard/
├── 05_ottomans_and_poufs/
├── 06_velvet_and_boucle/
├── 07_leather_and_eco_leather/
├── 08_modular_furniture/
└── captions.txt
Требования к фото:

Студийные и lifestyle снимки
Много close-up текстур ткани (ворс, стёжка, складки)
Разные углы и освещение (чтобы модель училась драпировке и объёму)

Примеры caption:
textcatvrf soft furniture, luxurious deep gray sectional sofa with soft bouclé fabric, detailed texture and comfortable cushions, studio lighting, high quality
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'furniture_master_v1'          => 0.75,
    'soft_furniture_v1'            => 0.94,     // высокий вес
    'material_texture_master_v1'   => 0.82,
    'color_grading_master_v1'      => 0.78,
    'interior_v1'                  => 0.70,
];
Примеры промптов:
Диван:
textcatvrf soft furniture, comfortable large modular sectional sofa in warm beige bouclé fabric, deep seating with soft cushions, realistic texture and folds, modern living room
Кресло:
textcatvrf soft furniture, elegant velvet accent armchair in deep emerald color, tufted back and brass legs, luxury interior detail
Кровать:
textcatvrf soft furniture, modern bed with tall upholstered headboard in soft gray fabric, luxurious bedding, cozy bedroom atmosphere
Negative Prompt:
texthard furniture, wooden chair, flat cushions, bad fabric texture, deformed sofa, plastic look, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (soft_furniture_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для мягкой мебели
Рекомендации по комбинации с Furniture Master и Interior LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт мягкую мебель (upholstered furniture):

Диваны (прямые, угловые, модульные, секционные)
Кресла (классические, lounge, акцентные)
Пуфы, банкетки, оттоманки
Кровати с мягким изголовьем
Мягкие элементы интерьера (валики, подушки, чехлы)

Особое внимание уделить:

Реалистичным тканям (велюр, бархат, буклированная ткань, лён, кожа, экокожа)
Складкам, драпировке, пуговицам, стёжке
Мягкости и объёму наполнителя
Светотеневым переходам на ткани

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с тканями и мягкими формами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Soft_Furniture_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 88
alpha: 44
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 8500 - 12800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf soft furniture, realistic upholstered sofa, comfortable fabric texture, detailed upholstery

Дополнительные триггеры:

plush velvet sofa, bouclé fabric armchair, modular sectional couch, tufted headboard bed
deep seating, soft cushions, realistic folds and wrinkles on fabric, luxury upholstery

4. Структура датасета
Рекомендуемый объём: 2000–3400 изображений
Структура папок:
textsoft_furniture_dataset/
├── 01_sectional_sofas/
├── 02_classic_sofas/
├── 03_armchairs_and_accents/
├── 04_beds_with_upholstered_headboard/
├── 05_ottomans_and_poufs/
├── 06_velvet_and_boucle/
├── 07_leather_and_eco_leather/
├── 08_modular_furniture/
└── captions.txt
Требования к фото:

Студийные и lifestyle снимки
Много close-up текстур ткани (ворс, стёжка, складки)
Разные углы и освещение (чтобы модель училась драпировке и объёму)

Примеры caption:
textcatvrf soft furniture, luxurious deep gray sectional sofa with soft bouclé fabric, detailed texture and comfortable cushions, studio lighting, high quality
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'furniture_master_v1'          => 0.75,
    'soft_furniture_v1'            => 0.94,     // высокий вес
    'material_texture_master_v1'   => 0.82,
    'color_grading_master_v1'      => 0.78,
    'interior_v1'                  => 0.70,
];
Примеры промптов:
Диван:
textcatvrf soft furniture, comfortable large modular sectional sofa in warm beige bouclé fabric, deep seating with soft cushions, realistic texture and folds, modern living room
Кресло:
textcatvrf soft furniture, elegant velvet accent armchair in deep emerald color, tufted back and brass legs, luxury interior detail
Кровать:
textcatvrf soft furniture, modern bed with tall upholstered headboard in soft gray fabric, luxurious bedding, cozy bedroom atmosphere
Negative Prompt:
texthard furniture, wooden chair, flat cushions, bad fabric texture, deformed sofa, plastic look, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (soft_furniture_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для мягкой мебели
Рекомендации по комбинации с Furniture Master и Interior LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт кожаную мебель:

Натуральную кожу (гладкую, зернистую, анилиновую, pull-up)
Экокожу и PU-кожу
Разные виды: диваны, кресла, оттоманки, банкетки, изголовья кроватей, пуфы
Реалистичные текстуры: поры, морщины, складки, блеск, матовость, состаренность
Взаимодействие кожи со светом (блики, отражения, мягкие тени)

Этот LoRA будет особенно полезен для премиум-мебели, мебельных салонов и визуализаций интерьеров.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с материалами и отражениями)
Альтернатива: SDXL + Realistic Vision / Juggernaut XL
Название LoRA: CatVRF_Leather_Furniture_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 88
alpha: 44
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.6e-5
max_train_steps: 8200 - 12500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf leather furniture, realistic leather sofa, detailed leather texture, luxury upholstered furniture

Дополнительные триггеры:

genuine aniline leather, full grain leather sofa, distressed leather armchair, smooth premium leather
natural wrinkles, visible pores, subtle sheen, high quality upholstery

4. Структура датасета
Рекомендуемый объём: 1800–3200 изображений
Структура папок:
textleather_furniture_dataset/
├── 1_leather_sofas/
├── 2_leather_armchairs/
├── 3_leather_ottomans_and_poufs/
├── 4_leather_beds_and_headboards/
├── 5_distressed_and_vintage_leather/
├── 6_premium_aniline_leather/
├── 7_black_and_brown_leather/
├── 8_modern_minimal_leather/
└── captions.txt
Требования к фото:

Студийные и lifestyle снимки
Много close-up текстур кожи (поры, морщины, блеск)
Разные углы освещения (чтобы модель училась отражениям)
Новые и состаренные варианты кожи

Примеры caption:
textcatvrf leather furniture, luxurious brown full grain leather sofa with natural wrinkles and rich texture, soft studio lighting, high quality upholstery
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'soft_furniture_v1'            => 0.65,
    'leather_furniture_v1'         => 0.94,     // доминирующий
    'material_texture_master_v1'   => 0.82,
    'reflections_specular_v1'      => 0.80,     // критично для кожи
    'furniture_master_v1'          => 0.70,
];
Примеры промптов:
Диван:
textcatvrf leather furniture, premium cognac brown leather sectional sofa, natural grain texture and realistic wrinkles, soft warm lighting, luxury interior
Кресло:
textcatvrf leather furniture, elegant black leather armchair with tufted back, rich aniline leather texture, detailed stitching, studio lighting
Оттоманка:
textcatvrf leather furniture, large square leather ottoman with soft top, high quality upholstery, realistic material surface, modern living room
Negative Prompt:
textfabric sofa, plastic look, flat texture, bad leather, cartoonish, low detail, deformed furniture
6. Что должен вернуть ИИ

Полный конфиг обучения (leather_furniture_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для кожаной мебели
Рекомендации по комбинации с Soft Furniture и Material Texture LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт деревянную мебель всех типов:

Массив дерева (дуб, орех, ясень, тик, сосна, береза)
Шпон и инженерная древесина
Разные виды отделки (масло, лак, воск, патина, браширование)
Текстуры: видимое волокно, сучки, текстура спила, естественные imperfections
Стили: сканди, минимализм, классика, рустик, mid-century, luxury

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация дерева и естественных текстур)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Wooden_Furniture_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 8800 - 13200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf wooden furniture, realistic solid wood furniture, detailed wood grain texture, natural wood surface

Дополнительные триггеры:

solid oak dining table, walnut coffee table, ash wood cabinet, brushed wood texture
visible wood grain, natural knots, matte oil finish, warm wood tones

4. Структура датасета
Рекомендуемый объём: 2000–3500 изображений
Структура папок:
textwooden_furniture_dataset/
├── 01_oak_furniture/
├── 02_walnut_furniture/
├── 03_ash_and_beech/
├── 04_pine_and_spruce/
├── 05_teak_and_exotic_woods/
├── 06_rustic_wood_furniture/
├── 07_minimal_wood_design/
├── 08_vintage_aged_wood/
└── captions.txt
Требования к фото:

Студийные и lifestyle снимки
Обязательно close-up текстуры дерева (волокно, сучки, поры)
Разные углы освещения (чтобы модель училась естественным бликам и теням)
Новые и состаренные варианты дерева

Примеры caption:
textcatvrf wooden furniture, solid oak dining table with beautiful natural wood grain and visible knots, matte oil finish, warm studio lighting, high quality
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'furniture_master_v1'          => 0.75,
    'wooden_furniture_v1'          => 0.94,     // высокий вес
    'material_texture_master_v1'   => 0.85,
    'interior_v1'                  => 0.78,
    'color_grading_master_v1'      => 0.72,
];
Примеры промптов:
Стол:
textcatvrf wooden furniture, elegant solid walnut dining table with rich wood grain and natural texture, warm lighting, luxury interior
Шкаф:
textcatvrf wooden furniture, minimalist oak wardrobe with beautiful wood texture and matte finish, clean lines, modern bedroom
Кресло:
textcatvrf wooden furniture, comfortable wooden armchair with soft leather seat and visible wood grain, mid-century modern style
Negative Prompt:
textplastic furniture, fake wood texture, blurry grain, cartoonish, low quality, deformed wood
6. Что должен вернуть ИИ

Полный конфиг обучения (wooden_furniture_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для деревянной мебели
Рекомендации по комбинации с Soft Furniture, Leather и Interior LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт металлическую мебель:

Каркасы, ножки, основания столов, стульев, стеллажей
Полностью металлическую мебель (индустриальный стиль, лофт)
Комбинированную мебель (металл + дерево, металл + стекло, металл + кожа)
Разные виды металла: хром, матовый чёрный, нержавеющая сталь, латунь, медь, алюминий, corten steel
Поверхности: полированная, brushed (шлифованная), порошковое покрытие, ржавчина (corten)

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с металлическими отражениями и текстурами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Metal_Furniture_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 85
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 7800 - 11800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf metal furniture, realistic metal frame furniture, detailed metallic texture, industrial design

Дополнительные триггеры:

matte black metal chair, polished chrome table base, brushed stainless steel cabinet, brass accents
industrial metal shelving, powder coated steel frame, corten steel outdoor furniture

4. Структура датасета
Рекомендуемый объём: 1600–2800 изображений
Структура папок:
textmetal_furniture_dataset/
├── 1_matte_black_metal/
├── 2_polished_chrome_and_stainless/
├── 3_brushed_metal/
├── 4_brass_and_copper_accents/
├── 5_industrial_loft_furniture/
├── 6_metal_and_wood_combination/
├── 7_outdoor_metal_furniture/
├── 8_modern_minimal_metal/
└── captions.txt
Требования к фото:

Чёткие отражения и блики на металле
Макро-снимки текстуры (brushed, polished, powder coated)
Разные условия освещения (чтобы модель училась specular highlights)

Примеры caption:
textcatvrf metal furniture, modern matte black metal dining chair with clean lines, realistic powder coated texture, studio lighting, high quality
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'furniture_master_v1'          => 0.70,
    'metal_furniture_v1'           => 0.93,     // высокий вес
    'reflections_specular_v1'      => 0.88,     // критично для металла
    'material_texture_master_v1'   => 0.82,
    'soft_furniture_v1'            => 0.55,     // для комбинированной мебели
];
Примеры промптов:
Стул:
textcatvrf metal furniture, sleek modern black metal chair with powder coated finish, realistic metallic texture, clean industrial design, studio lighting
Стол:
textcatvrf metal furniture, industrial dining table with thick steel legs and wooden top, brushed metal surface, realistic reflections, modern loft style
Стеллаж:
textcatvrf metal furniture, minimalist metal and wood shelving unit, brushed stainless steel frame, clean lines, high quality product photography
Negative Prompt:
textplastic furniture, wooden only, bad metal texture, blurry reflections, cartoonish, low detail, rusty unwanted`
6. Что должен вернуть ИИ

Полный конфиг обучения (metal_furniture_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Reflections, Soft Furniture и Wooden Furniture LoRA

### 1. Цель
Создать высококачественный, узкоспециализированный LoRA, который отлично передаёт стеклянную мебель и её визуальные особенности:

Прозрачность, преломление и отражения света
Толщину стекла, фаски, полированные кромки
Комбинированную мебель (стекло + металл, стекло + дерево, стекло + камень)
Разные типы стекла: прозрачное, матовое, тонированное, рифлёное, закалённое, с UV-печатью
Реалистичные блики, caustics (световые узоры) и отражения окружения

Этот LoRA особенно важен для современных минималистичных и luxury интерьеров.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с прозрачностью и отражениями)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Glass_Furniture_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 78
alpha: 39
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.0e-5
max_train_steps: 7200 - 10800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf glass furniture, realistic transparent glass table, detailed glass surface, elegant glass design

Дополнительные триггеры:

tempered glass coffee table, clear glass dining table, frosted glass cabinet, glass and metal console
beautiful light refraction, realistic glass reflections, subtle caustics on floor

4. Структура датасета
Рекомендуемый объём: 1600–2800 изображений
Структура папок:
textglass_furniture_dataset/
├── 01_glass_coffee_tables/
├── 02_glass_dining_tables/
├── 03_glass_shelves_and_cabinets/
├── 04_glass_console_and_side_tables/
├── 05_frosted_and_textured_glass/
├── 06_glass_and_metal_combination/
├── 07_luxury_glass_furniture/
├── 08_glass_with_wood_and_stone/
└── captions.txt
Требования к фото:

Чёткие отражения и преломления света
Макро-снимки кромок и толщины стекла
Разные условия освещения (мягкое, драматическое, с backlit)
Фото в интерьере + на чистом фоне

Примеры caption:
textcatvrf glass furniture, elegant round tempered glass coffee table with thin profile, realistic reflections and light refraction, modern minimalist interior, studio lighting
5. Оптимальные настройки использования
В TextureGenerationService:
PHP$loras = [
    'furniture_master_v1'          => 0.68,
    'glass_furniture_v1'           => 0.94,     // доминирующий
    'reflections_specular_v1'      => 0.90,     // обязательно высокий вес
    'metal_furniture_v1'           => 0.70,     // для комбинированной мебели
    'material_texture_master_v1'   => 0.75,
];
Примеры промптов:
Журнальный стол:
textcatvrf glass furniture, beautiful transparent glass coffee table with polished edges, realistic light refraction and reflections, modern luxury interior
Обеденный стол:
textcatvrf glass furniture, large clear glass dining table with slim metal legs, crystal clear surface, elegant minimalist design, studio lighting
Полки/витрина:
textcatvrf glass furniture, minimalist floating glass display shelves, invisible brackets, clean transparent look, high-end retail interior
Negative Prompt:
textmilky glass, plastic look, distorted reflections, bad transparency, low quality, blurry edges, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (glass_furniture_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Reflections, Metal Furniture и Interior LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт современные офисные интерьеры и офисную мебель:

Рабочие места, open-space, кабинеты руководителей
Офисную мебель (столы, кресла, тумбы, стеллажи, переговорные столы)
Зоны отдыха, рецепцию, конференц-залы
Корпоративный стиль (минимализм, hi-tech, сканди, премиум)
Материалы: дерево, металл, стекло, акустические панели, ткань

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Office_Interior_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 88
alpha: 44
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.7e-5
max_train_steps: 8500 - 12800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf office interior, modern workspace, professional office design, detailed corporate interior

Дополнительные триггеры:

open space office, executive office, minimalist workstation, ergonomic office chair
conference room, reception desk, glass partition, acoustic panels

4. Структура датасета
Рекомендуемый объём: 2000–3400 изображений
Структура папок:
textoffice_interior_dataset/
├── 01_open_space_workstations/
├── 02_executive_offices/
├── 03_conference_rooms/
├── 04_reception_and_lobby/
├── 05_ergonomic_furniture/
├── 06_minimalist_office/
├── 07_corporate_lounge_areas/
├── 08_glass_partition_offices/
└── captions.txt
Требования к фото:

Чистые, современные офисные интерьеры
Хорошее освещение (дневное + искусственное)
Видимые детали мебели, материалов и организации пространства

Примеры caption:
textcatvrf office interior, modern open space office with ergonomic white desks and green plants, bright natural lighting, clean professional design, high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.85,
    'office_interior_v1'           => 0.93,     // высокий вес
    'furniture_master_v1'          => 0.78,
    'wooden_furniture_v1'          => 0.65,
    'metal_furniture_v1'           => 0.60,
    'volumetric_lighting_v1'       => 0.80,
];
Примеры промптов:
Open Space:
textcatvrf office interior, bright modern open space office with ergonomic desks and comfortable chairs, lots of plants, natural daylight, professional corporate design
Кабинет руководителя:
textcatvrf office interior, luxury executive office with large wooden desk and leather chair, elegant bookshelves, warm lighting, premium corporate atmosphere
Переговорная:
textcatvrf office interior, modern glass conference room with long meeting table and ergonomic chairs, bright lighting, professional business environment
Negative Prompt:
textcluttered messy office, dark depressing room, low quality, bad furniture, unrealistic proportions, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (office_interior_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Furniture, Wooden, Metal и Interior LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт атмосферу и детали современных коворкингов:

Open-space зоны с рабочими столами
Hot desks, fixed desks, standing desks
Phone booths и quiet zones
Meeting rooms и collaboration areas
Lounge и chill-зоны
Кофейни/кухни внутри коворкинга
Современный, динамичный, креативный стиль (часто с элементами loft, minimal, biophilic design)

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Coworking_Space_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 85
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.0e-5
max_train_steps: 8200 - 12500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf coworking space, modern coworking interior, dynamic workspace, collaborative office environment

Дополнительные триггеры:

open space coworking, hot desk area, phone booth, cozy lounge zone, industrial loft coworking
plants and biophilic design, ergonomic furniture, natural daylight, creative workspace

4. Структура датасета
Рекомендуемый объём: 1900–3200 изображений
Структура папок:
textcoworking_dataset/
├── 01_open_space_workstations/
├── 02_phone_booths_and_quiet_zones/
├── 03_meeting_and_collaboration_rooms/
├── 04_lounge_and_relax_zones/
├── 05_coffee_kitchen_areas/
├── 06_industrial_loft_coworking/
├── 07_minimalist_coworking/
├── 08_biophilic_coworking_with_plants/
└── captions.txt
Требования к фото:

Современные коворкинги (WeWork, Regus, местные сети)
Разные ракурсы: общий план, рабочие места, зоны отдыха
Хорошее естественное + искусственное освещение
Видимые детали (эргономика, растения, доски, декор)

Примеры caption:
textcatvrf coworking space, vibrant modern open space coworking with ergonomic desks and lots of green plants, bright natural daylight, creative and productive atmosphere, high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.82,
    'coworking_space_v1'           => 0.94,     // высокий вес
    'office_interior_v1'           => 0.75,
    'volumetric_lighting_v1'       => 0.80,
    'plants_foliage_v1'            => 0.65,     // если есть растения
];
Примеры промптов:
Open Space:
textcatvrf coworking space, bright and airy open space coworking with wooden desks, ergonomic chairs and many indoor plants, natural daylight, modern productive atmosphere
Зона отдыха:
textcatvrf coworking space, cozy lounge area in coworking with comfortable sofas, coffee tables and warm lighting, relaxed creative environment
Phone Booth:
textcatvrf coworking space, modern soundproof phone booth with glass walls in busy coworking, clean minimalist design
Negative Prompt:
textempty office, dark depressing space, cluttered messy desks, low quality, bad lighting, unrealistic proportions
6. Что должен вернуть ИИ

Полный конфиг обучения (coworking_space_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Office, Interior и Plants LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт конференц-залы и переговорные комнаты:

Современные конференц-залы разного масштаба (от 4 до 50+ человек)
Большой переговорный стол, эргономичные кресла
Экраны, проекторы, интерактивные панели, доски
Зоны презентаций и видеоконференций
Разные стили: минимализм, корпоративный премиум, hi-tech, сканди
Атмосферу деловых переговоров, тренингов, совещаний

Этот LoRA будет особенно полезен для B2B-вертикалей (коворкинги, бизнес-центры, корпоративные клиенты).
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (отличная детализация пространства и мебели)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Conference_Room_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 86
alpha: 43
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.9e-5
max_train_steps: 8200 - 12400
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf conference room, modern meeting room, professional boardroom, corporate conference hall

Дополнительные триггеры:

large oval conference table, ergonomic office chairs, video conference setup, interactive display screen
minimalist meeting room, luxury executive boardroom, collaborative workspace

4. Структура датасета
Рекомендуемый объём: 1800–3000 изображений
Структура папок:
textconference_room_dataset/
├── 01_small_meeting_rooms_4-8/
├── 02_medium_conference_rooms_10-20/
├── 03_large_boardrooms_20+/
├── 04_executive_meeting_rooms/
├── 05_video_conference_rooms/
├── 06_minimalist_meeting_spaces/
├── 07_luxury_corporate_boardrooms/
└── captions.txt
Требования к фото:

Профессиональные интерьерные снимки конференц-залов
Разные ракурсы: общий план, вид на стол, презентационная зона
Чётко видно мебель, технику, освещение

Примеры caption:
textcatvrf conference room, modern minimalist meeting room with large wooden table and ergonomic chairs, interactive screen, bright natural lighting, professional corporate design
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.82,
    'conference_room_v1'           => 0.94,     // высокий вес
    'office_interior_v1'           => 0.78,
    'furniture_master_v1'          => 0.75,
    'wooden_furniture_v1'          => 0.68,
    'volumetric_lighting_v1'       => 0.80,
];
Примеры промптов:
Средний конференц-зал:
textcatvrf conference room, modern corporate meeting room with oval wooden table for 12 people, ergonomic black chairs, large LED screen, bright daylight, professional atmosphere
Премиум boardroom:
textcatvrf conference room, luxury executive boardroom with massive marble table and leather chairs, elegant lighting, sophisticated corporate interior
Маленькая переговорная:
textcatvrf conference room, cozy small meeting room with round table and comfortable seating, soft lighting, ideal for team discussions
Negative Prompt:
textempty room, cluttered messy table, bad lighting, low quality, unrealistic furniture, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (conference_room_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Office, Interior и Furniture LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт атмосферу и детали современных open-space офисов:

Большое открытое пространство с множеством рабочих мест
Разнообразные рабочие станции (fixed desks, hot desks, standing desks)
Зоны коллаборации, телефонные будки, quiet pods
Биофильный дизайн (много растений), акустические панели
Естественное освещение + современное искусственное
Динамичную, продуктивную и креативную атмосферу

LoRA идеально дополнит предыдущие (office_interior_v1, coworking_space_v1).
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_OpenSpace_Office_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 87
alpha: 43
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 8600 - 13200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf open space office, modern open plan workspace, dynamic open office interior

Дополнительные триггеры:

large open space with ergonomic desks, collaborative open office, bright open plan workspace
standing desks, acoustic panels, lots of plants, natural daylight office

4. Структура датасета
Рекомендуемый объём: 2100–3600 изображений
Структура папок:
textopen_space_office_dataset/
├── 01_large_open_space_50plus/
├── 02_medium_open_space_20-50/
├── 03_hot_desk_zones/
├── 04_standing_desk_areas/
├── 05_collaboration_zones/
├── 06_quiet_pods_and_phone_booths/
├── 07_biophilic_open_space_with_plants/
├── 08_industrial_loft_open_office/
└── captions.txt
Требования к фото:

Реальные современные open-space (WeWork, Яндекс, Сбер, Avito и т.д.)
Разные ракурсы: общий план сверху/сбоку, рабочие места крупно
Хорошее освещение (много окон + LED)
Видимые детали: мониторы, клавиатуры, растения, акустика

Примеры caption:
textcatvrf open space office, bright modern open plan office with rows of ergonomic white desks and comfortable chairs, lots of green plants, natural daylight through large windows, productive atmosphere, high quality
5. Оптимальные настройки использования
В TextureGenerationService / InteriorGenerationService:
PHP$loras = [
    'interior_v1'                  => 0.80,
    'open_space_office_v1'         => 0.95,     // доминирующий
    'office_interior_v1'           => 0.72,
    'coworking_space_v1'           => 0.65,
    'plants_foliage_v1'            => 0.70,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Большой open-space:
textcatvrf open space office, vast modern open plan workspace with dozens of ergonomic desks, standing desks and comfortable chairs, abundant indoor plants, bright natural daylight, dynamic productive atmosphere
Средний open-space:
textcatvrf open space office, bright collaborative open office with wooden desks, acoustic panels and green walls, natural light, modern corporate design
С зонами коллаборации:
textcatvrf open space office, open plan office with central collaboration area, high tables and stools, surrounded by workstations, energetic creative environment
Negative Prompt:
textcramped office, dark gloomy space, messy cluttered desks, low quality, bad perspective, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (open_space_office_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Office, Coworking и Conference Room LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт цветочные композиции:

Букеты, композиции в вазах, корзинах, коробках
Свадебные, праздничные, интерьерные, траурные аранжировки
Разные стили: европейский, японский (икебана), wild & natural, luxury mono-bouquets
Реалистичную текстуру лепестков, капли росы, объём, светопроницаемость
Сезонность (весенние тюльпаны, летние пионы, осенние хризантемы, зимние композиции)

Особенно важен для вертикали флористика, подарки, декор мероприятий и интерьеров.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (отличная работа с органикой и мелкими деталями)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Floral_Arrangements_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 90
alpha: 45
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.6e-5
max_train_steps: 9200 - 14200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf floral arrangement, realistic flower bouquet, detailed floral composition, professional floristry

Дополнительные триггеры:

luxury peony bouquet, wildflower arrangement, ikebana style, elegant rose composition
fresh flowers with dew drops, voluminous bouquet, soft natural lighting

4. Структура датасета
Рекомендуемый объём: 2400–4200 изображений (очень важна детализация)
Структура папок:
textfloral_arrangements_dataset/
├── 01_luxury_bouquets/
├── 02_wedding_florals/
├── 03_interior_compositions/
├── 04_wild_natural_bouquets/
├── 05_seasonal_spring/
├── 06_seasonal_summer/
├── 07_seasonal_autumn/
├── 08_seasonal_winter/
├── 09_monobouquets/
├── 10_ikebana_and_asian_style/
└── captions.txt
Требования к фото:

Только профессиональная флористическая съёмка
Много macro-деталей (лепестки, капли росы, текстура листьев)
Разные ракурсы и освещение (мягкое окно, студия, golden hour)

Примеры caption:
textcatvrf floral arrangement, luxurious voluminous peony bouquet with eucalyptus and ranunculus, fresh petals with dew drops, soft natural lighting, professional floristry
5. Оптимальные настройки использования
В TextureGenerationService / FlowerGenerationService:
PHP$loras = [
    'plants_foliage_v1'            => 0.75,
    'floral_arrangements_v1'       => 0.95,     // очень высокий вес
    'color_grading_master_v1'      => 0.82,
    'volumetric_lighting_v1'       => 0.80,
    'festive_interior_v1'          => 0.60,     // для праздничных
];
Примеры промптов:
Люксовый букет:
textcatvrf floral arrangement, stunning luxury bouquet with large pink peonies, white roses and delicate greenery, fresh dew drops, soft morning light, elegant composition
Свадебный:
textcatvrf floral arrangement, romantic wedding bouquet with white roses, ranunculus and eucalyptus, delicate and voluminous, natural style
Интерьерная композиция:
textcatvrf floral arrangement, large interior flower arrangement in glass vase with monstera and orchids, modern minimalist style, bright daylight
Negative Prompt:
textartificial flowers, plastic look, deformed petals, blurry flowers, bad composition, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (floral_arrangements_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов для цветочных композиций
Рекомендации по комбинации с Plants Foliage и Festive Interior LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт свадебный декор премиум-уровня:

Арки, церемониальные зоны, фотозоны
Оформление столов (table setting), стульев, дорожек
Цветочные инсталляции, drapery (тканевые драпировки), световые гирлянды
Свадебные торты, candy bar, welcome-зоны
Атмосферу: романтика, элегантность, luxury, boho, rustic, modern minimal
Взаимодействие с освещением (золотой час, вечерние огни, свечи)

LoRA идеально дополняет floral_arrangements_v1 и festive_interior_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая атмосфера и детали тканей/цветов)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Wedding_Decor_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 89
alpha: 44
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.7e-5
max_train_steps: 8800 - 13500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf wedding decor, elegant wedding decoration, romantic ceremony setup, luxury wedding styling

Дополнительные триггеры:

floral wedding arch, draped ceremony backdrop, elegant table setting, candlelit reception
boho wedding decor, luxury white and gold wedding, soft fairy lights

4. Структура датасета
Рекомендуемый объём: 2300–3800 изображений
Структура папок:
textwedding_decor_dataset/
├── 01_ceremony_arches_and_backdrops/
├── 02_wedding_table_settings/
├── 03_reception_and_dance_floor/
├── 04_welcome_sign_and_guest_areas/
├── 05_candle_and_lighting_decor/
├── 06_drapery_and_fabric_installations/
├── 07_luxury_white_gold_wedding/
├── 08_boho_rustic_wedding/
├── 09_modern_minimal_wedding/
└── captions.txt
Требования к фото:

Только премиум-свадебные съёмки (real weddings)
Много деталей: текстуры тканей, лепестки, блеск свечей, мягкий свет
Разные время суток (день, golden hour, вечер)

Примеры caption:
textcatvrf wedding decor, stunning floral wedding arch with white roses and greenery, draped fabric, soft golden hour lighting, romantic ceremony setup, luxury style
5. Оптимальные настройки использования
В TextureGenerationService / FestiveGenerationService:
PHP$loras = [
    'festive_interior_v1'          => 0.78,
    'floral_arrangements_v1'       => 0.82,
    'wedding_decor_v1'             => 0.95,     // доминирующий
    'volumetric_lighting_v1'       => 0.85,
    'color_grading_master_v1'      => 0.80,
];
Примеры промптов:
Арка:
textcatvrf wedding decor, beautiful large floral wedding arch with white and blush roses, greenery and soft drapery, golden hour sunlight, romantic outdoor ceremony
Сервировка стола:
textcatvrf wedding decor, elegant luxury wedding table setting with white linen, gold cutlery, fresh flowers and candles, soft romantic lighting
Зал приёма:
textcatvrf wedding decor, magical wedding reception hall with draped ceiling, fairy lights and stunning floral centerpieces, warm evening atmosphere
Negative Prompt:
textcheap decor, plastic flowers, messy setup, dark gloomy lighting, low quality, cartoonish, deformed fabrics
6. Что должен вернуть ИИ

Полный конфиг обучения (wedding_decor_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Floral Arrangements и Festive Interior LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт праздничные баннеры и вывески:

Тканевые растяжки, бумажные баннеры, фольгированные, LED-баннеры, неоновые вывески
Разные события: День рождения, Новый год, свадьба, юбилей, корпоратив, 8 марта, 23 февраля
Реалистичную текстуру материалов (глянец фольги, матовая ткань, гофрокартон, объёмные буквы)
Эффекты: складки ткани, блики, отражения, подсветка, конфетти, воздушные шары рядом
Интеграцию текста (читаемый, но без генерации конкретных слов — только стиль)

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всего справляется с текстурами и освещением)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Festive_Banners_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 82
alpha: 41
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.1e-5
max_train_steps: 7600 - 11800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf festive banner, realistic party banner, celebration sign, holiday decoration banner

Дополнительные триггеры:

happy birthday banner, luxury wedding welcome sign, New Year festive stretch, corporate event banner
glossy foil banner, fabric with folds, illuminated LED banner, voluminous 3D letters

4. Структура датасета
Рекомендуемый объём: 1700–2900 изображений
Структура папок:
textfestive_banners_dataset/
├── 01_birthday_banners/
├── 02_wedding_welcome_signs/
├── 03_new_year_and_holiday_banners/
├── 04_corporate_event_banners/
├── 05_luxury_gold_silver_banners/
├── 06_fabric_and_draped_banners/
├── 07_led_and_neon_signs/
├── 08_kids_party_banners/
└── captions.txt
Требования к фото:

Реальные праздничные баннеры в интерьере и на улице
Close-up текстур (фольга, ткань, печать)
Разное освещение (день, вечер, с подсветкой)

Примеры caption:
textcatvrf festive banner, elegant gold happy birthday banner with voluminous letters and soft fabric folds, warm party lighting, realistic decoration
5. Оптимальные настройки использования
В TextureGenerationService / FestiveGenerationService:
PHP$loras = [
    'festive_interior_v1'          => 0.78,
    'floral_arrangements_v1'       => 0.65,
    'wedding_decor_v1'             => 0.70,
    'festive_banners_v1'           => 0.94,     // высокий вес
    'reflections_specular_v1'      => 0.82,
    'volumetric_lighting_v1'       => 0.80,
];
Примеры промптов:
День рождения:
textcatvrf festive banner, large colorful happy birthday banner with gold foil letters and balloons, hanging in decorated room, bright festive atmosphere
Свадьба:
textcatvrf festive banner, elegant white and gold "Welcome to our wedding" fabric banner with floral decoration, soft romantic lighting
Новый год:
textcatvrf festive banner, luxurious New Year banner with sparkling gold text and snow elements, festive interior with lights
Negative Prompt:
textblurry text, cheap plastic banner, deformed letters, bad printing, dark dull colors, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (festive_banners_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Decor, Floral Arrangements и Festive Interior LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт праздничные воздушные шары:

Обычные латексные, фольгированные, хромированные, прозрачные, с конфетти
Букеты шаров, арки, колонны, потолочные композиции
Разные события: День рождения, свадьба, гендер-пати, корпоратив, Новый год, детские праздники
Реалистичную физику: отражения, блики, складки, гелиевый подъём, ленты
Взаимодействие с освещением (блики на фольге, полупрозрачность, объём)

LoRA идеально дополняет festive_banners_v1, floral_arrangements_v1 и wedding_decor_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (отлично работает с отражениями и объёмными объектами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Festive_Balloons_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 84
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.9e-5
max_train_steps: 7800 - 12200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf festive balloons, realistic party balloons, colorful helium balloons, celebration balloon bouquet

Дополнительные триггеры:

chrome balloons, transparent balloons with confetti, balloon arch, balloon column
birthday balloon bunch, luxury gold and white balloons, floating helium balloons

4. Структура датасета
Рекомендуемый объём: 1900–3300 изображений
Структура папок:
textfestive_balloons_dataset/
├── 01_standard_latex_balloons/
├── 02_foil_chrome_balloons/
├── 03_transparent_confetti_balloons/
├── 04_balloon_bouquets_and_bunches/
├── 05_balloon_arches_and_columns/
├── 06_birthday_party_balloons/
├── 07_wedding_and_luxury_balloons/
├── 08_kids_party_balloons/
├── 09_corporate_event_balloons/
└── captions.txt
Требования к фото:

Профессиональные съёмки с хорошим освещением
Close-up текстур (глянец фольги, матовость латекса, отражения)
Разные состояния: в воздухе, в связках, арки, на потолке

Примеры caption:
textcatvrf festive balloons, large colorful helium balloon bouquet with gold and white chrome balloons, realistic reflections and ribbons, bright party lighting
5. Оптимальные настройки использования
В TextureGenerationService / FestiveGenerationService:
PHP$loras = [
    'festive_interior_v1'          => 0.75,
    'festive_banners_v1'           => 0.68,
    'floral_arrangements_v1'       => 0.60,
    'festive_balloons_v1'          => 0.95,     // очень высокий вес
    'reflections_specular_v1'      => 0.85,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Букет шаров:
textcatvrf festive balloons, big joyful helium balloon bouquet with pastel and chrome balloons, floating in decorated room, bright festive atmosphere, realistic reflections
Арка:
textcatvrf festive balloons, impressive balloon arch with gold and white balloons for wedding entrance, soft natural lighting, elegant celebration setup
Детский праздник:
textcatvrf festive balloons, colorful kids party balloon arrangement with cartoon characters and bright latex balloons, joyful and playful atmosphere
Negative Prompt:
textdeflated balloons, bad reflections, plastic look, deformed shapes, blurry, low quality, dark lighting
6. Что должен вернуть ИИ

Полный конфиг обучения (festive_balloons_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Festive Banners, Floral Arrangements и Wedding Decor LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт свадебные платья премиум-класса:

Разные силуэты: A-line, Mermaid, Ball Gown, Sheath, Empire, Trumpet
Ткани: кружево, атлас, фатин, шифон, органза, шёлк, 3D-флористика
Детали: вышивка, бисер, жемчуг, аппликации, длинный шлейф, вуаль
Стили: классика, boho, modern minimal, royal luxury, rustic
Реалистичную драпировку, игру света на ткани, объём, текстуру кружева

LoRA идеально дополняет wedding_decor_v1, floral_arrangements_v1 и festive_interior_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа со сложными тканями и драпировкой)
Альтернатива: SDXL + Realistic Vision / EpicRealism
Название LoRA: CatVRF_Wedding_Dresses_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9800 - 14800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf wedding dress, realistic bridal gown, elegant wedding gown, detailed lace wedding dress

Дополнительные триггеры:

princess ball gown, mermaid silhouette, boho lace dress, luxury beaded wedding dress
long train, cathedral veil, intricate embroidery, soft fabric flow

4. Структура датасета
Рекомендуемый объём: 2600–4200 изображений (очень важно качество)
Структура папок:
textwedding_dresses_dataset/
├── 01_a_line_dresses/
├── 02_mermaid_trumpet/
├── 03_ball_gown_princess/
├── 04_boho_lace_dresses/
├── 05_luxury_beaded_and_3d/
├── 06_minimalist_modern/
├── 07_vintage_and_retro/
├── 08_with_veil_and_train/
├── 09_detail_shots_lace_fabric/
└── captions.txt
Требования к фото:

Только профессиональные свадебные съёмки (high-end)
Много close-up текстур ткани, кружева, бисера
Разные ракурсы: full body, back view, detail shots, movement

Примеры caption:
textcatvrf wedding dress, stunning princess ball gown with voluminous tulle skirt and intricate lace bodice, long cathedral train, soft natural lighting, luxury bridal
5. Оптимальные настройки использования
В TextureGenerationService / WeddingGenerationService:
PHP$loras = [
    'wedding_decor_v1'             => 0.75,
    'floral_arrangements_v1'       => 0.70,
    'wedding_dresses_v1'           => 0.96,     // очень высокий вес
    'soft_furniture_v1'            => 0.55,     // для диванов/кресел на фото
    'reflections_specular_v1'      => 0.78,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Классическое платье:
textcatvrf wedding dress, elegant A-line wedding gown with delicate lace sleeves and flowing skirt, soft romantic lighting, beautiful bride pose
Морской стиль (mermaid):
textcatvrf wedding dress, luxurious mermaid wedding dress with heavy beading and dramatic train, figure-hugging silhouette, luxury hotel interior
Бохо:
textcatvrf wedding dress, boho chic wedding dress with flowing chiffon and floral lace, outdoor garden ceremony, natural sunlight
Negative Prompt:
textdeformed dress, bad anatomy, plastic fabric, blurry lace, cartoonish, low quality, unnatural folds
6. Что должен вернуть ИИ

Полный конфиг обучения (wedding_dresses_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Decor, Floral и Festive LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт полный образ жениха:

Классические костюмы, смокинги, tuxedo, three-piece suits
Разные цвета: чёрный, тёмно-синий, серый, бежевый, бордовый
Рубашки, галстуки, бабочки, жилеты, запонки, платки
Обувь (оксфорды, дерби, лоферы), носки, ремни
Мужские причёски (undercut, slick back, pompadour, modern fade, textured crop)
Разные стили: classic formal, modern minimal, boho groom, luxury royal, rustic

LoRA идеально дополняет wedding_dresses_v1, bridal_makeup_v1, evening_dresses_v1 и позволяет генерировать парные образы жених + невеста.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (отличная работа с мужской анатомией, тканями костюмов и причёсками)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Groom_Attire_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9200 - 14800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf groom attire, realistic wedding suit, elegant groom look, luxury tuxedo

Дополнительные триггеры:

black tuxedo groom, navy three piece suit, modern slim fit suit, boho groom with suspenders
slick back hairstyle groom, sharp formal look, confident groom portrait

4. Структура датасета
Рекомендуемый объём: 2400–4100 изображений
Структура папок:
textgroom_attire_dataset/
├── 01_classic_black_tuxedo/
├── 02_navy_and_gray_suits/
├── 03_three_piece_suits/
├── 04_modern_slim_fit/
├── 05_boho_and_rustic_groom/
├── 06_luxury_royal_style/
├── 07_groom_hairstyles/
├── 08_full_couple_with_bride/
├── 09_detail_accessories_cufflinks_tie/
└── captions.txt
Требования к фото:

Профессиональные свадебные съёмки женихов
Full body + close-up деталей (ткань, запонки, туфли, причёска)
Разные ракурсы и освещение

Примеры caption:
textcatvrf groom attire, handsome groom in classic black tuxedo with white shirt and black bow tie, sharp slick back hairstyle, confident pose, luxury wedding
5. Оптимальные настройки использования
В TextureGenerationService / WeddingGenerationService:
PHP$loras = [
    'wedding_dresses_v1'           => 0.75,
    'evening_dresses_v1'           => 0.60,
    'groom_attire_v1'              => 0.95,     // доминирующий
    'bridal_makeup_v1'             => 0.70,
    'evening_hairstyles_v1'        => 0.65,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Классический смокинг:
textcatvrf groom attire, elegant groom in classic black tuxedo with satin lapels, white shirt and bow tie, neat hairstyle, confident standing pose next to bride
Современный костюм:
textcatvrf groom attire, stylish modern navy slim fit three-piece suit with pocket square, textured fade haircut, luxury outdoor wedding
Бохо-стиль:
textcatvrf groom attire, boho groom in beige linen suit with suspenders and open collar shirt, natural hairstyle, rustic garden wedding
Negative Prompt:
textdeformed suit, bad anatomy, cheap fabric, blurry details, cartoonish, messy hair, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (groom_attire_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Dresses, Evening Dresses и Bridal Makeup LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт вечерний макияж премиум-уровня:

Драматический smoky eyes, cat-eye, cut-crease, glitter eyes
Glowy skin, стробинг, contouring, highligher
Губы: matte red, glossy nude, ombré, metallic
Полный glamorous look (red carpet, gala, cocktail party)
Идеальные переходы, текстуру кожи, блеск, объём ресниц
Взаимодействие с вечерним платьем, причёской и освещением (драматический свет, spotlight)

LoRA идеально дополняет evening_dresses_v1, evening_hairstyles_v1 и bridal_makeup_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с кожей, блеском и драматическим освещением)
Альтернатива: SDXL + Realistic Vision / EpicRealism
Название LoRA: CatVRF_Evening_Makeup_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.6e-5
max_train_steps: 9200 - 14500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf evening makeup, glamorous evening look, red carpet makeup, dramatic party makeup

Дополнительные триггеры:

smoky eyes evening, bold cat eye, glowing skin strobing, deep red lips
luxury full glam, glitter cut crease, soft sultry evening makeup

4. Структура датасета
Рекомендуемый объём: 2400–4000 изображений
Структура папок:
textevening_makeup_dataset/
├── 01_dramatic_smoky_eyes/
├── 02_glitter_and_cut_crease/
├── 03_red_carpet_glam/
├── 04_sultry_nude_glow/
├── 05_bold_lips_and_eyeliner/
├── 06_golden_hour_evening/
├── 07_dark_elegance_black_silver/
├── 08_cocktail_party_makeup/
├── 09_closeup_eyes_lips_skin/
└── captions.txt
Требования к фото:

Профессиональные fashion/editorial съёмки
Много macro (глаза, губы, кожа)
Драматическое освещение + soft fill light

Примеры caption:
textcatvrf evening makeup, dramatic smoky eyes with glitter cut crease, glowing skin with strobing highlighter, deep burgundy lips, red carpet glamour
5. Оптимальные настройки использования
В TextureGenerationService / BeautyGenerationService:
PHP$loras = [
    'evening_dresses_v1'           => 0.85,
    'evening_hairstyles_v1'        => 0.88,
    'evening_makeup_v1'            => 0.96,     // доминирующий
    'bridal_makeup_v1'             => 0.70,
    'volumetric_lighting_v1'       => 0.84,
    'reflections_specular_v1'      => 0.78,
];
Примеры промптов:
Драматический:
textcatvrf evening makeup, bold dramatic smoky eyes with black liner and silver glitter, glowing radiant skin, deep matte red lips, luxury evening gown, dramatic lighting
Гламур:
textcatvrf evening makeup, red carpet full glam makeup with sharp cat eye, luminous highlighter and glossy nude lips, elegant sophisticated look
Султри:
textcatvrf evening makeup, sultry evening makeup with warm bronze eyeshadow, defined brows and glossy lips, soft moody lighting, cocktail party vibe
Negative Prompt:
textbad skin texture, plastic face, overdone makeup, blurry eyes, cartoonish, heavy filters, day makeup
6. Что должен вернуть ИИ

Полный конфиг обучения (evening_makeup_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Evening Dresses, Evening Hairstyles и Bridal Makeup LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт вечерние причёски премиум-уровня:

Голливудские локоны, sleek straight, high voluminous updos, messy elegant buns, braided crowns, wet look, finger waves
Объём, блеск, текстуру волос, отдельные пряди, идеальные укладки
Интеграцию с вечерними платьями, макияжем, украшениями (тиары, заколки, жемчуг, металлические элементы)
Разные стили: red carpet glamour, dark elegance, soft romantic, bold modern

LoRA идеально дополняет evening_dresses_v1, bridal_hairstyles_v1 и bridal_makeup_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с волосами, блеском и драпировкой)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Evening_Hairstyles_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 90
alpha: 45
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.6e-5
max_train_steps: 8800 - 13800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf evening hairstyle, glamorous evening hair, red carpet hair styling, elegant party updo

Дополнительные триггеры:

hollywood waves evening, sleek high ponytail, voluminous messy bun, wet look glamour
finger waves vintage, bold side swept, intricate braided evening style

4. Структура датасета
Рекомендуемый объём: 2300–3900 изображений
Структура папок:
textevening_hairstyles_dataset/
├── 01_hollywood_waves_and_curls/
├── 02_high_voluminous_updos/
├── 03_sleek_straight_and_low_buns/
├── 04_messy_elegant_buns/
├── 05_braided_and_intricate/
├── 06_wet_look_and_glossy/
├── 07_red_carpet_gala_styles/
├── 08_cocktail_party_hairstyles/
├── 09_with_accessories_and_jewelry/
└── captions.txt
Требования к фото:

Профессиональные вечерние съёмки (red carpet, gala, fashion editorials)
Много close-up и side/profile ракурсов
Драматическое освещение + блики на волосах

Примеры caption:
textcatvrf evening hairstyle, glamorous hollywood waves with deep side part and voluminous shine, red carpet elegance, dramatic lighting
5. Оптимальные настройки использования
В TextureGenerationService / FashionGenerationService:
PHP$loras = [
    'evening_dresses_v1'           => 0.88,
    'bridal_hairstyles_v1'         => 0.70,
    'evening_hairstyles_v1'        => 0.95,     // доминирующий
    'bridal_makeup_v1'             => 0.82,
    'volumetric_lighting_v1'       => 0.85,
];
Примеры промптов:
Голливудские волны:
textcatvrf evening hairstyle, stunning voluminous hollywood waves with glossy shine and deep side part, elegant evening gown, red carpet lighting
Высокий пучок:
textcatvrf evening hairstyle, sophisticated high sleek updo with face-framing strands, dramatic evening look, luxury gala atmosphere
Месси-бан:
textcatvrf evening hairstyle, romantic messy low bun with soft curls and pearl pins, soft candlelight, elegant cocktail party style
Negative Prompt:
textbad hair anatomy, frizzy hair, plastic strands, blurry details, cartoonish, flat hair, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (evening_hairstyles_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Evening Dresses, Bridal Hairstyles и Makeup LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт вечерние платья премиум-уровня:

Разные силуэты: Mermaid, Trumpet, Sheath, A-line, Ball gown, Slip dress, Off-shoulder, One-shoulder
Ткани: атлас, шёлк, бархат, шифон, пайетки, бисер, кружево, металлизированные
Эффекты: глубокий вырез, высокий разрез, открытая спина, драпировка, блеск под светом
Стили: red carpet, cocktail, gala, modern luxury, dark elegance, metallic shine

LoRA отлично дополняет wedding_dresses_v1 и подходит для бьюти/фэшн вертикалей.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая драпировка тканей и работа со светом)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Evening_Dresses_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9600 - 15200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf evening dress, realistic luxury evening gown, elegant cocktail dress, glamorous red carpet dress

Дополнительные триггеры:

sequin evening gown, satin slip dress, velvet off-shoulder dress, dramatic mermaid silhouette

4. Структура датасета
Рекомендуемый объём: 2500–4200 изображений
Структура папок:
textevening_dresses_dataset/
├── 01_mermaid_and_trumpet/
├── 02_sheath_and_column/
├── 03_a_line_and_ball_gown/
├── 04_slip_and_off_shoulder/
├── 05_sequin_and_metallic/
├── 06_velvet_and_rich_fabrics/
├── 07_dark_elegance_black_dresses/
├── 08_red_carpet_glam/
├── 09_cocktail_dresses/
└── captions.txt
Требования к фото:

Профессиональные вечерние съёмки (red carpet, gala, studio)
Много close-up тканей + full body shots
Разное освещение (драматическое, spotlight, golden hour)

Примеры caption:
textcatvrf evening dress, stunning emerald green satin mermaid evening gown with dramatic train and off-shoulder neckline, luxurious fabric shine, red carpet lighting
5. Оптимальные настройки использования
В TextureGenerationService / FashionGenerationService:
PHP$loras = [
    'wedding_dresses_v1'           => 0.65,
    'evening_dresses_v1'           => 0.95,     // доминирующий
    'soft_furniture_v1'            => 0.55,     // для поз на мебели
    'reflections_specular_v1'      => 0.85,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Классика:
textcatvrf evening dress, elegant black off-shoulder evening gown with slit and luxurious satin fabric, dramatic lighting, sophisticated woman pose
Металлик:
textcatvrf evening dress, shimmering gold sequin mermaid evening dress with deep V-neck, sparkling under lights, red carpet glamour
Бархат:
textcatvrf evening dress, rich burgundy velvet evening gown with long sleeves and elegant draping, moody luxury atmosphere
Negative Prompt:
textdeformed dress, bad fabric texture, plastic look, blurry details, cartoonish, low quality, unnatural folds
6. Что должен вернуть ИИ

Полный конфиг обучения (evening_dresses_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Dresses, Bridal Makeup и Reflections LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт вечерние платья премиум-уровня:

Разные силуэты: Mermaid, Trumpet, Sheath, A-line, Ball gown, Slip dress, Off-shoulder, One-shoulder
Ткани: атлас, шёлк, бархат, шифон, пайетки, бисер, кружево, металлизированные
Эффекты: глубокий вырез, высокий разрез, открытая спина, драпировка, блеск под светом
Стили: red carpet, cocktail, gala, modern luxury, dark elegance, metallic shine

LoRA отлично дополняет wedding_dresses_v1 и подходит для бьюти/фэшн вертикалей.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая драпировка тканей и работа со светом)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Evening_Dresses_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9600 - 15200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf evening dress, realistic luxury evening gown, elegant cocktail dress, glamorous red carpet dress

Дополнительные триггеры:

sequin evening gown, satin slip dress, velvet off-shoulder dress, dramatic mermaid silhouette

4. Структура датасета
Рекомендуемый объём: 2500–4200 изображений
Структура папок:
textevening_dresses_dataset/
├── 01_mermaid_and_trumpet/
├── 02_sheath_and_column/
├── 03_a_line_and_ball_gown/
├── 04_slip_and_off_shoulder/
├── 05_sequin_and_metallic/
├── 06_velvet_and_rich_fabrics/
├── 07_dark_elegance_black_dresses/
├── 08_red_carpet_glam/
├── 09_cocktail_dresses/
└── captions.txt
Требования к фото:

Профессиональные вечерние съёмки (red carpet, gala, studio)
Много close-up тканей + full body shots
Разное освещение (драматическое, spotlight, golden hour)

Примеры caption:
textcatvrf evening dress, stunning emerald green satin mermaid evening gown with dramatic train and off-shoulder neckline, luxurious fabric shine, red carpet lighting
5. Оптимальные настройки использования
В TextureGenerationService / FashionGenerationService:
PHP$loras = [
    'wedding_dresses_v1'           => 0.65,
    'evening_dresses_v1'           => 0.95,     // доминирующий
    'soft_furniture_v1'            => 0.55,     // для поз на мебели
    'reflections_specular_v1'      => 0.85,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Классика:
textcatvrf evening dress, elegant black off-shoulder evening gown with slit and luxurious satin fabric, dramatic lighting, sophisticated woman pose
Металлик:
textcatvrf evening dress, shimmering gold sequin mermaid evening dress with deep V-neck, sparkling under lights, red carpet glamour
Бархат:
textcatvrf evening dress, rich burgundy velvet evening gown with long sleeves and elegant draping, moody luxury atmosphere
Negative Prompt:
textdeformed dress, bad fabric texture, plastic look, blurry details, cartoonish, low quality, unnatural folds
6. Что должен вернуть ИИ

Полный конфиг обучения (evening_dresses_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Dresses, Bridal Makeup и Reflections LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт свадебный педикюр:

Идеальные формы ногтей на ногах (square, round, almond, coffin)
Классический French, nude, milky, chrome, glitter, floral micro-art
Сочетание с открытой обувью невесты (босоножки, туфли с открытым носом)
Реалистичную кожу ступней, блеск покрытия, объёмные элементы
Взаимодействие с платьем (вид из-под подола), букетом, кольцом и обувью

LoRA идеально дополняет bride_shoes_v1, bridal_manicure_v1 и wedding_dresses_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (отличная детализация кожи и мелких элементов)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Bridal_Pedicure_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 84
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 7800 - 12200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf bridal pedicure, realistic wedding toenails, elegant bride feet, detailed bridal pedicure

Дополнительные триггеры:

french pedicure bride, chrome wedding toes, soft nude pedicure, floral toe nail art
perfect pedicure in open sandals, glossy toes with ring detail

4. Структура датасета
Рекомендуемый объём: 1700–3000 изображений
Структура папок:
textbridal_pedicure_dataset/
├── 01_classic_french_pedicure/
├── 02_nude_and_milky_toes/
├── 03_chrome_and_shimmer/
├── 04_floral_and_micro_art/
├── 05_with_open_toe_shoes/
├── 06_luxury_glossy_pedicure/
├── 07_minimal_and_clean/
├── 08_full_foot_with_dress_and_shoes/
└── captions.txt
Требования к фото:

Макро-съёмка пальцев ног + полный кадр ступни в обуви/под платьем
Профессиональные bridal-фотосессии
Разное освещение (soft, studio, natural)

Примеры caption:
textcatvrf bridal pedicure, perfect classic French pedicure on elegant feet, glossy finish, wearing open toe wedding sandals, soft romantic lighting
5. Оптимальные настройки использования
В TextureGenerationService / WeddingGenerationService:
PHP$loras = [
    'bride_shoes_v1'               => 0.88,
    'bridal_manicure_v1'           => 0.82,
    'bridal_pedicure_v1'           => 0.94,     // высокий вес
    'wedding_dresses_v1'           => 0.70,
    'reflections_specular_v1'      => 0.85,
    'bridal_makeup_v1'             => 0.65,
];
Примеры промптов:
Классика:
textcatvrf bridal pedicure, beautiful French pedicure on well-groomed feet with glossy white tips, wearing delicate open toe wedding heels, visible under dress hem
Люкс-хром:
textcatvrf bridal pedicure, luxurious chrome mirror pedicure with subtle glitter, perfect almond toes, elegant bridal sandals, soft lighting
С цветочным акцентом:
textcatvrf bridal pedicure, delicate floral micro nail art on toes with nude base, fresh pedicure, bride standing in garden wedding shoes
Negative Prompt:
textdeformed toes, bad skin texture, plastic nails, blurry details, dirty feet, cartoonish, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (bridal_pedicure_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Bride Shoes, Bridal Manicure и Wedding Dresses LoRA

### 1. Цель
Создать высокодетализированный LoRA, который превосходно передаёт сложные ногтевые дизайны:

Интрикатные узоры, 3D-элементы, микро-живопись, стемпинг, градиенты, втирки
Темы: флористика, геометрия, мрамор, космос, акварель, кружево, хром + графика
Реалистичную текстуру: блеск, матовость, объём, толщину покрытия, блики
Разные длины и формы ногтей (almond, coffin, ballerina, square, stiletto)
Контекст: свадебный, вечерний, повседневный премиум, сезонные дизайны

LoRA отлично дополняет bridal_manicure_v1 и может использоваться как standalone для бьюти-вертикали.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (максимальная детализация мелких элементов)
Альтернатива: SDXL + Realistic Vision / Juggernaut XL
Название LoRA: CatVRF_Detailed_Nail_Art_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 96                    # максимальный rank — для сверхдетальных дизайнов
alpha: 48
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.4e-5
max_train_steps: 10500 - 15800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf detailed nail art, intricate nail design, realistic luxury manicure, complex nail artwork

Дополнительные триггеры:

3d floral nail art, micro painting nails, marble chrome nails, lace pattern manicure
crystal embellished nails, watercolor gradient, geometric luxury nails

4. Структура датасета
Рекомендуемый объём: 2600–4500 изображений (очень важно качество)
Структура папок:
textdetailed_nail_art_dataset/
├── 01_floral_and_botanical/
├── 02_geometric_and_abstract/
├── 03_marble_and_stone/
├── 04_chrome_and_mirror_designs/
├── 05_3d_embellished_and_crystals/
├── 06_lace_and_embroidery_nails/
├── 07_watercolor_and_gradient/
├── 08_wedding_and_bridal_nail_art/
├── 09_luxury_minimal/
├── 10_seasonal_and_thematic/
└── captions.txt
Требования к фото:

Макро-съёмка 1:1 и 2:1
Профессиональное студийное освещение + softbox
Руки в разных позах (с кольцом, с цветами, на фоне платья)

Примеры caption:
textcatvrf detailed nail art, intricate 3D floral nail design with hand-painted roses and crystals on almond nails, luxury glossy finish, macro photography
5. Оптимальные настройки использования
В TextureGenerationService / BeautyGenerationService:
PHP$loras = [
    'bridal_manicure_v1'           => 0.75,
    'detailed_nail_art_v1'         => 0.96,     // доминирующий
    'bridal_makeup_v1'             => 0.82,
    'reflections_specular_v1'      => 0.88,     // критично для блеска и хрома
    'floral_arrangements_v1'       => 0.55,
];
Примеры промптов:
Свадебный:
textcatvrf detailed nail art, exquisite bridal manicure with delicate white lace pattern and tiny crystals on long almond nails, soft elegant lighting
Люксовый 3D:
textcatvrf detailed nail art, luxurious 3D floral nail art with sculpted roses and gold accents on coffin shape, mirror chrome base, studio macro shot
Геометрия:
textcatvrf detailed nail art, modern geometric nail design with gold lines and marble effect, matte and glossy mix, luxury fashion style
Negative Prompt:
textblurry nails, deformed fingers, bad proportions, plastic texture, low detail, cartoonish, dirty nails
6. Что должен вернуть ИИ

Полный конфиг обучения (detailed_nail_art_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Bridal Manicure, Makeup и Floral LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт свадебный маникюр:

Классический French, nude, milky white, chrome, cat-eye, glitter, ombre
Формы ногтей: almond, ballerina, coffin, square, stiletto, round
Дизайны: минимализм, тонкие линии, флористика, жемчуг, кристаллы, кружевные узоры, 3D-элементы
Разная длина и покрытие (глянец, матовый, втирка, вельвет)
Взаимодействие с кольцом, букетом, платьем и руками невесты

LoRA идеально дополняет bridal_makeup_v1, wedding_dresses_v1 и floral_arrangements_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация мелких элементов и блеска)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Bridal_Manicure_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 85
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.9e-5
max_train_steps: 7900 - 12400
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf bridal manicure, realistic wedding nails, elegant bride nails, detailed bridal nail art

Дополнительные триггеры:

french manicure bride, chrome wedding nails, pearl nail design, floral nail art
nude glossy nails with ring, almond shape bridal nails

4. Структура датасета
Рекомендуемый объём: 2000–3400 изображений
Структура папок:
textbridal_manicure_dataset/
├── 01_classic_french/
├── 02_nude_and_milky/
├── 03_chrome_and_mirror/
├── 04_floral_and_lace_nail_art/
├── 05_pearl_and_crystal_3d/
├── 06_minimal_lines_and_geometry/
├── 07_with_wedding_ring/
├── 08_hands_with_bouquet_and_dress/
└── captions.txt
Требования к фото:

Макро-съёмка ногтей + руки в контексте (с кольцом, букетом, платьем)
Разные ракурсы и освещение (soft light, studio, golden hour)
Реальные свадебные маникюры премиум-уровня

Примеры caption:
textcatvrf bridal manicure, elegant classic French manicure on almond nails with subtle shimmer, sparkling engagement ring, soft natural lighting, luxury bridal hands
5. Оптимальные настройки использования
В TextureGenerationService / WeddingGenerationService:
PHP$loras = [
    'bridal_makeup_v1'             => 0.88,
    'bridal_hairstyles_v1'         => 0.75,
    'wedding_dresses_v1'           => 0.78,
    'bridal_manicure_v1'           => 0.94,     // высокий вес
    'reflections_specular_v1'      => 0.85,     // критично для блеска
    'floral_arrangements_v1'       => 0.60,
];
Примеры промптов:
Классика:
textcatvrf bridal manicure, perfect classic French manicure on long almond nails with glossy finish, beautiful engagement ring, soft romantic lighting, bride hands holding bouquet
Хром + жемчуг:
textcatvrf bridal manicure, luxurious chrome mirror nails with delicate pearl and crystal 3D decoration, elegant wedding style, close-up hands
Минимализм:
textcatvrf bridal manicure, soft milky nude manicure with thin gold lines and matte finish, modern minimalist bride, natural daylight
Negative Prompt:
textdeformed nails, bad proportions, plastic look, blurry details, cartoonish nails, dirty hands, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (bridal_manicure_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Bridal Makeup, Wedding Dresses и Veils LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт свадебный макияж:

Natural glow, soft glam, full glam, Korean-style, smoky eyes, romantic nude
Идеальная кожа (glowy skin, soft focus, subtle contouring)
Глаза: длинные ресницы, стрелки, смоки, блестящие тени
Губы: nude, mauve, red, glossy, matte
Хайлайтер, румяна, брови, идеальные переходы
Взаимодействие с освещением, вуалью, платьем и причёской

LoRA идеально замыкает свадебный образ вместе с wedding_dresses_v1, bridal_hairstyles_v1 и veils_bridal_accessories_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с кожей и тонкими косметическими текстурами)
Альтернатива: SDXL + Realistic Vision / EpicRealism
Название LoRA: CatVRF_Bridal_Makeup_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 90
alpha: 45
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.7e-5
max_train_steps: 8800 - 13800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf bridal makeup, realistic wedding makeup, elegant bride makeup, soft glam bridal look

Дополнительные триггеры:

dewy glowing skin, long lashes, soft pink nude lips, romantic eye makeup
luxury full glam bride, natural fresh makeup, Korean bridal makeup

4. Структура датасета
Рекомендуемый объём: 2200–3800 изображений
Структура папок:
textbridal_makeup_dataset/
├── 01_natural_fresh_makeup/
├── 02_soft_glam/
├── 03_full_glam_smoky/
├── 04_korean_style_bride/
├── 05_red_lips_dramatic/
├── 06_nude_and_dewy/
├── 07_with_veil_and_hairstyle/
├── 08_closeup_eyes_lips_skin/
└── captions.txt
Требования к фото:

Только профессиональный bridal макияж (high-end)
Много close-up (кожа, глаза, губы)
Разные углы и освещение (soft window, studio, golden hour)

Примеры caption:
textcatvrf bridal makeup, beautiful soft glam wedding makeup with glowing dewy skin, long lashes, subtle pink eyeshadow and nude glossy lips, elegant bride look
5. Оптимальные настройки использования
В TextureGenerationService / WeddingGenerationService:
PHP$loras = [
    'wedding_dresses_v1'           => 0.80,
    'bridal_hairstyles_v1'         => 0.85,
    'veils_bridal_accessories_v1'  => 0.78,
    'bride_shoes_v1'               => 0.50,
    'bridal_makeup_v1'             => 0.96,     // самый высокий вес
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Мягкий глам:
textcatvrf bridal makeup, stunning soft glam wedding makeup with radiant glowing skin, long fluttery lashes, soft rose eyeshadow and nude glossy lips, elegant bride portrait
Натуральный:
textcatvrf bridal makeup, fresh natural bridal makeup with dewy skin, subtle contour, defined brows and soft pink lips, outdoor garden wedding
Драматичный:
textcatvrf bridal makeup, bold dramatic wedding makeup with smoky eyes, sharp liner, red lips and flawless porcelain skin, luxury evening reception
Negative Prompt:
textbad skin texture, plastic face, overdone makeup, blurry eyes, cartoonish, low quality, heavy filters
6. Что должен вернуть ИИ

Полный конфиг обучения (bridal_makeup_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Dresses, Hairstyles и Veils LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт прически невесты:

Разные стили: высокий/низкий пучок, голливудские локоны, beach waves, braided updos, sleek straight, boho loose curls
Объём, текстуру волос, отдельные пряди, блеск
Интеграцию с аксессуарами (вуаль, тиара, hair vine, цветы, гребни, жемчуг)
Разные длины и типы волос (длинные, средние, короткие; прямые, волнистые, кудрявые)
Взаимодействие с лицом, платьем, освещением и движением (лёгкий ветер, поворот головы)

LoRA идеально дополняет wedding_dresses_v1, veils_bridal_accessories_v1 и bride_shoes_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с волосами и тонкими деталями)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Bridal_Hairstyles_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.6e-5
max_train_steps: 9200 - 14500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf bridal hairstyle, realistic wedding hair, elegant bride updo, detailed bridal hair styling

Дополнительные триггеры:

hollywood waves, messy romantic bun, boho braided crown, sleek low bun with veil
voluminous curls, soft beach waves, intricate braided hairstyle

4. Структура датасета
Рекомендуемый объём: 2400–4000 изображений
Структура папок:
textbridal_hairstyles_dataset/
├── 01_updos_and_buns/
├── 02_loose_waves_and_curls/
├── 03_braided_styles/
├── 04_sleek_straight_styles/
├── 05_boho_and_natural/
├── 06_with_veil_and_tiara/
├── 07_with_floral_hair_accessories/
├── 08_half_up_half_down/
├── 09_short_and_medium_hair/
└── captions.txt
Требования к фото:

Только профессиональные bridal-фотосессии
Много close-up и side/profile ракурсов
Разное освещение (soft window light, golden hour, studio)

Примеры caption:
textcatvrf bridal hairstyle, elegant low messy bun with soft face-framing curls and delicate lace veil, voluminous texture, romantic natural lighting
5. Оптимальные настройки использования
В TextureGenerationService / WeddingGenerationService:
PHP$loras = [
    'wedding_dresses_v1'           => 0.82,
    'veils_bridal_accessories_v1'  => 0.88,
    'bridal_hairstyles_v1'         => 0.95,     // очень высокий вес
    'bride_shoes_v1'               => 0.55,
    'volumetric_lighting_v1'       => 0.80,
    'floral_arrangements_v1'       => 0.60,
];
Примеры промптов:
Классический пучок:
textcatvrf bridal hairstyle, sophisticated low elegant bun with loose strands and crystal hairpins, paired with cathedral veil, soft romantic lighting, luxury bride
Голливудские локоны:
textcatvrf bridal hairstyle, voluminous hollywood waves with side part and delicate tiara, flowing long hair, golden hour outdoor wedding
Бохо:
textcatvrf bridal hairstyle, boho braided crown with fresh flowers and loose waves, natural outdoor ceremony, soft sunlight
Negative Prompt:
textbad hair anatomy, plastic hair, blurry strands, deformed face, low quality, cartoonish, unnatural volume
6. Что должен вернуть ИИ

Полный конфиг обучения (bridal_hairstyles_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Dresses, Veils и Bridal Accessories LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт обувь невесты:

Классические туфли-лодочки, босоножки, сапожки, балетки
Материалы: атлас, кружево, кожа, замша, прозрачный пластик, хрустальные украшения
Детали: блеск, стразы, жемчуг, бантики, перья, высокие каблуки, платформы, удобные низкие модели
Взаимодействие с платьем (вид из-под подола), позой невесты, освещением (блики на атласе и кристаллах)

LoRA идеально дополняет wedding_dresses_v1, veils_bridal_accessories_v1 и wedding_decor_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (отлично работает с тканями, блеском и мелкими деталями)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Bride_Shoes_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 86
alpha: 43
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 8100 - 12600
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf bride shoes, realistic wedding shoes, elegant bridal heels, detailed bridal footwear

Дополнительные триггеры:

satin wedding pumps, crystal embellished heels, lace bridal sandals, ivory satin shoes
sparkling strappy heels, comfortable low wedding heels, luxury bridal footwear

4. Структура датасета
Рекомендуемый объём: 1800–3200 изображений
Структура папок:
textbride_shoes_dataset/
├── 01_classic_pumps_and_stilettos/
├── 02_strappy_sandals/
├── 03_lace_and_embroidered_shoes/
├── 04_crystal_and_pearl_embellished/
├── 05_ivory_and_white_satin/
├── 06_boho_and_rustic_bride_shoes/
├── 07_comfortable_low_heels_and_flats/
├── 08_with_dress_details/
└── captions.txt
Требования к фото:

Профессиональные bridal съёмки (close-up + full foot in dress)
Много macro текстур (атлас, кружево, блеск камней)
Разные ракурсы и освещение (особенно backlight для бликов)

Примеры caption:
textcatvrf bride shoes, elegant ivory satin wedding heels with delicate crystal embellishments, soft fabric texture and sparkling details, luxury bridal footwear
5. Оптимальные настройки использования
В TextureGenerationService / WeddingGenerationService:
PHP$loras = [
    'wedding_dresses_v1'           => 0.85,
    'veils_bridal_accessories_v1'  => 0.75,
    'bride_shoes_v1'               => 0.94,     // высокий вес
    'reflections_specular_v1'      => 0.82,     // критично для блеска
    'floral_arrangements_v1'       => 0.55,
];
Примеры промптов:
Классические лодочки:
textcatvrf bride shoes, beautiful ivory satin wedding pumps with subtle crystal buckle, elegant pointed toe, visible under wedding dress hem, soft romantic lighting
Босоножки:
textcatvrf bride shoes, delicate strappy crystal wedding sandals with sparkling stones, perfect for summer wedding, detailed foot and dress interaction
Комфортный вариант:
textcatvrf bride shoes, stylish low block heel bridal shoes in soft satin with pearl details, comfortable yet elegant, garden wedding style
Negative Prompt:
textdeformed shoes, bad proportions, plastic look, blurry details, cartoonish, low quality, dirty shoes
6. Что должен вернуть ИИ

Полный конфиг обучения (bride_shoes_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Dresses, Veils и Floral LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт вуали и свадебные аксессуары:

Все типы вуалей (cathedral, chapel, fingertip, elbow, birdcage, mantilla)
Текстуры: тонкий тюль, кружевная кайма, вышивка, фата с блёстками, 3D-флористика
Аксессуары: тиары, гребни, заколки, серьги, ожерелья, пояса, перчатки, подвязки, hair vines, броши
Взаимодействие с платьем, волосами, освещением и движением (лёгкое колыхание вуали)

LoRA идеально дополняет wedding_dresses_v1, floral_arrangements_v1 и wedding_decor_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с прозрачными тканями и мелкими деталями)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Veils_Bridal_Accessories_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 88
alpha: 44
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.8e-5
max_train_steps: 8400 - 12900
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf bridal veil, realistic wedding veil, elegant bridal accessories, detailed lace veil

Дополнительные триггеры:

cathedral length veil, fingertip veil with lace edge, birdcage veil, tiara with crystals
delicate hair vine, pearl earrings, satin belt, embroidered mantilla veil

4. Структура датасета
Рекомендуемый объём: 2100–3600 изображений
Структура папок:
textveils_accessories_dataset/
├── 01_cathedral_and_long_veils/
├── 02_fingertip_and_elbow_veils/
├── 03_birdcage_and_short_veils/
├── 04_lace_and_embroidery_veils/
├── 05_tiaras_and_crowns/
├── 06_hair_vines_and_combs/
├── 07_earrings_necklaces_belts/
├── 08_gloves_and_garters/
├── 09_full_bridal_look_with_accessories/
└── captions.txt
Требования к фото:

Профессиональные bridal съёмки
Много macro-деталей (тюль, кружево, блеск камней, прозрачность)
Разные ракурсы и освещение (backlight особенно важен для вуалей)

Примеры caption:
textcatvrf bridal veil, long cathedral wedding veil with delicate lace edge and soft tulle, flowing in gentle wind, realistic fabric texture, romantic lighting
5. Оптимальные настройки использования
В TextureGenerationService / WeddingGenerationService:
PHP$loras = [
    'wedding_dresses_v1'           => 0.88,
    'veils_bridal_accessories_v1'  => 0.95,     // очень высокий вес
    'floral_arrangements_v1'       => 0.65,
    'wedding_decor_v1'             => 0.60,
    'reflections_specular_v1'      => 0.80,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Вуаль:
textcatvrf bridal veil, elegant long cathedral veil with intricate lace trim and soft flowing tulle, attached to updo hairstyle, soft natural window light, romantic bridal look
Тиара + вуаль:
textcatvrf bridal veil, luxurious crystal tiara with matching cathedral veil, sparkling details, elegant princess style, luxury wedding photography
Комплекс аксессуаров:
textcatvrf bridal veil, delicate pearl hair vine and matching earrings with fingertip veil, boho chic bridal accessories, soft outdoor lighting
Negative Prompt:
textdeformed veil, plastic look, blurry lace, bad transparency, cartoonish, low quality, unnatural folds
6. Что должен вернуть ИИ

Полный конфиг обучения (veils_bridal_accessories_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Dresses, Floral Arrangements и Wedding Decor LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт свадебные платья премиум-класса:

Разные силуэты: A-line, Mermaid, Ball Gown, Sheath, Empire, Trumpet
Ткани: кружево, атлас, фатин, шифон, органза, шёлк, 3D-флористика
Детали: вышивка, бисер, жемчуг, аппликации, длинный шлейф, вуаль
Стили: классика, boho, modern minimal, royal luxury, rustic
Реалистичную драпировку, игру света на ткани, объём, текстуру кружева

LoRA идеально дополняет wedding_decor_v1, floral_arrangements_v1 и festive_interior_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа со сложными тканями и драпировкой)
Альтернатива: SDXL + Realistic Vision / EpicRealism
Название LoRA: CatVRF_Wedding_Dresses_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9800 - 14800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf wedding dress, realistic bridal gown, elegant wedding gown, detailed lace wedding dress

Дополнительные триггеры:

princess ball gown, mermaid silhouette, boho lace dress, luxury beaded wedding dress
long train, cathedral veil, intricate embroidery, soft fabric flow

4. Структура датасета
Рекомендуемый объём: 2600–4200 изображений (очень важно качество)
Структура папок:
textwedding_dresses_dataset/
├── 01_a_line_dresses/
├── 02_mermaid_trumpet/
├── 03_ball_gown_princess/
├── 04_boho_lace_dresses/
├── 05_luxury_beaded_and_3d/
├── 06_minimalist_modern/
├── 07_vintage_and_retro/
├── 08_with_veil_and_train/
├── 09_detail_shots_lace_fabric/
└── captions.txt
Требования к фото:

Только профессиональные свадебные съёмки (high-end)
Много close-up текстур ткани, кружева, бисера
Разные ракурсы: full body, back view, detail shots, movement

Примеры caption:
textcatvrf wedding dress, stunning princess ball gown with voluminous tulle skirt and intricate lace bodice, long cathedral train, soft natural lighting, luxury bridal
5. Оптимальные настройки использования
В TextureGenerationService / WeddingGenerationService:
PHP$loras = [
    'wedding_decor_v1'             => 0.75,
    'floral_arrangements_v1'       => 0.70,
    'wedding_dresses_v1'           => 0.96,     // очень высокий вес
    'soft_furniture_v1'            => 0.55,     // для диванов/кресел на фото
    'reflections_specular_v1'      => 0.78,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Классическое платье:
textcatvrf wedding dress, elegant A-line wedding gown with delicate lace sleeves and flowing skirt, soft romantic lighting, beautiful bride pose
Морской стиль (mermaid):
textcatvrf wedding dress, luxurious mermaid wedding dress with heavy beading and dramatic train, figure-hugging silhouette, luxury hotel interior
Бохо:
textcatvrf wedding dress, boho chic wedding dress with flowing chiffon and floral lace, outdoor garden ceremony, natural sunlight
Negative Prompt:
textdeformed dress, bad anatomy, plastic fabric, blurry lace, cartoonish, low quality, unnatural folds
6. Что должен вернуть ИИ

Полный конфиг обучения (wedding_dresses_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Decor, Floral и Festive LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт праздничные воздушные шары:

Обычные латексные, фольгированные, хромированные, прозрачные, с конфетти
Букеты шаров, арки, колонны, потолочные композиции
Разные события: День рождения, свадьба, гендер-пати, корпоратив, Новый год, детские праздники
Реалистичную физику: отражения, блики, складки, гелиевый подъём, ленты
Взаимодействие с освещением (блики на фольге, полупрозрачность, объём)

LoRA идеально дополняет festive_banners_v1, floral_arrangements_v1 и wedding_decor_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (отлично работает с отражениями и объёмными объектами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Festive_Balloons_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 84
alpha: 42
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.9e-5
max_train_steps: 7800 - 12200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf festive balloons, realistic party balloons, colorful helium balloons, celebration balloon bouquet

Дополнительные триггеры:

chrome balloons, transparent balloons with confetti, balloon arch, balloon column
birthday balloon bunch, luxury gold and white balloons, floating helium balloons

4. Структура датасета
Рекомендуемый объём: 1900–3300 изображений
Структура папок:
textfestive_balloons_dataset/
├── 01_standard_latex_balloons/
├── 02_foil_chrome_balloons/
├── 03_transparent_confetti_balloons/
├── 04_balloon_bouquets_and_bunches/
├── 05_balloon_arches_and_columns/
├── 06_birthday_party_balloons/
├── 07_wedding_and_luxury_balloons/
├── 08_kids_party_balloons/
├── 09_corporate_event_balloons/
└── captions.txt
Требования к фото:

Профессиональные съёмки с хорошим освещением
Close-up текстур (глянец фольги, матовость латекса, отражения)
Разные состояния: в воздухе, в связках, арки, на потолке

Примеры caption:
textcatvrf festive balloons, large colorful helium balloon bouquet with gold and white chrome balloons, realistic reflections and ribbons, bright party lighting
5. Оптимальные настройки использования
В TextureGenerationService / FestiveGenerationService:
PHP$loras = [
    'festive_interior_v1'          => 0.75,
    'festive_banners_v1'           => 0.68,
    'floral_arrangements_v1'       => 0.60,
    'festive_balloons_v1'          => 0.95,     // очень высокий вес
    'reflections_specular_v1'      => 0.85,
    'volumetric_lighting_v1'       => 0.82,
];
Примеры промптов:
Букет шаров:
textcatvrf festive balloons, big joyful helium balloon bouquet with pastel and chrome balloons, floating in decorated room, bright festive atmosphere, realistic reflections
Арка:
textcatvrf festive balloons, impressive balloon arch with gold and white balloons for wedding entrance, soft natural lighting, elegant celebration setup
Детский праздник:
textcatvrf festive balloons, colorful kids party balloon arrangement with cartoon characters and bright latex balloons, joyful and playful atmosphere
Negative Prompt:
textdeflated balloons, bad reflections, plastic look, deformed shapes, blurry, low quality, dark lighting
6. Что должен вернуть ИИ

Полный конфиг обучения (festive_balloons_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Festive Banners, Floral Arrangements и Wedding Decor LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который отлично передаёт праздничные баннеры и вывески:

Тканевые растяжки, бумажные баннеры, фольгированные, LED-баннеры, неоновые вывески
Разные события: День рождения, Новый год, свадьба, юбилей, корпоратив, 8 марта, 23 февраля
Реалистичную текстуру материалов (глянец фольги, матовая ткань, гофрокартон, объёмные буквы)
Эффекты: складки ткани, блики, отражения, подсветка, конфетти, воздушные шары рядом
Интеграцию текста (читаемый, но без генерации конкретных слов — только стиль)

2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всего справляется с текстурами и освещением)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Festive_Banners_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 82
alpha: 41
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 5.1e-5
max_train_steps: 7600 - 11800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 700
Trigger words (основные):

catvrf festive banner, realistic party banner, celebration sign, holiday decoration banner

Дополнительные триггеры:

happy birthday banner, luxury wedding welcome sign, New Year festive stretch, corporate event banner
glossy foil banner, fabric with folds, illuminated LED banner, voluminous 3D letters

4. Структура датасета
Рекомендуемый объём: 1700–2900 изображений
Структура папок:
textfestive_banners_dataset/
├── 01_birthday_banners/
├── 02_wedding_welcome_signs/
├── 03_new_year_and_holiday_banners/
├── 04_corporate_event_banners/
├── 05_luxury_gold_silver_banners/
├── 06_fabric_and_draped_banners/
├── 07_led_and_neon_signs/
├── 08_kids_party_banners/
└── captions.txt
Требования к фото:

Реальные праздничные баннеры в интерьере и на улице
Close-up текстур (фольга, ткань, печать)
Разное освещение (день, вечер, с подсветкой)

Примеры caption:
textcatvrf festive banner, elegant gold happy birthday banner with voluminous letters and soft fabric folds, warm party lighting, realistic decoration
5. Оптимальные настройки использования
В TextureGenerationService / FestiveGenerationService:
PHP$loras = [
    'festive_interior_v1'          => 0.78,
    'floral_arrangements_v1'       => 0.65,
    'wedding_decor_v1'             => 0.70,
    'festive_banners_v1'           => 0.94,     // высокий вес
    'reflections_specular_v1'      => 0.82,
    'volumetric_lighting_v1'       => 0.80,
];
Примеры промптов:
День рождения:
textcatvrf festive banner, large colorful happy birthday banner with gold foil letters and balloons, hanging in decorated room, bright festive atmosphere
Свадьба:
textcatvrf festive banner, elegant white and gold "Welcome to our wedding" fabric banner with floral decoration, soft romantic lighting
Новый год:
textcatvrf festive banner, luxurious New Year banner with sparkling gold text and snow elements, festive interior with lights
Negative Prompt:
textblurry text, cheap plastic banner, deformed letters, bad printing, dark dull colors, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (festive_banners_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Wedding Decor, Floral Arrangements и Festive Interior LoRA

### 1. Цель
Создать мощный специализированный LoRA, который превосходно передаёт тюнингованные автомобили:

Внешний тюнинг (обвесы, спойлеры, арки, капоты, диски, оптика)
Окраска (матовая, глянец, хром, графика, airbrush, wrapping)
Салон (перетяжка, подсветка, мультимедиа, ковши, карбон)
Стенс, дрифт, stance, JDM, VIP, off-road, luxury tuning
Реалистичные отражения, блики на хроме, дым из-под колёс, подсветка неоном

LoRA будет критически важен для вертикалей автосервис, детейлинг, продажа авто, тюнинг-ателье.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с металлом, отражениями и сложными формами)
Альтернатива: SDXL + Realistic Vision / Juggernaut XL
Название LoRA: CatVRF_Car_Tuning_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.4e-5
max_train_steps: 10200 - 15800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf car tuning, realistic tuned car, aggressive widebody kit, stance car

Дополнительные триггеры:

JDM tuned, luxury wrapped car, carbon fiber hood, air suspension stance, neon underglow

4. Структура датасета
Рекомендуемый объём: 2800–4800 изображений (очень важно качество)
Структура папок:
textcar_tuning_dataset/
├── 01_widebody_kit_and_aero/
├── 02_stance_and_air_suspension/
├── 03_jdm_culture/
├── 04_luxury_tuning/
├── 05_drift_cars/
├── 06_offroad_tuning/
├── 07_interior_tuning/
├── 08_wrap_and_custom_paint/
├── 09_night_shots_with_lights/
└── captions.txt
Требования к фото:

Профессиональные съёмки тюнингованных авто (low angle, wheel close-up, night shots)
Много деталей (карбон, швы, диски, выхлоп)
Разные углы и освещение (день, закат, неон ночью)

Примеры caption:
textcatvrf car tuning, aggressive widebody Toyota Supra with carbon fiber kit, matte black wrap, deep dish wheels, stance suspension, dramatic lighting
5. Оптимальные настройки использования
В TextureGenerationService / AutoGenerationService:
PHP$loras = [
    'metal_furniture_v1'           => 0.45,   // для деталей
    'reflections_specular_v1'      => 0.88,
    'car_tuning_v1'                => 0.96,   // доминирующий
    'volumetric_lighting_v1'       => 0.82,
    'color_grading_master_v1'      => 0.78,
];
Примеры промптов:
JDM:
textcatvrf car tuning, highly detailed JDM tuned Nissan Silvia S15 with widebody kit, aggressive stance, bright neon underglow, night city background
Люкс:
textcatvrf car tuning, luxury Mercedes S-Class with full carbon body kit, mirror black wrap, huge forged wheels, elegant evening lighting
Дрифт:
textcatvrf car tuning, drift BMW E46 with slammed suspension, smoke from tires, aggressive widebody and spoiler, dynamic action shot
Negative Prompt:
textstock car, deformed body, bad wheels, blurry details, cartoonish, low quality, fake plastic look
6. Что должен вернуть ИИ

Полный конфиг обучения (car_tuning_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Reflections, Metal и Lighting LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который превосходно передаёт профессиональный автодетейлинг:

Идеальный глянец кузова, керамическое покрытие, вода в каплях (water beading)
Глубокий блеск, отражения, «мокрый эффект», устранение swirl marks
Детейлинг салона (кожа, алькантара, пластик, потолок)
Моторный отсек, диски, выхлоп, стёкла, хром
Before/After, процесс (нанесение состава, полировка, финальный shine)

LoRA критически важен для вертикали автосервис, детейлинг-студии, продажа авто.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая работа с отражениями, бликами и текстурами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Car_Detailing_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 93
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9800 - 15200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf car detailing, professional auto detailing, glossy ceramic coated car, deep shine car

Дополнительные триггеры:

water beading on paint, mirror finish, showroom shine, interior detailing
polished wheels, engine bay detail, hydrophobic effect, luxury car cleaning

4. Структура датасета
Рекомендуемый объём: 2600–4400 изображений
Структура папок:
textcar_detailing_dataset/
├── 01_exterior_gloss_and_ceramic/
├── 02_water_beading_and_hydrophobic/
├── 03_interior_leather_and_alcantara/
├── 04_wheels_and_brake_calipers/
├── 05_engine_bay_detailing/
├── 06_glass_and_chrome/
├── 07_before_after/
├── 08_night_shots_with_reflections/
├── 09_luxury_cars_showroom_shine/
└── captions.txt
Требования к фото:

Профессиональные съёмки детейлинг-студий
Много макро (капли воды, блики, текстура кожи)
Разные освещения (солнце, студия, неон ночью)

Примеры caption:
textcatvrf car detailing, luxury black Mercedes with perfect ceramic coating and insane water beading, deep mirror shine, professional studio lighting
5. Оптимальные настройки использования
В TextureGenerationService / AutoDetailingService:
PHP$loras = [
    'car_tuning_v1'                => 0.60,
    'reflections_specular_v1'      => 0.90,     // критично
    'car_detailing_v1'             => 0.96,     // доминирующий
    'volumetric_lighting_v1'       => 0.82,
    'color_grading_master_v1'      => 0.85,
];
Примеры промптов:
Кузов:
textcatvrf car detailing, stunning deep black ceramic coated car with perfect water beading after wash, insane glossy mirror finish, bright sunlight
Салон:
textcatvrf car detailing, luxury car interior detailing with restored leather, alcantara ceiling and glowing screens, showroom clean
Диски + тормоза:
textcatvrf car detailing, perfectly polished forged wheels and red brake calipers, mirror shine, professional detailing shot
Negative Prompt:
textdirty car, matte dull paint, water spots, swirl marks, bad reflections, low quality, plastic look
6. Что должен вернуть ИИ

Полный конфиг обучения (car_detailing_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Car Tuning, Reflections и Lighting LoRA

### 1. Цель
Создать ультраспециализированный LoRA, который идеально передаёт эффект профессионального керамического покрытия на автомобилях:

Глубокий «мокрый» глянец и зеркальный эффект
Hydrophobic water beading (идеальные круглые капли, которые скатываются)
Защита ЛКП, усиление глубины цвета, устранение swirl marks
Разные типы покрытий: 9H, 10H, графеновое, кварцевое, многослойное
Визуальные эффекты: блики, отражения неба/окружения, «glass-like» поверхность

LoRA станет ключевым дополнением к car_detailing_v1 и car_tuning_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с отражениями и жидкостями)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Ceramic_Coating_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 95
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.3e-5
max_train_steps: 9800 - 15500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf ceramic coating, professional ceramic coated car, deep glossy ceramic finish, hydrophobic car surface

Дополнительные триггеры:

9H ceramic coating, insane water beading, mirror like paint, glass-like car shine
graphene coating, multi layer ceramic, showroom ceramic protection

4. Структура датасета
Рекомендуемый объём: 2400–4200 изображений
Структура папок:
textceramic_coating_dataset/
├── 01_water_beading_macro/
├── 02_deep_gloss_and_reflections/
├── 03_black_and_dark_cars/
├── 04_white_and_light_cars/
├── 05_colored_cars_ceramic/
├── 06_night_ceramic_shots/
├── 07_before_after_ceramic/
├── 08_engine_bay_and_interior_protection/
└── captions.txt
Требования к фото:

Только реальные профессиональные съёмки после нанесения керамики
Обязательно макро-капли воды (идеально круглые, не растекаются)
Разные углы, освещение и цвета автомобилей

Примеры caption:
textcatvrf ceramic coating, luxury black car with perfect 10H ceramic coating, insane water beading with perfect spherical droplets, deep mirror gloss, professional detailing
5. Оптимальные настройки использования
В TextureGenerationService / AutoDetailingService:
PHP$loras = [
    'car_detailing_v1'             => 0.78,
    'ceramic_coating_v1'           => 0.96,     // доминирующий
    'reflections_specular_v1'      => 0.92,     // критично
    'volumetric_lighting_v1'       => 0.85,
    'color_grading_master_v1'      => 0.80,
];
Примеры промптов:
Чёрный автомобиль:
textcatvrf ceramic coating, stunning deep black car with professional ceramic coating, perfect water beading and mirror-like reflections, bright sunlight, ultra glossy finish
Белый автомобиль:
textcatvrf ceramic coating, pearl white luxury SUV with multi-layer ceramic protection, crystal clear water droplets rolling off, showroom shine
Ночной эффект:
textcatvrf ceramic coating, red sports car with graphene ceramic coating under night lights, intense reflections and hydrophobic surface, dramatic lighting
Negative Prompt:
textmatte paint, water spots, dull surface, flat finish, bad reflections, plastic look, dirty car
6. Что должен вернуть ИИ

Полный конфиг обучения (ceramic_coating_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Car Detailing, Reflections и Tuning LoRA

### 1. Цель
Создать премиум-специализированный LoRA, который идеально передаёт эффект профессионального графенового покрытия — самого современного и технологичного вида защиты кузова:

Ультра-глубокий «стеклянный» глянец с «жидким» эффектом
Максимальную гидрофобность (вода скатывается мгновенно, почти как ртуть)
Теплоотвод и антистатический эффект (меньше пыли)
Усиление глубины цвета + металлический/алмазный блик
Долговечность и «шелковистость» поверхности
Отличия от керамики: более «жидкий» блеск, холодный металлический оттенок, повышенная стойкость к высоким температурам

LoRA станет топовым продуктом в вертикали автодетейлинг премиум-сегмента.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех справляется с сложными отражениями и «жидким» блеском)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Graphene_Coating_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 96
alpha: 48
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.3e-5
max_train_steps: 10800 - 16200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 850
Trigger words (основные):

catvrf graphene coating, professional graphene car coating, ultra slick graphene finish, advanced graphene protection

Дополнительные триггеры:

graphene ceramic hybrid, liquid graphene shine, extreme water repellency, diamond-like gloss
thermal graphene coating, anti-static graphene surface

4. Структура датасета
Рекомендуемый объём: 2200–4000 изображений (высокое качество критично)
Структура папок:
textgraphene_coating_dataset/
├── 01_extreme_water_beading/
├── 02_deep_liquid_gloss/
├── 03_graphene_on_black_cars/
├── 04_graphene_on_white_and_colors/
├── 05_thermal_resistance_tests/
├── 06_night_and_light_reflections/
├── 07_graphene_vs_ceramic_comparison/
├── 08_engine_bay_and_interior_graphene/
└── captions.txt
Требования к фото:

Только реальные нанесения графена/графен-керамики
Обязательно макро-капли (очень круглые, быстро скатываются)
Сравнительные съёмки до/после и с керамикой
Разные углы и освещение (особенно контрастный свет)

Примеры caption:
textcatvrf graphene coating, luxury black car with premium graphene coating, extreme water beading with perfect spherical droplets instantly rolling off, ultra deep liquid mirror shine
5. Оптимальные настройки использования
В TextureGenerationService / AutoDetailingService:
PHP$loras = [
    'car_detailing_v1'             => 0.70,
    'ceramic_coating_v1'           => 0.75,
    'graphene_coating_v1'          => 0.97,     // максимальный вес
    'reflections_specular_v1'      => 0.93,
    'volumetric_lighting_v1'       => 0.84,
    'color_grading_master_v1'      => 0.82,
];
Примеры промптов:
Чёрный автомобиль:
textcatvrf graphene coating, stunning black supercar with professional multi-layer graphene coating, insane hydrophobic effect and diamond-like liquid gloss, bright sunlight
Белый автомобиль:
textcatvrf graphene coating, pearl white Porsche with graphene protection, perfect water beading, ultra slick surface, showroom-level depth and shine
Ночной эффект:
textcatvrf graphene coating, deep blue BMW with graphene coating under city night lights, extreme reflections and anti-static clean surface, dramatic cinematic lighting
Negative Prompt:
textdull matte finish, water spots, uneven coating, ceramic-like only, low gloss, plastic look, dirty surface
6. Что должен вернуть ИИ

Полный конфиг обучения (graphene_coating_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Ceramic Coating, Car Detailing и Reflections LoRA

### Техническое задание (ТЗ) для ИИ: Специализированный LoRA для PPF покрытий (Paint Protection Film LoRA) в CatVRF
Автор ТЗ: Сенсей (ex-Amazon, Alibaba, Ozon)
Дата: 18 апреля 2026
1. Цель
Создать высокотехнологичный специализированный LoRA, который идеально передаёт эффект профессионального PPF (Paint Protection Film) — плёнки защиты кузова:

Прозрачная, почти невидимая защитная плёнка с «мокрым» глубоким глянцем
Self-healing эффект (самовосстановление мелких царапин)
Гидрофобность + антигравийная защита
Разные виды PPF: глянцевый, матовый (satin/matte), цветной, с анти-царапинным топкоутом
Визуальные особенности: лёгкая толщина плёнки, идеально ровная поверхность, видимые швы на стыках (при close-up), усиление глубины цвета

LoRA отлично дополняет ceramic_coating_v1, graphene_coating_v1 и car_detailing_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех передаёт прозрачные слои и сложные отражения)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_PPF_Protection_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.4e-5
max_train_steps: 10200 - 15800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf ppf coating, paint protection film, professional PPF wrap, self-healing clear bra

Дополнительные триггеры:

full front PPF, matte PPF finish, glossy PPF with deep wet look, anti-stone chip protection
invisible PPF layer, thick clear film, self-healing paint protection

4. Структура датасета
Рекомендуемый объём: 2500–4300 изображений
Структура папок:
textppf_coating_dataset/
├── 01_full_body_ppf/
├── 02_front_end_ppf_clear_bra/
├── 03_matte_ppf_finish/
├── 04_glossy_ppf_deep_shine/
├── 05_self_healing_demo/
├── 06_water_beading_on_ppf/
├── 07_before_after_ppf/
├── 08_closeup_edges_and_seams/
├── 09_luxury_cars_full_ppf/
└── captions.txt
Требования к фото:

Реальные съёмки автомобилей после установки PPF (XPEL, 3M, LLumar и т.д.)
Обязательно макро швов, краёв плёнки и water beading
Разные углы, освещение и типы машин

Примеры caption:
textcatvrf ppf coating, luxury black car with full body professional PPF protection, deep wet glossy look, perfect water beading, invisible thick clear film, high-end detailing
5. Оптимальные настройки использования
В TextureGenerationService / AutoDetailingService:
PHP$loras = [
    'car_detailing_v1'             => 0.72,
    'ceramic_coating_v1'           => 0.68,
    'graphene_coating_v1'          => 0.55,
    'ppf_protection_v1'            => 0.96,     // доминирующий
    'reflections_specular_v1'      => 0.90,
    'volumetric_lighting_v1'       => 0.83,
];
Примеры промптов:
Полная оклейка:
textcatvrf ppf coating, full body PPF on white Range Rover, ultra deep glossy wet look with perfect hydrophobic effect, invisible protection layer, luxury showroom shine
Передняя часть (Clear Bra):
textcatvrf ppf coating, professional front end PPF clear bra on red sports car, deep reflections, self-healing surface, bright sunlight
Матовый PPF:
textcatvrf ppf coating, aggressive widebody car with full matte PPF protection, satin finish with excellent water beading, modern stance look
Negative Prompt:
textscratched paint, dull surface, visible orange peel, cheap film look, bubbles under film, low gloss, dirty car
6. Что должен вернуть ИИ

Полный конфиг обучения (ppf_protection_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Ceramic, Graphene и Car Detailing LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который идеально передаёт матовый винил (матовая плёнка) на автомобилях:

Глубокий матовый эффект (soft-touch, velvet-like, anti-glare)
Разные виды: matte black, satin, camouflage, color-shift matte, textured matte
Реалистичную текстуру плёнки (лёгкая зернистость, отсутствие бликов, приглушённые отражения)
Взаимодействие с освещением (мягкие тени, минимальные блики, насыщенность цвета)
Эффекты оклейки: швы, стыки, растяжка на сложных поверхностях, воздушные пузыри (при необходимости)

LoRA отлично дополняет car_tuning_v1, ppf_protection_v1 и car_detailing_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всего работает с матовыми поверхностями и текстурами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Matte_Vinyl_Wrap_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 93
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9600 - 14800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf matte vinyl, matte car wrap, professional matte vinyl wrap, satin matte finish

Дополнительные триггеры:

matte black wrap, satin gray vinyl, camouflage matte wrap, deep matte texture
soft touch matte surface, anti-reflective car wrap

4. Структура датасета
Рекомендуемый объём: 2500–4200 изображений
Структура папок:
textmatte_vinyl_dataset/
├── 01_matte_black_wrap/
├── 02_satin_and_soft_matte/
├── 03_camouflage_and_patterned/
├── 04_color_matte_wraps/
├── 05_full_body_matte_vinyl/
├── 06_closeup_texture_and_seams/
├── 07_matte_with_tuning_kit/
├── 08_before_after_wrap/
└── captions.txt
Требования к фото:

Профессиональные съёмки автомобилей в матовом виниле
Обязательно close-up текстуры и швов
Разные углы, освещение (день, пасмурно, студия)

Примеры caption:
textcatvrf matte vinyl, aggressive widebody car with deep matte black vinyl wrap, soft touch texture, minimal reflections, professional installation
5. Оптимальные настройки использования
В TextureGenerationService / AutoTuningService:
PHP$loras = [
    'car_tuning_v1'                => 0.82,
    'ppf_protection_v1'            => 0.45,
    'matte_vinyl_wrap_v1'          => 0.96,     // доминирующий
    'reflections_specular_v1'      => 0.55,     // сниженный вес — матовый эффект
    'color_grading_master_v1'      => 0.80,
];
Примеры промптов:
Матовый чёрный:
textcatvrf matte vinyl, stunning widebody sports car in deep matte black vinyl wrap, soft velvet texture, aggressive stance, minimal reflections, dramatic lighting
Сатин:
textcatvrf matte vinyl, luxury SUV with elegant satin gray matte vinyl wrap, beautiful soft finish, clean lines, modern tuning
Камуфляж:
textcatvrf matte vinyl, off-road truck with tactical camouflage matte vinyl wrap, realistic texture and seams, adventure style
Negative Prompt:
textglossy paint, shiny reflections, chrome look, plastic wrap, bubbles under film, low quality, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (matte_vinyl_wrap_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Car Tuning, PPF и Ceramic Coating LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который идеально передаёт глянцевый винил (глянцевая плёнка) на автомобилях:

Максимально глубокий, зеркальный глянец
Усиление цвета и «мокрый» эффект
Чёткие отражения окружения, неба, источников света
Разные виды: чистый глянец, металлик, перламутр, хамелеон, candy
Реалистичную текстуру плёнки (ровная поверхность, швы, растяжка на сложных элементах)

LoRA станет прямым дополнением к matte_vinyl_wrap_v1, car_tuning_v1 и ppf_protection_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с глянцем и отражениями)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Glossy_Vinyl_Wrap_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9400 - 14800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf glossy vinyl, glossy car wrap, professional glossy vinyl wrap, deep mirror vinyl finish

Дополнительные триггеры:

high gloss vinyl wrap, candy paint vinyl, metallic glossy wrap, chrome-like vinyl
wet look glossy surface, mirror reflections on car wrap

4. Структура датасета
Рекомендуемый объём: 2600–4300 изображений
Структура папок:
textglossy_vinyl_dataset/
├── 01_deep_black_gloss/
├── 02_color_gloss_wraps/
├── 03_metallic_and_pearl/
├── 04_candy_and_chameleon/
├── 05_full_body_glossy_vinyl/
├── 06_closeup_seams_and_stretch/
├── 07_night_reflections_gloss/
├── 08_with_tuning_kit/
└── captions.txt
Требования к фото:

Профессиональные съёмки автомобилей в глянцевом виниле
Обязательно макро швов и сильные отражения
Разные освещения (солнце, закат, ночь с неоном)

Примеры caption:
textcatvrf glossy vinyl, luxury car with deep mirror black glossy vinyl wrap, insane reflections and wet look shine, professional installation
5. Оптимальные настройки использования
В TextureGenerationService / AutoTuningService:
PHP$loras = [
    'car_tuning_v1'                => 0.80,
    'matte_vinyl_wrap_v1'          => 0.40,
    'glossy_vinyl_wrap_v1'         => 0.96,     // доминирующий
    'reflections_specular_v1'      => 0.94,     // очень высокий вес
    'volumetric_lighting_v1'       => 0.85,
    'color_grading_master_v1'      => 0.82,
];
Примеры промптов:
Чёрный глянец:
textcatvrf glossy vinyl, aggressive widebody car in deep mirror black glossy vinyl wrap, perfect reflections of sky and lights, ultra shiny wet look
Металлик:
textcatvrf glossy vinyl, stunning blue metallic glossy vinyl wrap on sports car, rich color depth and mirror reflections, bright sunlight
Хамелеон:
textcatvrf glossy vinyl, luxury sedan with color-shifting chameleon glossy vinyl wrap, dramatic reflections, premium tuning style
Negative Prompt:
textmatte finish, dull surface, orange peel texture, cheap vinyl look, bubbles, low gloss, flat reflections
6. Что должен вернуть ИИ

Полный конфиг обучения (glossy_vinyl_wrap_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Matte Vinyl, Car Tuning и Reflections LoRA

### 1. Цель
Создать мощный специализированный LoRA, который отлично передаёт профессиональный автозвук и car audio инсталляции:

Мощные сабвуферы, корпуса, усилители, процессоры
Кастомные сабвуферные ящики (stealth, ported, sealed, wall)
Премиум компонентная акустика, коаксиалы, твитеры
LED-подсветка, RGB-эффекты в багажнике и салоне
Визуализация звука (басовые волны, вибрация, «дрожь» воздуха)
Разные уровни: бюджетный, SQ (Sound Quality), SPL (Sound Pressure Level), competition

LoRA идеально дополняет car_tuning_v1, car_detailing_v1 и matte_vinyl_wrap_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с освещением, отражениями и сложными объектами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Car_Audio_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.4e-5
max_train_steps: 9800 - 15500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf car audio, professional car audio system, powerful subwoofer install, custom sound system

Дополнительные триггеры:

SPL competition trunk, SQ premium audio, RGB illuminated sub box, massive amplifier setup

4. Структура датасета
Рекомендуемый объём: 2600–4500 изображений
Структура папок:
textcar_audio_dataset/
├── 01_subwoofer_boxes_and_enclosures/
├── 02_amplifiers_and_processors/
├── 03_full_trunk_installs/
├── 04_SPL_competition_builds/
├── 05_SQ_premium_audio/
├── 06_RGB_lighting_and_effects/
├── 07_interior_speakers_and_tweeters/
├── 08_before_after_audio_install/
├── 09_night_shots_with_lighting/
└── captions.txt
Требования к фото:

Реальные мощные инсталляции автозвука
Много close-up (сабы, усилители, проводка, LED)
Динамичные кадры с вибрацией и освещением

Примеры caption:
textcatvrf car audio, massive custom subwoofer box with 4x 15" woofers, RGB lighting, professional SPL build, deep trunk install
5. Оптимальные настройки использования
В TextureGenerationService / AutoAudioService:
PHP$loras = [
    'car_tuning_v1'                => 0.78,
    'matte_vinyl_wrap_v1'          => 0.60,
    'car_audio_v1'                 => 0.96,     // доминирующий
    'reflections_specular_v1'      => 0.82,
    'volumetric_lighting_v1'       => 0.88,     // важно для RGB
];
Примеры промптов:
SPL-монстр:
textcatvrf car audio, extreme SPL competition trunk with four 18" subwoofers, massive amplifiers, aggressive RGB lighting, shaking bass waves
Премиум SQ:
textcatvrf car audio, luxury SQ car audio system with high-end component speakers, hidden amplifiers, clean and elegant install, soft ambient lighting
Багажник с подсветкой:
textcatvrf car audio, custom illuminated subwoofer enclosure with RGB effects in matte black trunk, deep powerful bass, modern tuning style
Negative Prompt:
textstock audio, cheap speakers, deformed boxes, bad wiring, cartoonish, low quality, plastic look
6. Что должен вернуть ИИ

Полный конфиг обучения (car_audio_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Car Tuning, Matte Vinyl и Lighting LoRA

### 1. Цель
Создать высокотехнологичный LoRA, который идеально передаёт аудиоосвещение — синхронизированную RGB-подсветку с автозвуком:

RGB-подсветка багажника, сабвуферных ящиков, дверей, ног, потолка
Реактивное освещение (реагирует на бас, музыку, частоты)
Эффекты: стробоскоп, пульсация, цветомузыка, wave, breathing, spectrum analyzer
Премиум-инсталляции: адресная лента, матрицы, неоновые трубки, световые панели
Визуализация звука: «волны» света, дрожь воздуха, вспышки в такт ударам

LoRA идеально дополняет car_audio_v1, car_tuning_v1 и matte_vinyl_wrap_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с динамическим освещением и цветными бликами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Audio_Lighting_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9600 - 15200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf audio lighting, car audio rgb lighting, reactive sound lighting, music synced car lights

Дополнительные триггеры:

bass reactive RGB, spectrum analyzer lighting, illuminated subwoofer box, neon audio install
pulsing lights with beat, color music car interior

4. Структура датасета
Рекомендуемый объём: 2400–4100 изображений
Структура папок:
textaudio_lighting_dataset/
├── 01_trunk_rgb_installs/
├── 02_reactive_bass_lighting/
├── 03_spectrum_analyzer_effects/
├── 04_door_and_footwell_lighting/
├── 05_full_interior_audio_lighting/
├── 06_subwoofer_box_lighting/
├── 07_night_shots_with_music_sync/
├── 08_premium_addressable_rgb/
└── captions.txt
Требования к фото:

Реальные инсталляции с работающим RGB-подсветкой
Ночные и тёмные съёмки (где свет особенно красив)
Кадры с видимой синхронизацией (размытие движения, вспышки)

Примеры caption:
textcatvrf audio lighting, massive subwoofer box with reactive RGB lighting pulsing to heavy bass, deep purple and blue colors, dark trunk, dramatic night shot
5. Оптимальные настройки использования
В TextureGenerationService / CarAudioService:
PHP$loras = [
    'car_audio_v1'                 => 0.88,
    'audio_lighting_v1'            => 0.96,     // доминирующий
    'car_tuning_v1'                => 0.70,
    'matte_vinyl_wrap_v1'          => 0.55,
    'volumetric_lighting_v1'       => 0.90,     // критично для атмосферы
    'reflections_specular_v1'      => 0.78,
];
Примеры промптов:
Багажник:
textcatvrf audio lighting, insane car audio trunk with multiple subwoofers and aggressive reactive RGB lighting pulsing in time with bass, purple and cyan colors, dark dramatic atmosphere
Салон:
textcatvrf audio lighting, luxury car interior with full addressable RGB lighting synced to music, ambient footwell and door panels glowing, modern premium build
Ночной эффект:
textcatvrf audio lighting, widebody car at night with powerful sound system and bright reactive underglow + interior RGB, visual bass waves, cyberpunk vibe
Negative Prompt:
textstatic lighting, flat colors, bad sync, cheap leds, overexposed, cartoonish, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (audio_lighting_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Car Audio, Car Tuning и Volumetric Lighting LoRA

### 1. Цель
Создать мощный и атмосферный LoRA, который идеально передаёт неоновую подсветку автомобилей:

Классический неон (трубки) и современный LED-неон
Underglow (подсветка днища), боковая подсветка, колесные арки
Интерьерный неон, багажник, двигательный отсек
Эффекты: стробоскоп, breathing, rainbow, single color, police style, running lights
Реалистичное свечение, отражения на асфальте, объёмный свет, bloom-эффект

LoRA отлично дополняет car_audio_v1, audio_lighting_v1, car_tuning_v1 и matte_vinyl_wrap_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех справляется с неоновым свечением и bloom)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Neon_Underglow_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 93
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.6e-5
max_train_steps: 9200 - 14800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 750
Trigger words (основные):

catvrf neon underglow, realistic car neon lighting, aggressive neon glow, cyberpunk car lights

Дополнительные триггеры:

pink neon underglow, blue neon sides, RGB neon interior, old school neon tubes
neon reflection on ground, vibrant neon night shot

4. Структура датасета
Рекомендуемый объём: 2300–4000 изображений
Структура папок:
textneon_underglow_dataset/
├── 01_classic_neon_tubes/
├── 02_modern_led_neon_underglow/
├── 03_rgb_multicolor_neon/
├── 04_interior_neon_lighting/
├── 05_wheel_arch_and_side_glow/
├── 06_trunk_and_engine_bay_neon/
├── 07_night_street_shots/
├── 08_cyberpunk_and_aggressive_style/
└── captions.txt
Требования к фото:

Ночные и вечерние съёмки (где неон выглядит максимально эффектно)
Низкие углы съёмки (low angle)
Видимые отражения неона на мокром асфальте

Примеры caption:
textcatvrf neon underglow, aggressive widebody car with bright pink neon underglow and side lighting, strong reflections on wet asphalt, cyberpunk night atmosphere
5. Оптимальные настройки использования
В TextureGenerationService / AutoLightingService:
PHP$loras = [
    'car_tuning_v1'                => 0.75,
    'audio_lighting_v1'            => 0.82,
    'neon_underglow_v1'            => 0.96,     // доминирующий
    'volumetric_lighting_v1'       => 0.90,     // критично для bloom
    'reflections_specular_v1'      => 0.88,
    'matte_vinyl_wrap_v1'          => 0.60,
];
Примеры промптов:
Классический underglow:
textcatvrf neon underglow, stunning low sports car with vibrant blue neon underglow, strong reflections on wet road, night city background, cyberpunk vibe
Многоцветный:
textcatvrf neon underglow, aggressive stance car with full RGB neon lighting changing colors, pink to cyan gradient, dramatic night shot
Интерьер + багажник:
textcatvrf neon underglow, luxury trunk with powerful audio system and bright purple neon lighting, open trunk view, deep rich colors
Negative Prompt:
textdaytime neon, weak glow, flat lighting, bad reflections, cartoonish, low quality, burned out lights
6. Что должен вернуть ИИ

Полный конфиг обучения (neon_underglow_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Car Audio, Audio Lighting и Car Tuning LoRA

### 1. Цель
Создать высококачественный специализированный LoRA, который идеально передаёт ксеноновые фары (HID/Xenon) на автомобилях:

Характерный холодный белый свет с голубым оттенком (4300K–6000K)
Чёткую светотеневую границу (cutoff line)
Реалистичные линзы проекторов, отражения внутри фары, блики на стекле
Эффекты: световые столбы в тумане/дожде, отражения на мокром асфальте, lens flare
Разные поколения: классический ксенон, bi-xenon, с ангельскими глазками (angel eyes)

LoRA отлично дополняет car_tuning_v1, neon_underglow_v1, car_detailing_v1 и ppf_protection_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с источниками света и lens effects)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Xenon_Headlights_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 92
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9200 - 14600
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf xenon headlights, realistic xenon lamps, bi-xenon projectors, cold white xenon light

Дополнительные триггеры:

sharp cutoff line, blue tinted xenon, angel eyes with xenon, powerful HID beams

4. Структура датасета
Рекомендуемый объём: 2200–3800 изображений
Структура папок:
textxenon_headlights_dataset/
├── 01_classic_xenon_reflectors/
├── 02_bi_xenon_projectors/
├── 03_with_angel_eyes/
├── 04_night_beam_shots/
├── 05_wet_road_reflections/
├── 06_fog_and_rain_effects/
├── 07_closeup_lens_and_details/
├── 08_tuning_cars_with_xenon/
└── captions.txt
Требования к фото:

Ночные и вечерние съёмки (где ксенон раскрывается полностью)
Low-angle shots + frontal views
С мокрой дорогой и туманом для красивых лучей

Примеры caption:
textcatvrf xenon headlights, sharp bi-xenon projectors with crisp cutoff line, cold white-blue light, powerful beams on wet asphalt at night
5. Оптимальные настройки использования
В TextureGenerationService / AutoLightingService:
PHP$loras = [
    'car_tuning_v1'                => 0.78,
    'neon_underglow_v1'            => 0.65,
    'xenon_headlights_v1'          => 0.95,     // доминирующий
    'volumetric_lighting_v1'       => 0.92,     // критично для световых столбов
    'reflections_specular_v1'      => 0.88,
];
Примеры промптов:
Ночной выезд:
textcatvrf xenon headlights, aggressive tuned car with bright bi-xenon headlights, sharp cutoff line and strong blue-white beams cutting through night fog, wet road reflections
С ангельскими глазками:
textcatvrf xenon headlights, BMW with classic angel eyes and xenon projectors, iconic blue glow, night city street, cinematic lighting
Тюнингованный вариант:
textcatvrf xenon headlights, widebody stance car with upgraded xenon headlights and LED angel eyes, dramatic night shot with powerful light throw
Negative Prompt:
textyellow halogen light, LED daytime look, blurry beams, flat lighting, cartoonish, low quality, wrong color temperature
6. Что должен вернуть ИИ

Полный конфиг обучения (xenon_headlights_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Neon Underglow, Car Tuning и Volumetric Lighting LoRA

### 1. Цель
Создать высокотехнологичный специализированный LoRA, который идеально передаёт современные LED-фары:

Чёткий, яркий белый свет (5500K–6500K) с характерным «ледяным» оттенком
Matrix LED, Pixel LED, адаптивный дальний свет, signature daytime running lights (DRL)
Чёткую светотеневую границу, красивые световые рисунки и «зрачки»
Реалистичные отражения в линзах, блики, lens flare, объёмный световой пучок
Эффекты в дождь, туман, ночь: красивые лучи, отражения на мокром асфальте

LoRA отлично дополняет xenon_headlights_v1, neon_underglow_v1 и car_tuning_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с современными источниками света и сложными оптическими эффектами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_LED_Headlights_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 93
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9400 - 14900
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf led headlights, modern led headlamps, matrix led projectors, sharp led daytime running lights

Дополнительные триггеры:

pixel led adaptive headlights, signature led drl, ice blue led glow, powerful led high beam

4. Структура датасета
Рекомендуемый объём: 2500–4200 изображений
Структура папок:
textled_headlights_dataset/
├── 01_matrix_pixel_led/
├── 02_signature_drl_and_angel_eyes/
├── 03_full_beam_night_shots/
├── 04_wet_road_and_rain_effects/
├── 05_modern_luxury_cars_led/
├── 06_tuning_cars_with_led_upgrade/
├── 07_closeup_lens_and_optics/
├── 08_adaptive_high_beam/
└── captions.txt
Требования к фото:

Современные автомобили (2020–2026) с заводским и тюнингованным LED-светом
Обязательно ночные и вечерние съёмки
Макро-детали линз и DRL

Примеры caption:
textcatvrf led headlights, sharp matrix LED headlights with distinctive signature DRL, bright cold white light, powerful high beam cutting through night, wet asphalt reflections
5. Оптимальные настройки использования
В TextureGenerationService / AutoLightingService:
PHP$loras = [
    'xenon_headlights_v1'          => 0.45,
    'neon_underglow_v1'            => 0.65,
    'led_headlights_v1'            => 0.96,     // доминирующий
    'volumetric_lighting_v1'       => 0.93,     // критично для световых пучков
    'reflections_specular_v1'      => 0.89,
    'car_tuning_v1'                => 0.75,
];
Примеры промптов:
Современный автомобиль:
textcatvrf led headlights, luxury Mercedes with advanced matrix LED headlights and illuminated signature DRL, sharp cold white light, night highway scene, cinematic reflections
Тюнингованный вариант:
textcatvrf led headlights, aggressive widebody car with upgraded full LED projectors and angel eyes, bright blue-white beams, dramatic night lighting
Дождь:
textcatvrf led headlights, sports car with powerful LED high beam cutting through heavy rain, beautiful light rays and wet road reflections, atmospheric night shot
Negative Prompt:
textyellow halogen light, old xenon look, blurry beams, flat lighting, wrong color temperature, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (led_headlights_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Neon Underglow, Car Tuning и Xenon Headlights LoRA

### 1. Цель
Создать высокотехнологичный специализированный LoRA, который идеально передаёт современные LED-фары:

Чёткий, яркий белый свет (5500K–6500K) с характерным «ледяным» оттенком
Matrix LED, Pixel LED, адаптивный дальний свет, signature daytime running lights (DRL)
Чёткую светотеневую границу, красивые световые рисунки и «зрачки»
Реалистичные отражения в линзах, блики, lens flare, объёмный световой пучок
Эффекты в дождь, туман, ночь: красивые лучи, отражения на мокром асфальте

LoRA отлично дополняет xenon_headlights_v1, neon_underglow_v1 и car_tuning_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с современными источниками света и сложными оптическими эффектами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_LED_Headlights_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 93
alpha: 46
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.5e-5
max_train_steps: 9400 - 14900
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf led headlights, modern led headlamps, matrix led projectors, sharp led daytime running lights

Дополнительные триггеры:

pixel led adaptive headlights, signature led drl, ice blue led glow, powerful led high beam

4. Структура датасета
Рекомендуемый объём: 2500–4200 изображений
Структура папок:
textled_headlights_dataset/
├── 01_matrix_pixel_led/
├── 02_signature_drl_and_angel_eyes/
├── 03_full_beam_night_shots/
├── 04_wet_road_and_rain_effects/
├── 05_modern_luxury_cars_led/
├── 06_tuning_cars_with_led_upgrade/
├── 07_closeup_lens_and_optics/
├── 08_adaptive_high_beam/
└── captions.txt
Требования к фото:

Современные автомобили (2020–2026) с заводским и тюнингованным LED-светом
Обязательно ночные и вечерние съёмки
Макро-детали линз и DRL

Примеры caption:
textcatvrf led headlights, sharp matrix LED headlights with distinctive signature DRL, bright cold white light, powerful high beam cutting through night, wet asphalt reflections
5. Оптимальные настройки использования
В TextureGenerationService / AutoLightingService:
PHP$loras = [
    'xenon_headlights_v1'          => 0.45,
    'neon_underglow_v1'            => 0.65,
    'led_headlights_v1'            => 0.96,     // доминирующий
    'volumetric_lighting_v1'       => 0.93,     // критично для световых пучков
    'reflections_specular_v1'      => 0.89,
    'car_tuning_v1'                => 0.75,
];
Примеры промптов:
Современный автомобиль:
textcatvrf led headlights, luxury Mercedes with advanced matrix LED headlights and illuminated signature DRL, sharp cold white light, night highway scene, cinematic reflections
Тюнингованный вариант:
textcatvrf led headlights, aggressive widebody car with upgraded full LED projectors and angel eyes, bright blue-white beams, dramatic night lighting
Дождь:
textcatvrf led headlights, sports car with powerful LED high beam cutting through heavy rain, beautiful light rays and wet road reflections, atmospheric night shot
Negative Prompt:
textyellow halogen light, old xenon look, blurry beams, flat lighting, wrong color temperature, cartoonish
6. Что должен вернуть ИИ

Полный конфиг обучения (led_headlights_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Neon Underglow, Car Tuning и Xenon Headlights LoRA

### 1. Цель
Создать высокотехнологичный специализированный LoRA, который идеально передаёт адаптивные матричные фары (Matrix LED / Pixel LED / Adaptive Driving Beam):

Интеллектуальное управление отдельными сегментами LED
Автоматическое затемнение определённых зон (встречный транспорт, пешеходы)
Адаптация под повороты, город, трассу, дождь, туман
Характерный «живой» световой рисунок, динамические переходы
Реалистичные эффекты: красивые световые пучки, мягкие переходы, lens flare, отражения на мокром асфальте

LoRA отлично дополняет led_headlights_v1, xenon_headlights_v1 и neon_underglow_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с динамическим освещением и сложными оптическими эффектами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Adaptive_Matrix_Headlights_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.4e-5
max_train_steps: 9800 - 15500
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf adaptive headlights, matrix led adaptive beam, intelligent matrix headlights, dynamic adaptive lighting

Дополнительные триггеры:

adaptive high beam, matrix led with selective dimming, cornering adaptive lights, pixel led technology

4. Структура датасета
Рекомендуемый объём: 2600–4300 изображений
Структура папок:
textadaptive_headlights_dataset/
├── 01_matrix_led_adaptive_high_beam/
├── 02_selective_dimming_oncoming_traffic/
├── 03_cornering_and_curve_lights/
├── 04_city_highway_rain_modes/
├── 05_night_driving_scenes/
├── 06_luxury_premium_cars_matrix/
├── 07_closeup_pixel_segments/
├── 08_dynamic_light_patterns/
└── captions.txt
Требования к фото:

Реальные съёмки современных автомобилей (Mercedes Multibeam, BMW Laser, Audi Matrix, Porsche HD Matrix и т.д.)
Обязательно ночные съёмки с включённым адаптивным светом
Ситуации: встречные машины, повороты, дождь, туман

Примеры caption:
textcatvrf adaptive headlights, advanced matrix LED headlights with selective dimming for oncoming traffic, sharp adaptive high beam, dynamic light pattern on wet night road
5. Оптимальные настройки использования
В TextureGenerationService / AutoLightingService:
PHP$loras = [
    'led_headlights_v1'            => 0.78,
    'adaptive_headlights_v1'       => 0.96,     // доминирующий
    'volumetric_lighting_v1'       => 0.94,     // критично для пучков света
    'reflections_specular_v1'      => 0.89,
    'neon_underglow_v1'            => 0.60,
    'car_tuning_v1'                => 0.70,
];
Примеры промптов:
Адаптивный дальний:
textcatvrf adaptive headlights, luxury car with intelligent matrix LED adaptive headlights, selective dimming for oncoming traffic, powerful yet safe high beam, night highway
Поворот:
textcatvrf adaptive headlights, modern sports car with dynamic cornering adaptive lights, matrix LED illuminating the curve, dramatic night driving scene
Дождь:
textcatvrf adaptive headlights, premium SUV with matrix LED in rain mode, clear visibility with adaptive beams cutting through rain, beautiful light reflections on wet road
Negative Prompt:
textstatic headlights, uniform beam, old halogen/yellow light, wrong cutoff line, blurry light, cartoonish, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (adaptive_headlights_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с LED Headlights, Neon Underglow и Car Tuning LoRA

### 1. Цель
Создать топовый технологичный LoRA, который идеально передаёт лазерные фары (Laser High Beam / Digital Light):

Характерный сверхъяркий, чистый белый свет с лёгким голубым оттенком
Огромную дальность и концентрированный пучок (до 600–700 метров)
Матричную технологию + лазерные модули
Красивые световые «лезвия», чёткую границу, минимальное рассеивание
Эффекты: мощные лучи в тумане/дожде, яркие отражения на дороге, lens flare, "лазерный" характер свечения

LoRA станет флагманским для премиум- и гиперкаров в вертикали автотюнинг и автосвет.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех справляется с интенсивными источниками света и оптическими эффектами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Laser_Headlights_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 95
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.3e-5
max_train_steps: 10200 - 16200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 850
Trigger words (основные):

catvrf laser headlights, bmw laserlight, matrix laser beam, digital light laser

Дополнительные триггеры:

intense laser high beam, blue-white laser light, long range laser projectors, adaptive laser matrix

4. Структура датасета
Рекомендуемый объём: 2300–4000 изображений
Структура папок:
textlaser_headlights_dataset/
├── 01_bmw_laserlight/
├── 02_audi_matrix_laser/
├── 03_mercedes_digital_light/
├── 04_porsche_hd_matrix_laser/
├── 05_night_long_range_beam/
├── 06_fog_and_rain_laser/
├── 07_closeup_laser_modules/
├── 08_adaptive_laser_scenes/
└── captions.txt
Требования к фото:

Только реальные автомобили с заводскими лазерными фарами (BMW i8, X5 Laser, Audi R8, Mercedes S-Class Digital Light и т.д.)
Обязательно ночные съёмки с включённым дальним светом
С мокрой дорогой и туманом для демонстрации дальности

Примеры caption:
textcatvrf laser headlights, bmw laserlight with intense blue-white laser high beam, extremely long and sharp light throw, night highway scene
5. Оптимальные настройки использования
В TextureGenerationService / AutoLightingService:
PHP$loras = [
    'led_headlights_v1'            => 0.65,
    'adaptive_headlights_v1'       => 0.75,
    'laser_headlights_v1'          => 0.97,     // максимальный вес
    'volumetric_lighting_v1'       => 0.94,
    'reflections_specular_v1'      => 0.90,
    'neon_underglow_v1'            => 0.55,
];
Примеры промптов:
Классический BMW Laser:
textcatvrf laser headlights, bmw with iconic laserlight headlights, extremely bright and long blue-white beam cutting through darkness, sharp cutoff, night road
Премиум Digital Light:
textcatvrf laser headlights, mercedes s-class with digital light laser matrix, adaptive laser beams intelligently dimming for oncoming cars, cinematic night shot
Спортивный вариант:
textcatvrf laser headlights, porsche 911 with hd matrix laser headlights, powerful focused beam in rain, dramatic reflections on wet asphalt
Negative Prompt:
textyellow halogen, weak led, blurry beam, wrong color temperature, flat lighting, cartoonish, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (laser_headlights_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Adaptive, LED Headlights и Neon Underglow LoRA

### 1. Цель
Создать футуристический высокотехнологичный LoRA, который идеально передаёт голографические фары — самую продвинутую технологию автомобильного освещения:

3D-голографические проекции, плавающие световые элементы и цифровые световые рисунки
Голографические матрицы, объёмные световые «лезвия» и адаптивные голограммы
Характерный «цифровой» свет с лёгким голубым/фиолетовым оттенком и объёмным glow
Эффекты: floating holographic icons, dynamic light patterns, laser-holographic high beam
Реалистичные оптические эффекты: lens flare, volumetric light rays, holographic interference

LoRA станет топовым для гиперкаров, концепт-каров и премиум-тюнинга 2026+ годов.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучше всех работает с объёмным светом и цифровыми эффектами)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Holographic_Headlights_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 96
alpha: 48
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.3e-5
max_train_steps: 10800 - 16800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 850
Trigger words (основные):

catvrf holographic headlights, digital holographic beam, 3D holographic light, futuristic laser holographic

Дополнительные триггеры:

floating holographic matrix, volumetric holographic projection, cyberpunk holographic headlights

4. Структура датасета
Рекомендуемый объём: 2400–4100 изображений
Структура папок:
textholographic_headlights_dataset/
├── 01_futuristic_concept_cars/
├── 02_matrix_holographic_projection/
├── 03_3d_floating_light_patterns/
├── 04_night_holographic_high_beam/
├── 05_holographic_drl_and_signature/
├── 06_rain_fog_holographic_effects/
├── 07_closeup_holographic_optics/
├── 08_cyberpunk_and_hypercar/
└── captions.txt
Требования к фото:

Концепт-кары и прототипы с голографическим/проекционным светом
Ночные съёмки с объёмным светом
Макро-детали оптических элементов

Примеры caption:
textcatvrf holographic headlights, futuristic hypercar with 3D holographic matrix headlights, floating digital light patterns, intense blue-white volumetric beam, cyberpunk night
5. Оптимальные настройки использования
В TextureGenerationService / AutoLightingService:
PHP$loras = [
    'laser_headlights_v1'          => 0.65,
    'adaptive_headlights_v1'       => 0.70,
    'holographic_headlights_v1'    => 0.97,     // максимальный вес
    'volumetric_lighting_v1'       => 0.95,
    'reflections_specular_v1'      => 0.90,
    'neon_underglow_v1'            => 0.68,
];
Примеры промптов:
Футуристический гиперкар:
textcatvrf holographic headlights, stunning hypercar with advanced 3D holographic matrix headlights, floating digital light icons and powerful volumetric beam, cyberpunk night city
Адаптивная голограмма:
textcatvrf holographic headlights, luxury concept car with intelligent holographic adaptive beam, selective 3D light projection, dramatic night highway
Дождь + голограмма:
textcatvrf holographic headlights, aggressive electric hypercar with holographic projectors cutting through rain, beautiful volumetric light rays and floating patterns
Negative Prompt:
textstatic led/xenon, flat beam, wrong color temperature, blurry holography, cartoonish, low quality
6. Что должен вернуть ИИ

Полный конфиг обучения (holographic_headlights_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Laser, Adaptive и Neon Underglow LoRA

### 1. Цель
Создать универсальный и высокодетализированный LoRA, который превосходно передаёт салоны современных автомобилей:

Премиум-материалы: натуральная кожа, алькантара, карбон, алькантара, дерево, металл, мягкий пластик
Разные стили: luxury (Mercedes, Rolls-Royce), sport (BMW M, Porsche), futuristic (Tesla, hypercars), minimal, racing
Элементы: сиденья, торпедо, руль, центральная консоль, дверные карты, потолок, ambient lighting
Детали: строчка, перфорация, карбоновые вставки, RGB-подсветка, цифровая панель приборов

LoRA идеально дополняет car_tuning_v1, car_audio_v1, car_detailing_v1 и audio_lighting_v1.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая детализация материалов и интерьерного освещения)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Car_Interior_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.4e-5
max_train_steps: 9800 - 15800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf car interior, luxury car cabin, premium automotive interior, detailed vehicle interior

Дополнительные триггеры:

alcantara and leather interior, carbon fiber cockpit, ambient lighting cabin, sporty racing interior

4. Структура датасета
Рекомендуемый объём: 2800–4800 изображений
Структура папок:
textcar_interior_dataset/
├── 01_luxury_premium_cabin/
├── 02_sport_racing_interior/
├── 03_minimal_futuristic/
├── 04_carbon_and_alcantara/
├── 05_ambient_rgb_lighting/
├── 06_driver_pov_and_full_cabin/
├── 07_night_interior_shots/
├── 08_detailing_and_stitching/
└── captions.txt
Требования к фото:

Профессиональные заводские и тюнинговые съёмки салонов
Много close-up текстур материалов
Разные углы (driver POV, passenger, full interior)

Примеры caption:
textcatvrf car interior, luxury black and red leather interior with alcantara ceiling and carbon fiber trim, soft ambient lighting, premium automotive detailing
5. Оптимальные настройки использования
В TextureGenerationService / AutoInteriorService:
PHP$loras = [
    'car_tuning_v1'                => 0.65,
    'car_audio_v1'                 => 0.70,
    'car_interior_v1'              => 0.96,     // доминирующий
    'audio_lighting_v1'            => 0.82,
    'material_texture_master_v1'   => 0.85,
    'volumetric_lighting_v1'       => 0.80,
];
Примеры промптов:
Люкс-салон:
textcatvrf car interior, opulent Rolls-Royce style cabin with finest leather, wood trim and starlight headliner, soft warm ambient lighting, ultra detailed
Спорт:
textcatvrf car interior, aggressive BMW M interior with carbon fiber, alcantara and red stitching, sporty bucket seats, dynamic lighting
Футуристический:
textcatvrf car interior, Tesla-style minimalist futuristic cabin with huge digital dashboard and ambient RGB lighting, clean modern design
Negative Prompt:
textdeformed seats, bad textures, plastic look, blurry details, cartoonish, low quality, empty car
6. Что должен вернуть ИИ

Полный конфиг обучения (car_interior_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Car Audio, Neon/LED Lighting и Car Tuning LoRA

### 1. Цель
Создать универсальный, высокодетализированный LoRA, который отлично передаёт внешний вид автомобиля во всём его многообразии:

Кузов, пропорции, силуэт, аэродинамика
Разные типы автомобилей: седан, купе, SUV, hypercar, electric, off-road, drift
Материалы и покрытия: глянец, матовый винил, керамика, PPF, карбон, хром
Колёса, диски, тормозные суппорты, выхлопные системы
Оптика (фары, фонари), зеркала, спойлеры, обвесы
Реалистичные отражения, блики, взаимодействие с окружающей средой

Этот LoRA станет базовым для всей автомобильной вертикали проекта.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (лучшая геометрия, материалы и освещение)
Альтернатива: SDXL + Realistic Vision / Juggernaut XL
Название LoRA: CatVRF_Car_Exterior_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 96
alpha: 48
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.3e-5
max_train_steps: 11200 - 17200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 850
Trigger words (основные):

catvrf car exterior, realistic vehicle body, detailed automotive exterior, professional car photography

Дополнительные триггеры:

sleek modern sedan, aggressive widebody coupe, luxury SUV, hypercar stance

4. Структура датасета
Рекомендуемый объём: 3200–5500 изображений (самый большой датасет в линейке)
Структура папок:
textcar_exterior_dataset/
├── 01_sedans_and_coupe/
├── 02_suv_and_crossovers/
├── 03_hypercars_and_supercars/
├── 04_offroad_and_trucks/
├── 05_drift_and_tuning_cars/
├── 06_electric_and_futuristic/
├── 07_different_angles_full_body/
├── 08_closeup_details_wheels_optics/
├── 09_night_and_dramatic_lighting/
├── 10_matte_gloss_ceramic_ppf/
└── captions.txt
Требования к фото:

Профессиональные автомобильные съёмки (студия + outdoors)
Все ракурсы: 3/4, front, rear, side, low angle, high angle
Разные погодные условия и время суток

Примеры caption:
textcatvrf car exterior, sleek black luxury sedan with glossy paint and elegant proportions, dramatic low angle shot, professional automotive photography
5. Оптимальные настройки использования
В TextureGenerationService / CarExteriorService:
PHP$loras = [
    'car_exterior_v1'              => 0.97,     // основной
    'car_tuning_v1'                => 0.75,
    'matte_vinyl_wrap_v1'          => 0.65,
    'glossy_vinyl_wrap_v1'         => 0.65,
    'ceramic_coating_v1'           => 0.70,
    'led_headlights_v1'            => 0.72,
    'volumetric_lighting_v1'       => 0.85,
];
Примеры промптов:
Люксовый седан:
textcatvrf car exterior, elegant black Mercedes S-Class with deep glossy paint and chrome details, cinematic low angle, golden hour lighting
Тюнингованный:
textcatvrf car exterior, aggressive widebody BMW M3 in matte black vinyl, massive forged wheels, lowered stance, dramatic sunset
Гиперкар:
textcatvrf car exterior, futuristic hypercar with carbon fiber body and active aero, standing on wet asphalt at night with neon reflections
Negative Prompt:
textdeformed body, bad proportions, blurry wheels, cartoonish, low quality, plastic look
6. Что должен вернуть ИИ

Полный конфиг обучения (car_exterior_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Tuning, Matte/Glossy Vinyl, Ceramic и Lighting LoRA

### 1. Цель
Создать высокодетализированный LoRA, который идеально передаёт колёса и их элементы:

Диски (кованые, литые, двухцветные, brushed, polished, matte)
Тормозные суппорты (цветные, с логотипами)
Шины (протектор, боковина, low-profile, slick, semi-slick)
Болты, гайки, колпачки, центральные колпаки
Блики, отражения, текстуры металла, резины, тормозных дисков
Эффекты: пыль, грязь, следы торможения, дым от дрифта

LoRA станет ключевым для реалистичных визуализаций экстерьера и тюнинга.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (отличная детализация мелких механических элементов)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Car_Wheels_Details_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.4e-5
max_train_steps: 9800 - 15800
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf wheel details, realistic car rims, detailed forged wheels, brake calipers closeup

Дополнительные триггеры:

two tone forged rims, colored brake calipers, low profile tires, polished lip wheels

4. Структура датасета
Рекомендуемый объём: 2600–4500 изображений
Структура папок:
textcar_wheels_details_dataset/
├── 01_forged_and_multi_piece_rims/
├── 02_cast_and_flow_form_wheels/
├── 03_brake_calipers_and_discs/
├── 04_tire_tread_and_sidewall/
├── 05_two_tone_and_polished_lip/
├── 06_colored_calipers_and_bolts/
├── 07_drift_and_track_wheels/
├── 08_closeup_macro_details/
├── 09_wet_and_dry_conditions/
└── captions.txt
Требования к фото:

Профессиональные автомобильные съёмки колёс
Много макро (болты, гравировка, текстура резины, тормозные диски)
Разные углы и освещение

Примеры caption:
textcatvrf wheel details, stunning two-tone forged wheels with polished lip and red brake calipers, deep detailed texture, realistic automotive photography
5. Оптимальные настройки использования
В TextureGenerationService / CarExteriorService:
PHP$loras = [
    'car_exterior_v1'              => 0.82,
    'car_tuning_v1'                => 0.78,
    'car_wheels_details_v1'        => 0.96,     // доминирующий
    'reflections_specular_v1'      => 0.88,
    'material_texture_master_v1'   => 0.85,
];
Примеры промптов:
Премиум-диски:
textcatvrf wheel details, luxury black and silver forged multi-piece wheels with red brake calipers, perfect reflections and detailed bolts, low angle shot
Дрифт-вариант:
textcatvrf wheel details, aggressive drift wheels with slick tires and bright yellow calipers, visible brake disc and smoke effect, dynamic angle
Макро:
textcatvrf wheel details, extreme close-up of polished lip forged rim, visible tire lettering and center cap, studio lighting, ultra detailed
Negative Prompt:
textdeformed rims, bad tire texture, blurry details, cartoonish, plastic wheels, wrong proportions
6. Что должен вернуть ИИ

Полный конфиг обучения (car_wheels_details_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Car Exterior, Car Tuning и Reflections LoRA

### 1. Цель
Создать высокодетализированный LoRA, который идеально передаёт тормозные системы автомобилей:

Тормозные суппорты (моноблочные, многопоршневые, окрашенные)
Тормозные диски (вентилируемые, перфорированные, с насечками, карбон-керамика)
Колодки, шланги, магистрали, суппортные скобы
Детали: логотипы (Brembo, AP Racing, Wilwood, StopTech), пыльники, болты, охлаждение
Эффекты: следы торможения, нагрев дисков (красный/оранжевый), пыль от колодок, дым

LoRA критически важен для реалистичного тюнинга, трековых и премиум-автомобилей.
2. Рекомендуемый базовый модель
Лучший выбор: Flux.1-dev (отличная детализация металла, текстур и мелких механических элементов)
Альтернатива: SDXL + Realistic Vision
Название LoRA: CatVRF_Brake_Systems_V1
3. Параметры обучения (оптимальные)
Для Flux.1-dev:
YAMLrank: 94
alpha: 47
batch_size: 2
gradient_accumulation_steps: 4
learning_rate: 4.4e-5
max_train_steps: 9600 - 15200
resolution: 1024x1024
mixed_precision: bf16
save_every_n_steps: 800
Trigger words (основные):

catvrf brake system, detailed brake calipers, performance brake kit, Brembo brakes

Дополнительные триггеры:

red Brembo calipers, carbon ceramic discs, big brake kit, floating rotors

4. Структура датасета
Рекомендуемый объём: 2400–4200 изображений
Структура папок:
textbrake_systems_dataset/
├── 01_brembo_and_ap_racing/
├── 02_carbon_ceramic_discs/
├── 03_big_brake_kits/
├── 04_red_yellow_green_calipers/
├── 05_drilled_and_slotted_discs/
├── 06_closeup_calipers_pads/
├── 07_brakes_with_wheels/
├── 08_heated_glowing_discs/
└── captions.txt
Требования к фото:

Профессиональные съёмки тормозных систем (трековые, тюнинг, заводские)
Обязательно макро и close-up
Кадры с нагретыми дисками и пылью

Примеры caption:
textcatvrf brake system, massive red Brembo 6-piston calipers with floating discs on sports car, detailed logos and bolts, professional automotive photography
5. Оптимальные настройки использования
В TextureGenerationService / CarWheelsService:
PHP$loras = [
    'car_wheels_details_v1'        => 0.85,
    'car_exterior_v1'              => 0.75,
    'brake_systems_v1'             => 0.96,     // доминирующий
    'reflections_specular_v1'      => 0.88,
    'material_texture_master_v1'   => 0.82,
];
Примеры промптов:
Классика Brembo:
textcatvrf brake system, aggressive red Brembo monoblock calipers with large drilled discs, visible logos and stainless steel lines, low angle wheel shot
Карбон-керамика:
textcatvrf brake system, carbon ceramic brake discs with yellow calipers on hypercar, glowing hot rotors, detailed close-up
Трековый вариант:
textcatvrf brake system, big brake kit with 8-piston calipers and slotted rotors, brake dust and heat marks, dynamic track shot
Negative Prompt:
textdeformed calipers, plastic brakes, blurry details, wrong proportions, cartoonish, rusty brakes
6. Что должен вернуть ИИ

Полный конфиг обучения (brake_systems_lora_train.toml)
Структура датасета + лучшие примеры caption
Рекомендованные веса при совместном использовании
Обновлённый TextureGenerationService.php с примерами промптов
Рекомендации по комбинации с Car Wheels Details, Car Exterior и Car Tuning LoRA


### теперь сделай для всех LoRA файлы captions.txt с 500–900 примерами и добавь больше вариантов для каждой из категорий и раздела, файлы писать для кажой категории / типа отдельно

### 