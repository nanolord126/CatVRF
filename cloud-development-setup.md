# Облачное решение для разработки CatVRF

**Дата:** 2026-04-30  
**Цель:** Заменить локальный Docker на облачное решение для экономии ресурсов локальной машины

---

## Обзор облачных решений

### Вариант 1: GitHub Codespaces (РЕКОМЕНДУЕТСЯ)

**Преимущества:**
- ✅ Глубокая интеграция с GitHub
- ✅ Предустановленный Docker
- ✅ VS Code в браузере
- ✅ Бесплатные часы для личных аккаунтов (60 часов/месяц)
- ✅ Автоматическое сохранение состояния
- ✅ Мгновенный запуск среды
- ✅ Предустановленные расширения Laravel

**Недостатки:**
- ⚠️ Ограничение по бесплатным часам
- ⚠️ Требует GitHub
- ⚠️ Зависимость от интернета

**Стоимость:**
- Бесплатно: 60 часов/месяц для личных аккаунтов
- Платно: $0.18/час после лимита

**Настройка:** Требуется `.devcontainer/devcontainer.json`

---

### Вариант 2: Gitpod

**Преимущества:**
- ✅ Поддержка GitHub, GitLab, Bitbucket
- ✅ Предустановленный Docker
- ✅ VS Code или JetBrains IDE в браузере
- ✅ 50 бесплатных часов/месяц
- ✅ Гибкая конфигурация
- ✅ Предустановленные рабочие пространства

**Недостатки:**
- ⚠️ Ограничение по бесплатным часам
- ⚠️ Требует настройку `.gitpod.yml`

**Стоимость:**
- Бесплатно: 50 часов/месяц
- Платно: $0.07/час (Standard), $0.15/час (Professional)

**Настройка:** Требуется `.gitpod.yml`

---

### Вариант 3: VS Code Remote + VPS

**Преимущества:**
- ✅ Полный контроль над сервером
- ✅ Неограниченное время работы
- ✅ Можно использовать любой VPS провайдер
- ✅ Локальный VS Code подключается к удаленному серверу
- ✅ Дешевле в долгосрочной перспективе

**Недостатки:**
- ⚠️ Требует настройку VPS
- ⚠️ Нужно платить за VPS постоянно
- ⚠️ Требует базовые знания Linux администрирования

**Стоимость:**
- VPS: $5-20/месяц (в зависимости от ресурсов)
- Неограниченное время работы

**Настройка:** Требуется VPS + SSH ключи + установка Docker

**VPS провайдеры:**
- DigitalOcean ($5/мес за 1GB RAM, 1 vCPU)
- Hetzner ($4.5/мес за 2GB RAM, 1 vCPU)
- AWS Lightsail ($3.5/мес за 512MB RAM, 1 vCPU)
- Linode ($5/мес за 1GB RAM, 1 vCPU)

---

### Вариант 4: Railway / Render (для dev среды)

**Преимущества:**
- ✅ Простая настройка
- ✅ Автоматический деплой из GitHub
- ✅ Предустановленные базы данных
- ✅ Бесплатный tier для разработки

**Недостатки:**
- ⚠️ Не предназначено для активной разработки (больше для staging/production)
- ⚠️ Ограниченные ресурсы на бесплатном tier
- ⚠️ Холодный старт при бездействии

**Стоимость:**
- Railway: $5/мес после бесплатного периода
- Render: Бесплатный tier с ограничениями

**Настройка:** Подключение репозитория + настройка переменных окружения

---

## Рекомендация

### Для быстрого старта: GitHub Codespaces

**Почему:**
- Мгновенный запуск (2-3 минуты)
- Глубокая интеграция с GitHub
- Предустановленный Docker
- Бесплатные 60 часов/месяц достаточно для разработки
- Не требует настройки VPS

**Идеально для:**
- Начало работы над проектом
- Быстрого тестирования
- Временных задач

### Для долгосрочной разработки: VS Code Remote + VPS

**Почему:**
- Полный контроль над средой
- Неограниченное время работы
- Дешевле в долгосрочной перспективе ($5-20/мес vs $0.18/час)
- Можно настроить под конкретные нужды

**Идеально для:**
- Постоянной разработки
- Командной работы
- Требовательных к ресурсам задач

---

## План реализации

### Вариант A: GitHub Codespaces (быстрый старт)

**Шаг 1: Создать `.devcontainer/devcontainer.json`**
```json
{
  "name": "CatVRF Development",
  "image": "mcr.microsoft.com/devcontainers/base:ubuntu-22.04",
  "features": {
    "ghcr.io/devcontainers/features/node:1": {
      "version": "lts"
    },
    "ghcr.io/devcontainers/features/php:1": {
      "version": "8.3",
      "installComposer": true,
      "installXdebug": true
    },
    "ghcr.io/devcontainers/features/docker-in-docker:2": {}
  },
  "customizations": {
    "vscode": {
      "extensions": [
        "mikestead.dotenv",
        "felixfbecker.php-intellisense",
        "bmewburn.vscode-intelephense-client",
        "eamodio.gitlens",
        "GitHub.copilot"
      ]
    }
  },
  "postCreateCommand": "composer install && php artisan key:generate",
  "forwardPorts": [80, 5432, 6379, 3306],
  "portsAttributes": {
    "80": {
      "label": "HTTP",
      "onAutoForward": "openBrowser"
    }
  }
}
```

**Шаг 2: Создать Codespace**
1. Открыть репозиторий на GitHub
2. Нажать "Code" → "Codespaces" → "Create codespace on main"
3. Дождаться создания среды (2-3 минуты)

**Шаг 3: Установить зависимости**
```bash
cd /workspaces/CatVRF
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

**Шаг 4: Запустить приложение**
```bash
php artisan serve --host=0.0.0.0 --port=80
```

---

### Вариант B: VS Code Remote + VPS (долгосрочный)

**Шаг 1: Создать VPS**
- Рекомендуемый провайдер: Hetzner ($4.5/мес за 2GB RAM)
- ОС: Ubuntu 22.04 LTS
- Регион: Frankfurt (ближе к России)

**Шаг 2: Настроить VPS**
```bash
# Подключиться к VPS
ssh root@your-vps-ip

# Обновить систему
apt update && apt upgrade -y

# Установить Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Установить Docker Compose
curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# Установить PHP 8.3
add-apt-repository ppa:ondrej/php
apt update
apt install -y php8.3 php8.3-fpm php8.3-pgsql php8.3-redis php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd php8.3-sqlite3

# Установить Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Создать пользователя для разработки
adduser developer
usermod -aG docker developer
```

**Шаг 3: Настроить SSH ключи**
```bash
# На локальной машине
ssh-keygen -t ed25519 -C "your-email@example.com"
cat ~/.ssh/id_ed25519.pub

# Добавить публичный ключ в VPS
ssh-copy-id -i ~/.ssh/id_ed25519.pub developer@your-vps-ip
```

**Шаг 4: Подключить VS Code Remote**
1. Установить расширение "Remote - SSH" в VS Code
2. Нажать F1 → "Remote-SSH: Connect to Host"
3. Добавить хост: `developer@your-vps-ip`
4. Подключиться

**Шаг 5: Клонировать репозиторий**
```bash
cd /home/developer
git clone https://github.com/nanolord126/CatVRF.git
cd CatVRF
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

**Шаг 6: Запустить с Docker Compose**
```bash
docker-compose up -d
```

---

## Сравнение стоимости

### GitHub Codespaces
- Бесплатно: 60 часов/месяц
- Платно: $0.18/час
- При 8 часах работы/день × 20 дней = 160 часов/месяц
- Стоимость: (160 - 60) × $0.18 = $18/месяц

### VS Code Remote + VPS (Hetzner)
- VPS: $4.5/месяц (2GB RAM, 1 vCPU)
- Неограниченное время работы
- Стоимость: $4.5/месяц

**Экономия при VPS:** $13.5/месяц (75% дешевле)

---

## Рекомендация по выбору

### Использовать GitHub Codespaces если:
- Нужно быстро начать работу (сегодня)
- Проект в разработке менее 3 месяцев
- Используется GitHub
- Нужна простая настройка без администрирования

### Использовать VS Code Remote + VPS если:
- Проект в долгосрочной разработке (3+ месяцев)
- Нужна экономия бюджета
- Требуется полный контроль над средой
- Командная работа над проектом
- Нужны дополнительные сервисы (Redis, ClickHouse и т.д.)

---

## Следующие шаги

### Для GitHub Codespaces (быстрый старт):
1. Создать `.devcontainer/devcontainer.json`
2. Создать Codespace на GitHub
3. Установить зависимости
4. Начать разработку

### Для VS Code Remote + VPS (долгосрочный):
1. Создать VPS на Hetzner ($4.5/мес)
2. Настроить Docker и PHP
3. Настроить SSH ключи
4. Подключить VS Code Remote
5. Клонировать репозиторий
6. Начать разработку

---

**Документ создан:** 2026-04-30  
**Рекомендация:** Начать с GitHub Codespaces для быстрого старта, перейти на VPS для долгосрочной разработки
