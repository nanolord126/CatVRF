# ETAP 1 - КОМАНДЫ ДЛЯ ВЫПОЛНЕНИЯ

**Проект**: CatVRF - Laravel 11  
**Статус**: Готово к выполнению (70% завершено)  
**Дата**: 2026-03-28

---

## 📋 ПОЛНЫЙ ЧЕКЛИСТ КОМАНД

### ФАЗА 1: ВЕРИФИКАЦИЯ (5 минут)

```bash
# Перейти в директорию проекта
cd c:\opt\kotvrf\CatVRF

# Проверить текущее состояние архитектуры
php middleware_architecture_verification.php

# Проверить результаты
# Файл: MIDDLEWARE_VERIFICATION_REPORT.json
```

**Что проверяется**:
- ✅ Все 5 middleware классов существуют
- ✅ BaseApiController чист (только helper методы)
- ✅ Kernel.php правильно настроен
- ✅ Контроллеры на дублирование
- ✅ Routes на порядок middleware

**Успех**: Если статус = "GOOD" или максимум "ISSUES" (но не CRITICAL)

---

### ФАЗА 2: ДИАГНОСТИКА (5 минут, ОПЦИОНАЛЬНО)

```bash
# Анализ middleware реализаций
php audit_middleware_refactor.php

# Анализ дублирующихся паттернов в контроллерах
php middleware_cleanup_analysis.php
```

**Когда запускать**: Если хотите детальный анализ перед очисткой

---

### ФАЗА 3: ОЧИСТКА КОНТРОЛЛЕРОВ (2-3 минуты)

```bash
# ОСНОВНАЯ КОМАНДА: Удаляет дублирующийся код
php full_controller_refactor.php

# Проверить результаты
# Файл: MIDDLEWARE_REFACTOR_COMPLETE.json
```

**Что удаляется**:
- ❌ FraudControlService инжекции
- ❌ RateLimiterService инжекции
- ❌ Ручное создание correlation_id
- ❌ Вызовы fraud check
- ❌ Вызовы rate limiting
- ❌ Определение B2B режима

**Ожидаемые результаты**:
- ~200+ строк удалено
- ~40 контроллеров обработано
- Файлы контроллеров на 30-40% меньше

---

### ФАЗА 4: ОБНОВЛЕНИЕ ROUTES (30-60 минут, РУЧНО)

#### Файл 1: routes/api.php

```php
// СТАРОЕ (❌ Неправильно)
Route::middleware(['correlation-id', 'enrich-context'])
    ->group(function () {
        // routes
    });

// НОВОЕ (✅ Правильно)
Route::middleware([
    'correlation-id',
    'auth:sanctum',
    'tenant',
    'b2c-b2b',
    'rate-limit',
    'fraud-check',
    'age-verify',
])->group(function () {
    // routes
});
```

#### Файл 2: routes/api-v1.php

```php
// Добавить тот же порядок middleware как выше
Route::middleware([
    'correlation-id',
    'auth:sanctum',
    'tenant',
    'b2c-b2b',
    'rate-limit',
    'fraud-check',
    'age-verify',
])->group(function () {
    // routes
});
```

#### Файл 3: routes/[vertical].api.php (Повторить для всех 48 вертикалей)

```php
// Для каждого вертикального маршрута добавить full middleware order
```

**Порядок ОБЯЗАТЕЛЕН**:
1. correlation-id (генерировать/валидировать correlation_id)
2. auth:sanctum (аутентификация пользователя)
3. tenant (tenant scoping)
4. b2c-b2b (определить B2C/B2B режим)
5. rate-limit (проверка rate limiting)
6. fraud-check (ML fraud detection)
7. age-verify (проверка возраста)

---

### ФАЗА 5: ГЕНЕРИРОВАНИЕ ФИНАЛЬНОГО ОТЧЁТА (1 минута)

```bash
# Генерировать comprehensive финальный отчёт
php generate_final_report.php

# Проверить результаты
# Файл: ETAP1_MIDDLEWARE_REFACTOR_FINAL_REPORT.json
```

**Что содержит отчёт**:
- До/после метрики
- Проверка middleware порядка
- Чеклист тестирования
- Список обновлённых файлов

---

## 🧪 ФАЗА 6: ТЕСТИРОВАНИЕ (1-2 часа)

### Тест 1: Correlation ID

```bash
# Проверить, что correlation_id инжектируется
curl -X GET http://localhost:8000/api/health \
  -H "X-Correlation-ID: 123e4567-e89b-12d3-a456-426614174000"

# Проверка: Ответ должен содержать X-Correlation-ID header
```

### Тест 2: B2C/B2B Mode

```bash
# B2C режим (без INN)
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 1}'

# B2B режим (с INN)
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 1, "inn": "7701234567"}'
```

### Тест 3: Rate Limiting

```bash
# Выполнить 35 запросов (лимит = 30/мин для promo)
for i in {1..35}; do
  echo "Request $i"
  curl -X GET http://localhost:8000/api/v1/promo/list \
    -H "Authorization: Bearer YOUR_TOKEN"
done

# Проверка: Запросы 1-30 успешны (200), запрос 31-35 - 429 Too Many Requests
# Ответ должен содержать:
# - X-RateLimit-Limit: 30
# - X-RateLimit-Remaining: X
# - X-RateLimit-Reset: timestamp
# - Retry-After: seconds
```

### Тест 4: Fraud Detection

```bash
# Заблокировать подозрительный платёж
curl -X POST http://localhost:8000/api/payments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 9999999,
    "currency": "RUB"
  }'

# Проверка: Ответ должен быть 403 Forbidden (заблокирован)
# Сообщение: "Подозрение на мошенничество"
```

### Тест 5: Age Verification

```bash
# Попытка молодого пользователя купить в pharmacy
curl -X POST http://localhost:8000/api/pharmacy/orders \
  -H "Authorization: Bearer TOKEN_YOUNG_USER" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 123,
    "quantity": 1
  }'

# Проверка: Ответ должен быть 403 Forbidden
# Сообщение: "Вам должно быть минимум 18 лет"
```

### Тест 6: Проверить логи

```bash
# Проверить audit логи
tail -f storage/logs/laravel.log | grep -i "audit"

# Проверить fraud логи
tail -f storage/logs/laravel.log | grep -i "fraud"

# Проверить, что correlation_id присутствует в логах
grep -r "correlation_id" storage/logs/
```

---

## 📊 ПОЛНЫЙ ЦИКЛ КОМАНД (Скопировать и выполнить)

```bash
#!/bin/bash
# Complete ETAP 1 Execution Script

cd c:\opt\kotvrf\CatVRF

echo "=== ETAP 1 MIDDLEWARE REFACTOR - COMPLETE EXECUTION ==="
echo ""

# Phase 1: Verification
echo "PHASE 1: Architecture Verification (5 sec)"
php middleware_architecture_verification.php
echo "✓ Phase 1 complete - Review MIDDLEWARE_VERIFICATION_REPORT.json"
echo ""

# Phase 2: Cleanup
echo "PHASE 2: Controller Cleanup (2-3 min)"
php full_controller_refactor.php
echo "✓ Phase 2 complete - Review MIDDLEWARE_REFACTOR_COMPLETE.json"
echo ""

# Phase 3: Reporting
echo "PHASE 3: Final Report Generation (1 min)"
php generate_final_report.php
echo "✓ Phase 3 complete - Review ETAP1_MIDDLEWARE_REFACTOR_FINAL_REPORT.json"
echo ""

echo "=== ETAP 1 EXECUTION COMPLETE ==="
echo ""
echo "NEXT STEPS:"
echo "1. Manually update routes files with correct middleware order"
echo "2. Run tests (see ETAP1_MIDDLEWARE_REFACTOR_GUIDE.md)"
echo "3. Deploy and verify in production"
echo ""
```

---

## 🚨 ЕСЛИ ЧТО-ТО ПОШЛО НЕ ТАК

### Проблема: "Middleware not found"

```bash
# Проверить, что middleware файлы существуют
ls -la app/Http/Middleware/ | grep Middleware

# Проверить Kernel.php
grep -n "Middleware" app/Http/Kernel.php
```

### Проблема: "Controllers not updated"

```bash
# Запустить диагностику
php audit_middleware_refactor.php

# Проверить отчёт
cat MIDDLEWARE_REFACTOR_COMPLETE.json
```

### Проблема: "Tests failing"

```bash
# Проверить логи
tail -f storage/logs/laravel.log

# Проверить, что middleware в routes
grep -r "middleware.*correlation-id" routes/
```

---

## ✅ ПРОВЕРКА ЗАВЕРШЕНИЯ

Когда все команды выполнены, проверьте:

```bash
# 1. Файлы созданы?
ls -la MIDDLEWARE_VERIFICATION_REPORT.json
ls -la MIDDLEWARE_REFACTOR_COMPLETE.json
ls -la ETAP1_MIDDLEWARE_REFACTOR_FINAL_REPORT.json

# 2. Контроллеры очищены?
grep -r "FraudControlService" app/Http/Controllers/Api/ | wc -l
# Должно быть 0 (ноль) результатов

# 3. Middleware в routes?
grep -r "fraud-check" routes/

# 4. Тесты проходят?
php artisan test
```

---

## 📝 ДОКУМЕНТАЦИЯ ДЛЯ СПРАВКИ

- **README_ETAP1_INSTRUCTIONS.md** - Пошаговые инструкции
- **ETAP1_MIDDLEWARE_REFACTOR_GUIDE.md** - Архитектура и примеры
- **ETAP1_COMPLETION_STATUS.md** - Подробный статус
- **ETAP1_QUICKSTART_RU.md** - Быстрый старт на русском

---

## 🎯 РЕКОМЕНДУЕМАЯ ПОСЛЕДОВАТЕЛЬНОСТЬ

1. ✅ Прочитать README_ETAP1_INSTRUCTIONS.md (10 мин)
2. ✅ Выполнить: `php middleware_architecture_verification.php` (5 сек)
3. ✅ Выполнить: `php full_controller_refactor.php` (2-3 мин)
4. ✅ Обновить routes вручную (30-60 мин)
5. ✅ Выполнить: `php generate_final_report.php` (1 мин)
6. ✅ Протестировать все эндпоинты (1-2 часа)
7. ✅ Развернуть в production

---

## 🎊 УСПЕШНОЕ ЗАВЕРШЕНИЕ

Когда все выполнено:

✅ BaseApiController содержит только helper методы  
✅ Все контроллеры очищены от дублирующегося кода  
✅ Middleware выполняются в правильном порядке  
✅ Все тесты проходят  
✅ Correlation_id трекируется во всех логах  
✅ Rate limiting работает  
✅ Fraud detection работает  
✅ Age verification работает  

---

**Версия**: 1.0 - ETAP 1 Command Reference  
**Status**: Ready to Execute  
**Project**: CatVRF - Laravel 11  
**Date**: 2026-03-28

**НАЧНИТЕ С**:
```bash
php middleware_architecture_verification.php
```
