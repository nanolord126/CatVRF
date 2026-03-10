# 🚀 РУКОВОДСТВО ПО РАЗВЕРТЫВАНИЮ В PRODUCTION

**Версия**: 1.0
**Дата**: 2024
**Статус**: ✅ PRODUCTION READY

---

## ⚠️ ДО РАЗВЕРТЫВАНИЯ

### Чек-лист перед развертыванием

- [ ] Все файлы скопированы в production
- [ ] Синтаксис проверен (`php -l`)
- [ ] Конфиг `config/fiscal.php` актуален
- [ ] Переменные окружения в Doppler установлены
- [ ] База данных мигрирована (если нужна)
- [ ] Тесты пройдены локально
- [ ] Резервная копия production создана
- [ ] План отката подготовлен

---

## 📋 ШАГИ РАЗВЕРТЫВАНИЯ

### Шаг 1: Подготовка окружения

#### 1.1 Проверить PHP версию
```bash
php -v
# Требуется: PHP 8.0+
```

#### 1.2 Проверить конфигурацию Laravel
```bash
php artisan config:clear
php artisan cache:clear
```

#### 1.3 Убедиться, что все зависимости установлены
```bash
composer install --no-dev
```

### Шаг 2: Обновление файлов

#### 2.1 Копировать основные компоненты
```bash
# Фискальные драйверы
cp CloudKassirFiscalDriver.php app/Domains/Finances/Services/Fiscal/
cp AtolFiscalDriver.php app/Domains/Finances/Services/Fiscal/

# Сервисы
cp FiscalService.php app/Domains/Finances/Services/
cp PaymentService.php app/Domains/Finances/Services/

# Платежные шлюзы
cp TinkoffDriver.php app/Domains/Finances/Services/
cp SberDriver.php app/Domains/Finances/Services/
cp TochkaDriver.php app/Domains/Finances/Services/

# Интерфейсы
cp FiscalServiceInterface.php app/Domains/Finances/Interfaces/
cp FiscalDriverInterface.php app/Domains/Finances/Interfaces/
```

#### 2.2 Проверить синтаксис
```bash
php -l app/Domains/Finances/Services/Fiscal/CloudKassirFiscalDriver.php
php -l app/Domains/Finances/Services/Fiscal/AtolFiscalDriver.php
php -l app/Domains/Finances/Services/TinkoffDriver.php
php -l app/Domains/Finances/Services/SberDriver.php
php -l app/Domains/Finances/Services/TochkaDriver.php
```

**Ожидаемый результат**:
```
No syntax errors detected in CloudKassirFiscalDriver.php
No syntax errors detected in AtolFiscalDriver.php
No syntax errors detected in TinkoffDriver.php
No syntax errors detected in SberDriver.php
No syntax errors detected in TochkaDriver.php
```

### Шаг 3: Конфигурация

#### 3.1 Проверить config/fiscal.php
```php
// Убедитесь, что конфиг выглядит так:
return [
    'default' => env('FISCAL_DRIVER', 'cloudkassir'),  // CloudKassir - основной
    'fallback' => 'atol',                               // Atol - резервный
    'common' => [
        'inn' => env('FISCAL_INN'),
        'taxation_system' => env('FISCAL_TAXATION_SYSTEM', 'usn_income'),
    ],
    'drivers' => [
        'cloudkassir' => [
            'id' => env('CLOUDKASSIR_ID'),
            'key' => env('CLOUDKASSIR_KEY'),
            'endpoint' => 'https://api.cloudpayments.ru/kassa/receipt',
        ],
        'atol' => [
            'login' => env('ATOL_LOGIN'),
            'password' => env('ATOL_PASS'),
            'group_code' => env('ATOL_GROUP_CODE'),
            'endpoint' => 'https://online.atol.ru/possystem/v4/',
        ],
    ],
];
```

#### 3.2 Установить переменные окружения в Doppler
```bash
# Основной провайдер (CloudKassir)
FISCAL_DRIVER=cloudkassir
CLOUDKASSIR_ID=<your_id>
CLOUDKASSIR_KEY=<your_key>

# Резервный провайдер (Atol)
ATOL_LOGIN=<your_login>
ATOL_PASS=<your_password>
ATOL_GROUP_CODE=<your_group_code>

# Общие параметры
FISCAL_INN=<your_inn>
FISCAL_TAXATION_SYSTEM=usn_income  # По умолчанию УСН
```

### Шаг 4: Миграция и инициализация

#### 4.1 Выполнить миграции (если требуются)
```bash
php artisan migrate --force
```

#### 4.2 Очистить кеш
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

#### 4.3 Перезапустить очередь (если используется)
```bash
php artisan queue:restart
```

### Шаг 5: Тестирование

#### 5.1 Проверить здоровье системы
```php
// Локально или в Tinker
$health = app(\App\Domains\Finances\Services\FiscalService::class)->healthCheck();
// Ожидается: 'status' => 'operational'
```

#### 5.2 Отправить тестовый чек
```php
$result = app(\App\Domains\Finances\Services\FiscalService::class)->sendReceipt(
    [
        'id' => 'test-' . uniqid(),
        'payment_id' => 'test-payment-' . uniqid(),
        'user_id' => 1,
        'amount' => 100.00,
        'tax_system' => 'USN_INCOME',
        'metadata' => [
            'email' => 'test@example.com',
            'phone' => '+79999999999',
        ],
    ],
    [
        [
            'name' => 'Test Product',
            'price' => 100.00,
            'qty' => 1,
            'tax' => 'no_vat',
        ],
    ]
);

// Ожидается: 'status' => 'registered' или 'sent'
```

#### 5.3 Проверить логирование
```bash
tail -f storage/logs/laravel-*.log | grep -i fiscal
# Должны быть записи о тестовом чеке
```

### Шаг 6: Мониторинг

#### 6.1 Настроить алерты
```bash
# Мониторить логи на ошибки фискализации
grep -r "CloudKassir.*failed\|Atol.*failed" storage/logs/
```

#### 6.2 Проверить метрики
```bash
# Убедитесь, что:
# - Процент успешных чеков > 95%
# - Время ответа API < 5 сек
# - Нет ошибок синтаксиса в логах
```

---

## 🔄 ОТКАТ ИЗМЕНЕНИЙ (если нужно)

### Если возникли проблемы:

#### 1. Быстрый откат
```bash
# Восстановить из резервной копии
git checkout HEAD -- app/Domains/Finances/

# Или вручную скопировать старые файлы
cp backup/CloudKassirFiscalDriver.php app/Domains/Finances/Services/Fiscal/

# Очистить кеш
php artisan cache:clear
php artisan config:clear
```

#### 2. Откат в Doppler
```bash
# Восстановить старые значения переменных окружения
# в интерфейсе Doppler
```

#### 3. Проверить после отката
```bash
php artisan health:check
php artisan tinker
# Проверить, что старая версия работает
```

---

## 📊 ПРОВЕРОЧНЫЙ ЛИСТ ДЛЯ QA

### Тестирование НДС 20% (ОСН)
- [ ] Отправить чек с `tax_system: OSN` и `tax: vat_20`
- [ ] Проверить, что CloudKassir получил правильный код (`Vat20`)
- [ ] Убедиться, что чек зарегистрирован

### Тестирование БЕЗ НДС (УСН)
- [ ] Отправить чек с `tax_system: USN_INCOME` и `tax: no_vat`
- [ ] Проверить, что CloudKassir получил код `NoVat`
- [ ] Убедиться, что чек зарегистрирован

### Тестирование возвратов
- [ ] Отправить возврат с `tax_system: OSN` и `tax: vat_20`
- [ ] Проверить, что чек возврата создан
- [ ] Убедиться в логе correlation_id

### Тестирование fallback
- [ ] Остановить CloudKassir API (или забрать ключи)
- [ ] Отправить чек - должен перейти на Atol
- [ ] Проверить, что чек зарегистрирован через Atol

### Тестирование Tinkoff
- [ ] Отправить платеж через Tinkoff с `tax_system: OSN`
- [ ] Проверить, что Tinkoff получил правильный налоговый код (`vat20`)
- [ ] Убедиться в логе о успешной отправке

---

## 🚨 TROUBLESHOOTING

### Проблема: "No syntax errors detected" но ошибка в runtime

**Решение**:
```bash
# Проверить, что файлы скопированы полностью
wc -l app/Domains/Finances/Services/Fiscal/CloudKassirFiscalDriver.php
# Должно быть ~350+ строк

# Проверить, что класс объявлен правильно
grep "class CloudKassirFiscalDriver" app/Domains/Finances/Services/Fiscal/CloudKassirFiscalDriver.php
```

### Проблема: "Class not found"

**Решение**:
```bash
# Очистить автозагрузчик Composer
composer dump-autoload -o

# Или
php artisan tinker
# > class_exists('App\Domains\Finances\Services\Fiscal\CloudKassirFiscalDriver')
```

### Проблема: "CloudKassir API error: Invalid credentials"

**Решение**:
```bash
# Проверить переменные окружения
php artisan tinker
> config('fiscal.drivers.cloudkassir')

# Проверить в Doppler, что ключи актуальны
# Перезагрузить конфиг:
php artisan config:clear
```

### Проблема: "Atol API timeout"

**Решение**:
```bash
# Проверить сетевое соединение до Atol
curl -I https://online.atol.ru/possystem/v4/

# Увеличить timeout в драйвере (если нужно)
// В AtolFiscalDriver.php:
->timeout(30)  // вместо 15
```

---

## 📞 ПОДДЕРЖКА

### Контакты для помощи

**CloudKassir Support**:
- Email: support@cloudkassir.ru
- Docs: https://cloudkassir.ru/api

**Atol Support**:
- Email: support@atol.ru
- Docs: https://online.atol.ru/

**Tinkoff Support**:
- Email: merchant@tinkoff.ru
- Docs: https://www.tinkoff.ru/business/acquiring/

---

## ✅ ФИНАЛЬНАЯ ПРОВЕРКА ПЕРЕД ПРОДАКШЕНОМ

```bash
# 1. Проверить синтаксис всех файлов
php -l app/Domains/Finances/Services/Fiscal/CloudKassirFiscalDriver.php
php -l app/Domains/Finances/Services/Fiscal/AtolFiscalDriver.php
php -l app/Domains/Finances/Services/TinkoffDriver.php
php -l app/Domains/Finances/Services/FiscalService.php

# 2. Проверить конфиг
php artisan config:show fiscal

# 3. Проверить здоровье
php artisan tinker
> app('App\Domains\Finances\Services\FiscalService')->healthCheck()

# 4. Отправить тестовый чек
# (см. шаг 5.2 выше)

# 5. Проверить логи
tail -f storage/logs/laravel-*.log

# 6. Убедиться в резервной копии
ls -la backup/

# Если все ОК → готово к production! ✅
```

---

**Версия**: 1.0
**Дата**: 2024
**Статус**: ✅ PRODUCTION READY

🎉 **Успешного развертывания!**
