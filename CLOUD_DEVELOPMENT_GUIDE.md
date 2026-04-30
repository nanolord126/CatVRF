# Быстрый старт: Облачная разработка CatVRF

**Время настройки:** 3-5 минут  
**Стоимость:** Бесплатно (60 часов/месяц на GitHub Codespaces)

---

## GitHub Codespaces (рекомендуется)

### Шаг 1: Создать Codespace

1. Откройте репозиторий: https://github.com/nanolord126/CatVRF
2. Нажмите зеленую кнопку **"Code"**
3. Выберите вкладку **"Codespaces"**
4. Нажмите **"Create codespace on main"**
5. Дождитесь создания среды (2-3 минуты)

### Шаг 2: Автоматическая настройка

Codespaces автоматически выполнит:
- Установку PHP 8.3 и расширений
- Установку Composer и зависимостей
- Установку Node.js и npm зависимостей
- Создание .env файла
- Генерацию application key
- Настройку SQLite базы данных
- Выполнение миграций
- Создание storage links
- Кеширование конфигураций

### Шаг 3: Запуск приложения

После завершения настройки выполните:

```bash
# Запустить Laravel сервер
php artisan serve --host=0.0.0.0 --port=80
```

Приложение будет доступно по адресу: `http://localhost:80`

### Шаг 4: Запуск тестов

```bash
# Запустить все тесты
php artisan test

# Запустить конкретный тест
php artisan test --filter TestName
```

---

## Gitpod (альтернатива)

### Шаг 1: Открыть в Gitpod

1. Откройте: https://gitpod.io/#https://github.com/nanolord126/CatVRF
2. Дождитесь создания среды (2-3 минуты)

### Шаг 2: Автоматическая настройка

Gitpod автоматически выполнит те же шаги, что и Codespaces.

### Шаг 3: Запуск приложения

Сервер запустится автоматически. Приложение будет доступно по адресу: `http://localhost:80`

---

## Полезные команды

### Миграции

```bash
# Выполнить все миграции
php artisan migrate

# Откатить последнюю миграцию
php artisan migrate:rollback

# Сбросить и пересоздать все миграции
php artisan migrate:fresh --seed
```

### Тесты

```bash
# Запустить все тесты
php artisan test

# Запустить с coverage
php artisan test --coverage

# Запустить только unit тесты
php artisan test --testsuite=Unit
```

### Кеширование

```bash
# Очистить все кеши
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Кешировать для продакшн
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Composer

```bash
# Установить зависимости
composer install

# Обновить зависимости
composer update

# Добавить пакет
composer require package/name
```

### NPM

```bash
# Установить зависимости
npm install

# Запустить dev server
npm run dev

# Собрать для продакшн
npm run build
```

---

## Решение проблем

### Проблема: Composer install не работает

```bash
# Очистить кеш Composer
composer clear-cache

# Переустановить без кеша
composer install --no-cache --prefer-dist
```

### Проблема: Миграции не выполняются

```bash
# Проверить подключение к базе данных
php artisan tinker
>>> DB::connection()->getPdo();

# Сбросить базу данных
php artisan migrate:fresh
```

### Проблема: Порты недоступны

Codespaces автоматически форвардит порты. Если порт не открывается:
1. Нажмите на "Ports" в нижней панели
2. Найдите порт 80
3. Нажмите "Open in Browser"

---

## Стоимость

### GitHub Codespaces
- **Бесплатно:** 60 часов/месяц
- **Стандарт:** $0.18/час после лимита
- **Пример:** 8 часов/день × 20 дней = 160 часов = (160-60) × $0.18 = $18/месяц

### Gitpod
- **Бесплатно:** 50 часов/месяц
- **Standard:** $0.07/час
- **Пример:** 160 часов = (160-50) × $0.07 = $7.7/месяц

---

## Переход на VPS (для долгосрочной разработки)

Если вы планируете работать более 3 месяцев, дешевле использовать VPS:

**Hetzner CX22:** $4.5/мес (2GB RAM, неограниченное время)

Подробнее: [cloud-development-setup.md](cloud-development-setup.md)

---

## Следующие шаги

1. ✅ Создать Codespace или Gitpod
2. ✅ Дождаться автоматической настройки
3. ✅ Запустить приложение
4. ✅ Изучить [README.md](README.md) для понимания архитектуры
5. ✅ Начать разработку

---

**Документ создан:** 2026-04-30  
**Последнее обновление:** 2026-04-30
