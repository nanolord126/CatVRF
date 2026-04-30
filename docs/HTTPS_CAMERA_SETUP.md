# HTTPS Setup for Camera Access in Production

**Версия:** 1.0  
**Дата:** 2026-04-28  
**Модуль:** Barcode Scanner - Camera Access

## Обзор

Браузеры требуют HTTPS соединения для доступа к камере устройства в продакшене. Без HTTPS камера не будет работать. Это руководство описывает настройку HTTPS для CatVRF.

## Почему HTTPS обязателен

**Политика безопасности браузеров:**
- Chrome, Firefox, Safari, Edge требуют HTTPS для `getUserMedia()`
- Исключение: `localhost` и `127.0.0.1` в режиме разработки
- В продакшене без HTTPS камера будет недоступна

**Варианты HTTPS:**
1. Let's Encrypt (бесплатный SSL сертификат)
2. Cloudflare (бесплатный SSL через CDN)
3. Nginx/Apache с собственным сертификатом
4. AWS Certificate Manager (для AWS)

## Вариант 1: Let's Encrypt (рекомендуется)

### Установка Certbot

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install certbot python3-certbot-nginx
```

**CentOS/RHEL:**
```bash
sudo yum install certbot python3-certbot-nginx
```

### Получение сертификата

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Следуйте инструкциям:
1. Введите email для уведомлений
2. Примите условия обслуживания
3. Выберите перенаправление HTTP на HTTPS

### Автоматическое обновление

```bash
sudo certbot renew --dry-run
```

Certbot автоматически настраивает cron job для обновления.

## Вариант 2: Cloudflare (самый простой)

### Шаги

1. **Зарегистрируйтесь на Cloudflare**
   - Бесплатный план поддерживает HTTPS

2. **Добавьте ваш домен**
   - Следуйте инструкциям по изменению NS записей

3. **Настройте DNS**
   ```
   A    yourdomain.com    your-server-ip
   A    www.yourdomain.com    your-server-ip
   ```

4. **Включите SSL/TLS**
   - Перейдите в SSL/TLS → Overview
   - Выберите режим "Full" или "Full (strict)"
   - Включите "Always Use HTTPS"

5. **Настройте Origin Certificate** (опционально)
   - SSL/TLS → Origin Server → Create Certificate
   - Установите сертификат на сервер

### Nginx конфигурация для Cloudflare

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    # Cloudflare Origin Certificate (если используется)
    ssl_certificate /etc/ssl/cloudflare-origin.pem;
    ssl_certificate_key /etc/ssl/cloudflare-origin.key;

    # Или Let's Encrypt
    # ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /var/www/catvrf/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Вариант 3: Собственный SSL сертификат

### Генерация самоподписанного сертификата

**Только для тестирования! Не использовать в продакшене.**

```bash
sudo mkdir -p /etc/ssl/localcerts
sudo openssl req -new -x509 -days 365 -nodes \
  -out /etc/ssl/localcerts/apache.pem \
  -keyout /etc/ssl/localcerts/apache.key
```

### Nginx конфигурация

```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;

    ssl_certificate /etc/ssl/localcerts/apache.pem;
    ssl_certificate_key /etc/ssl/localcerts/apache.key;

    root /var/www/catvrf/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Добавление исключения в браузере

Для самоподписанных сертификатов пользователю нужно:
1. Открыть сайт в браузере
2. Принять предупреждение безопасности
3. Добавить исключение

**Не рекомендуется для продакшена!**

## Вариант 4: AWS Certificate Manager

### Для AWS ECS/Elastic Beanstalk

1. **Создайте сертификат в ACM**
   ```bash
   aws acm request-certificate \
     --domain-name yourdomain.com \
     --validation-method DNS
   ```

2. **Подтвердите домен**
   - Добавьте CNAME записи из ACM в DNS

3. **Используйте сертификат в Load Balancer**
   - В конфигурации Load Balancer выберите ACM сертификат

### Terraform конфигурация

```hcl
resource "aws_acm_certificate" "cert" {
  domain_name       = "yourdomain.com"
  validation_method = "DNS"

  subject_alternative_names = [
    "*.yourdomain.com"
  ]

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_acm_certificate_validation" "cert_validation" {
  certificate_arn         = aws_acm_certificate.cert.arn
  validation_record_fqdns = [for record in aws_acm_certificate.cert.domain_validation_options : record.resource_record_name]
}
```

## Laravel Конфигурация

### Обновление .env

```env
APP_URL=https://yourdomain.com
ASSET_URL=https://yourdomain.com

# Force HTTPS
FORCE_HTTPS=true
```

### Middleware для HTTPS

```php
// app/Http/Middleware/ForceHttps.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
```

### Регистрация middleware

```php
// app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\ForceHttps::class,
];
```

## Проверка HTTPS

### Проверка сертификата

```bash
# Проверка срока действия
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com | openssl x509 -noout -dates

# Проверка цепочки сертификатов
openssl s_client -connect yourdomain.com:443 -showcerts
```

### Онлайн инструменты

- SSL Labs: https://www.ssllabs.com/ssltest/
- Qualys SSL Server Test
- Let's Debug: https://letsdebug.net/

## Тестирование камеры с HTTPS

### Локальное тестирование с HTTPS

**Использование mkcert для локальной разработки:**

```bash
# Установка mkcert
# macOS
brew install mkcert nss

# Linux
sudo apt install mkcert

# Windows
chocolatey install mkcert

# Создание локального CA
mkcert -install

# Создание сертификата
mkcert localhost 127.0.0.1 ::1

# Использование в Nginx
ssl_certificate /path/to/localhost+2.pem;
ssl_certificate_key /path/to/localhost+2-key.pem;
```

### Тестирование на мобильных устройствах

1. **Убедитесь, что HTTPS работает**
   - Откройте сайт на мобильном устройстве
   - Проверьте замок в адресной строке

2. **Протестируйте камеру**
   - Откройте страницу сканера
   - Разрешите доступ к камере
   - Проверьте распознавание штрихкодов

3. **Проверьте PWA (если настроен)**
   - Попробуйте установить приложение
   - Проверьте работу оффлайн

## Troubleshooting

### Ошибка "Permission denied" для камеры

**Причина:** HTTP вместо HTTPS

**Решение:**
1. Проверьте, что сайт работает по HTTPS
2. Проверьте сертификат (не истек ли)
3. Проверьте цепочку сертификатов

### Смешанный контент (Mixed Content)

**Причина:** Ресурсы загружаются по HTTP на HTTPS странице

**Решение:**
```nginx
# В Nginx добавить
add_header Content-Security-Policy "upgrade-insecure-requests";
```

Или в Laravel:
```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\URL;

public function boot()
{
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }
}
```

### Сертификат не доверяется

**Причина:** Самоподписанный сертификат или истекший

**Решение:**
- Используйте Let's Encrypt или Cloudflare
- Обновите сертификат

### Перенаправление не работает

**Проверьте Nginx конфигурацию:**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$host$request_uri;
}
```

### CORS ошибки

**Для API запросов:**
```nginx
add_header 'Access-Control-Allow-Origin' 'https://yourdomain.com';
add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
add_header 'Access-Control-Allow-Headers' 'Authorization, Content-Type';
```

## Мониторинг SSL

### Проверка автоматического обновления

```bash
# Проверка статуса certbot
sudo systemctl status certbot.timer

# Ручное обновление
sudo certbot renew

# Проверка логов
sudo cat /var/log/letsencrypt/letsencrypt.log
```

### Alerting для истекающих сертификатов

```bash
# Скрипт проверки
#!/bin/bash
DAYS=30
DOMAIN="yourdomain.com"

EXPIRY=$(echo | openssl s_client -servername $DOMAIN -connect $DOMAIN:443 2>/dev/null | openssl x509 -noout -dates | grep notAfter | cut -d= -f2)
EXPIRY_EPOCH=$(date -d "$EXPIRY" +%s)
CURRENT_EPOCH=$(date +%s)
DAYS_LEFT=$(( ($EXPIRY_EPOCH - $CURRENT_EPOCH) / 86400 ))

if [ $DAYS_LEFT -lt $DAYS ]; then
    echo "WARNING: SSL certificate expires in $DAYS_LEFT days"
    # Отправить уведомление
fi
```

## Best Practices

1. **Всегда используйте HTTPS в продакшене**
2. **Используйте HSTS** (HTTP Strict Transport Security)
   ```nginx
   add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
   ```

3. **Обновляйте сертификаты автоматически**
4. **Используйте современные протоколы** (TLS 1.2, TLS 1.3)
5. **Отключайте устаревшие протоколы** (SSL, TLS 1.0, TLS 1.1)
6. **Мониторьте срок действия сертификатов**
7. **Используйте CDN** (Cloudflare, AWS CloudFront) для дополнительной защиты

## Резюме

| Метод | Сложность | Стоимость | Рекомендуется |
|-------|-----------|-----------|---------------|
| Let's Encrypt | Средняя | Бесплатно | ✅ Да |
| Cloudflare | Низкая | Бесплатно | ✅ Да |
| Собственный сертификат | Низкая | Платно | ❌ Нет |
| ACM (AWS) | Средняя | Бесплатно (в AWS) | ✅ Да (для AWS) |

## Поддержка

Для вопросов по настройке HTTPS обратитесь к системному администратору или DevOps команде.
