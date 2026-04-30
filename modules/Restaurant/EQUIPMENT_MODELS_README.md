# Поддерживаемые модели оборудования для KDS

## Принтеры

### Star Micronics

| Модель | Команды | Ширина | DPI | Barcode | QR | Рекомендация |
|--------|---------|--------|-----|---------|-----|--------------|
| TSP100III | StarLine | 80mm | 203 | ✅ | ✅ | Standard |
| TSP650II | StarLine | 80mm | 203 | ✅ | ✅ | Standard |
| TSP700II | StarLine | 80mm | 203 | ✅ | ✅ | High Volume |
| SM-S210i | StarLine | 58mm | 203 | ❌ | ❌ | Mobile |
| SM-S220i | StarLine | 80mm | 203 | ❌ | ❌ | Mobile |
| SM-T300i | StarLine | 80mm | 203 | ✅ | ✅ | Label |
| SM-T400i | StarLine | 112mm | 203 | ✅ | ✅ | Label |

### Epson

| Модель | Команды | Ширина | DPI | Barcode | QR | Рекомендация |
|--------|---------|--------|-----|---------|-----|--------------|
| TM-T20II | ESC/POS | 80mm | 203 | ✅ | ✅ | Standard |
| TM-T82II | ESC/POS | 80mm | 203 | ✅ | ✅ | Standard |
| TM-T88V | ESC/POS | 80mm | 203 | ✅ | ✅ | High Volume |
| TM-T90 | ESC/POS | 80mm | 203 | ✅ | ✅ | High Volume |
| TM-M30 | ESC/POS | 80mm | 203 | ✅ | ✅ | Mobile |
| TM-L90 | ESC/POS | 80mm | 203 | ✅ | ✅ | Label |

### Citizen

| Модель | Команды | Ширина | DPI | Barcode | QR | Рекомендация |
|--------|---------|--------|-----|---------|-----|--------------|
| CT-S310II | ESC/POS | 80mm | 203 | ✅ | ✅ | Standard |
| CT-S2000 | ESC/POS | 80mm | 203 | ✅ | ✅ | Standard |
| CT-E310 | ESC/POS | 58mm | 203 | ✅ | ✅ | Label |
| CT-S651II | ESC/POS | 80mm | 203 | ✅ | ✅ | High Volume |

### Bixolon

| Модель | Команды | Ширина | DPI | Barcode | QR | Рекомендация |
|--------|---------|--------|-----|---------|-----|--------------|
| SRP-350III | ESC/POS | 80mm | 203 | ✅ | ✅ | Standard |
| SRP-270 | ESC/POS | 80mm | 203 | ✅ | ✅ | Standard |
| SRP-350PLUS | ESC/POS | 80mm | 203 | ✅ | ✅ | High Volume |

### Zebra

| Модель | Команды | Ширина | DPI | Barcode | QR | Рекомендация |
|--------|---------|--------|-----|---------|-----|--------------|
| GK420T | ZPL | 104mm | 203 | ✅ | ✅ | Label |
| ZD420 | ZPL | 104mm | 300 | ✅ | ✅ | Label |
| ZD620 | ZPL | 104mm | 300 | ✅ | ✅ | Label |

## Дисплеи

### Android Tablets

| Модель | Размер | Разрешение | PWA | Рекомендация |
|--------|--------|------------|-----|--------------|
| Samsung Galaxy Tab A8 | 10.5" | 1920x1200 | ✅ | Standard |
| Samsung Galaxy Tab S6 | 10.4" | 2000x1200 | ✅ | Standard |
| Samsung Galaxy Tab Active3 | 8.0" | 1920x1200 | ✅ | Harsh Environment |
| Lenovo Tab P11 | 11.5" | 2000x1200 | ✅ | Expedition |
| Lenovo Tab M10 | 10.3" | 1920x1200 | ✅ | Standard |
| Huawei MatePad 11 | 11.0" | 2560x1600 | ✅ | Expedition |
| Xiaomi Pad 5 | 11.0" | 2560x1600 | ✅ | Expedition |
| Xiaomi Pad 6 | 11.0" | 2880x1800 | ✅ | Expedition |

### Windows Tablets

| Модель | Размер | Разрешение | PWA | Рекомендация |
|--------|--------|------------|-----|--------------|
| Microsoft Surface Go 3 | 10.5" | 1920x1280 | ✅ | Standard |
| Microsoft Surface Pro 9 | 13.0" | 2880x1920 | ✅ | Expedition |
| HP Elite x2 | 13.5" | 3000x2000 | ✅ | Mobile |
| Dell Latitude 7320 | 13.0" | 1920x1200 | ✅ | Mobile |
| Lenovo ThinkPad X12 | 12.3" | 1920x1280 | ✅ | Mobile |

### Dedicated KDS Displays

| Модель | Размер | Разрешение | PWA | Рекомендация |
|--------|--------|------------|-----|--------------|
| Star KDS Touch | 15.0" | 1920x1080 | ❌ | Harsh Environment |
| Epson KDS Display | 15.0" | 1920x1080 | ❌ | Harsh Environment |
| Loyverse KDS | 15.0" | 1920x1080 | ❌ | Standard |

### Industrial Monitors

| Модель | Размер | Разрешение | PWA | Рекомендация |
|--------|--------|------------|-----|--------------|
| ELO Touch 1515L | 15.0" | 1024x768 | ❌ | Standard |
| ELO Touch 1517L | 15.0" | 1280x1024 | ❌ | Standard |
| ViewSonic TD1655 | 16.0" | 1920x1080 | ❌ | Expedition |
| Iiyama ProLite | 24.0" | 1920x1080 | ❌ | Expedition |

## Конфигурация

### Пример конфигурации принтера

```php
'kitchen_printer' => [
    'enabled' => true,
    'printers' => [
        1 => [
            'name' => 'Hot Kitchen - Epson TM-T88V',
            'type' => 'network',
            'host' => '192.168.1.100',
            'port' => 9100,
            'model' => 'epson_tm_t88v',
        ],
    ],
],
```

### Пример конфигурации дисплея

```php
'kds_display' => [
    'enabled' => true,
    'displays' => [
        1 => [
            'name' => 'Hot Kitchen - Samsung Tab S6',
            'model' => 'samsung_galaxy_tab_s6',
            'orientation' => 'landscape',
        ],
    ],
],
```

## Добавление новой модели

### Принтер

1. Добавьте значение в `PrinterModel` enum
2. Укажите бренд, название, набор команд
3. Укажите характеристики (ширина бумаги, DPI)
4. Укажите поддерживаемые функции (barcode, QR, graphics)
5. Если нужно, добавьте специфичные команды в `PrinterCommandSet`

### Дисплей

1. Добавьте значение в `DisplayModel` enum
2. Укажите платформу, бренд, название
3. Укажите характеристики (размер, разрешение)
4. Укажите поддержку PWA
5. Укажите минимальную версию Android (если применимо)

## Команды принтеров

### ESC/POS (Epson, Citizen, Bixolon)

- Стандарт де-факто для термопринтеров
- Поддерживает: текст, штрих-коды, QR-коды, графику
- Примеры: Epson TM-T88V, Citizen CT-S310II

### StarLine (Star Micronics)

- Проприетарный формат Star Micronics
- Совместим с ESC/POS для базовых функций
- Примеры: Star TSP650II, Star TSP700II

### ZPL (Zebra)

- Язык разметки Zebra для этикеток
- Поддерживает: текст, штрих-коды, QR-коды, графику высокой точности
- Примеры: Zebra GK420T, Zebra ZD420

## Рекомендации по выбору

### Для маленького кафе

- **Принтер:** Epson TM-T20II (доступный, надежный)
- **Дисплей:** Lenovo Tab M10 (доступный Android)

### Для среднего ресторана

- **Принтер:** Epson TM-T88V (высокий объём)
- **Дисплей:** Samsung Galaxy Tab S6 (хороший экран)

### Для сетевого ресторана

- **Принтер:** Star TSP700II (высокий объём)
- **Дисплей:** Microsoft Surface Pro 9 (надёжность)
- **Дополнительно:** Zebra GK420T для этикеток

### Для суровых условий

- **Принтер:** Samsung Galaxy Tab Active3 (rugged)
- **Дисплей:** Star KDS Touch (industrial)

## Тестирование

```bash
# Проверить соединение с принтером
php artisan kds:test-printer --station=1

# Проверить статус всех принтеров
php artisan kds:status-printers

# Напечатать тестовый чек
php artisan kds:print-test --station=1 --model=epson_tm_t88v
```
