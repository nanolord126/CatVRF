# 🗄️ ПЕРЕКЛЮЧЕНИЕ БАЗЫ ДАННЫХ: SQLite ↔ MySQL 8

## 📋 ТЕКУЩЕЕ СОСТОЯНИЕ

- **Локальная разработка**: SQLite (`.sqlite` файл)
- **Production**: MySQL 8.0
- **Docker Compose**: Готов для MySQL 8.0

## 🔄 ПЕРЕКЛЮЧЕНИЕ НА MYSQL 8

### 1. ТРЕБОВАНИЯ

```bash
# Docker Desktop должен быть установлен и запущен
# ИЛИ MySQL 8 установлен локально:
mysql --version  # MySQL  Ver 8.0+
```

### 2. КОНФИГУРАЦИЯ .env

```env
# ДЛЯ SQLITE (ТЕКУЩЕЕ):
DB_CONNECTION=sqlite

# ДЛЯ MYSQL:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=catvrf
DB_USERNAME=root
DB_PASSWORD=secret
```

### 3. ЗАПУСК MYSQL ЧЕРЕЗ DOCKER

```bash
# Запустить Docker Compose с MySQL 8
docker-compose up -d db redis app

# Проверить статус
docker-compose ps

# Просмотреть логи MySQL
docker-compose logs db

# Подключиться в MySQL контейнер
docker-compose exec db mysql -u root -p
```

### 4. МИГРАЦИИ И SEEDERS

```bash
# Создать базу данных
php artisan migrate:fresh --seed

# Или отдельно:
php artisan migrate
php artisan db:seed
```

### 5. ВОЗВРАТ НА SQLITE

```bash
# Просто изменить .env:
DB_CONNECTION=sqlite

# Файл БД находится в:
# database/tenant.sqlite (для tenant-specific данных)
# database/database.sqlite (для central данных)
```

## 📊 СРАВНЕНИЕ БЭКЕНДОВ

| Параметр | SQLite | MySQL 8 |
|----------|--------|---------|
| **Установка** | Встроена в PHP | Docker / Хост |
| **Размер** | Файл (~50MB) | Контейнер / Сервис |
| **Скорость** | Быстро для dev | Оптимизирована для prod |
| **Параллелизм** | Слабый | Сильный |
| **Резервные копии** | Копия файла | mysqldump / snapshots |
| **Масштабируемость** | ❌ Ограничена | ✅ Хорошая |
| **Для разработки** | ✅ Идеально | Слишком heavy |
| **Для production** | ❌ Не рекомендуется | ✅ Идеально |

## 🚀 DEPLOYMENT НА PRODUCTION

### С Docker

```bash
docker-compose -f docker-compose.yml up -d
```

### На выделенном сервере (Ubuntu/CentOS)

```bash
# 1. Установить MySQL 8
curl -sL https://dev.mysql.com/get/mysql-apt-config_0.8.17-1_all.deb -o mysql-apt-config.deb
sudo dpkg -i mysql-apt-config.deb
sudo apt-get update
sudo apt-get install mysql-server

# 2. Запустить миграции
ssh user@server "cd /var/www && php artisan migrate --force"

# 3. Заполнить данные
ssh user@server "cd /var/www && php artisan db:seed"
```

## 🔍 ОТЛАДКА ПРОБЛЕМ

### MySQL не подключается

```bash
# Проверить конфиг
php artisan tinker
>>> DB::connection('mysql')->getPdo();

# Проверить Docker
docker-compose exec db mysql -u root -p secret -e "SELECT VERSION();"
```

### Миграции падают

```bash
# Проверить статус миграций
php artisan migrate:status

# Сбросить и начать заново
php artisan migrate:reset
php artisan migrate
```

## 📝 ФАЙЛЫ, ТРЕБУЮЩИЕ ИЗМЕНЕНИЯ ПРИ ПЕРЕКЛЮЧЕНИИ

1. `.env` - DB_CONNECTION
2. `config/database.php` - default connection
3. `docker-compose.yml` - образ БД
4. Миграции - если есть SQLite-специфичный код

## ✅ СТАТУС МИГРАЦИЙ ДЛЯ MYSQL

Все 44 миграции совместимы с MySQL 8:

- ✅ Используются стандартные Schema методы
- ✅ Нет SQLite-специфичного синтаксиса
- ✅ Поддерживаются foreign keys
- ✅ JSON столбцы работают

---

**Обновлено**: 10/03/2026
**Версия**: Production-Ready v1.0
