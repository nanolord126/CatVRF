# Performance Production Guide 2026

**Версия:** 1.0 (16.04.2026)  
**Цель:** Гайд по мониторингу TTFB и настройке Octane/Redis в production окружении CatVRF.

---

## 1. Мониторинг TTFB в реальном времени

### 1.1 Prometheus Metrics

RoadRunner предоставляет метрики на `/metrics` endpoint.

```bash
# Проверка метрик
curl http://localhost:2112/metrics
```

**Ключевые метрики для мониторинга:**

```promql
# Время ответа HTTP запросов (95th percentile)
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))

# Количество запросов по статусу
rate(http_requests_total{status=~"5.."}[5m])

# Worker pool utilization
rr_worker_pool_busy / rr_worker_pool_total
```

### 1.2 Grafana Dashboard

Создайте дашборд в Grafana со следующими панелями:

**Panel 1: TTFB (95th percentile)**
```promql
histogram_quantile(0.95, 
  sum(rate(http_request_duration_seconds_bucket[5m])) by (le)
)
```
- Threshold: < 0.08s (80ms)
- Alert: > 0.15s (150ms) for 5% of requests

**Panel 2: Request Rate**
```promql
sum(rate(http_requests_total[1m]))
```

**Panel 3: Error Rate**
```promql
sum(rate(http_requests_total{status=~"5.."}[5m])) / 
sum(rate(http_requests_total[5m]))
```
- Threshold: < 1%
- Alert: > 5%

**Panel 4: Redis Operations**
```promql
rate(redis_commands_processed_total[1m])
```

### 1.3 Laravel Telescope (Development/Staging)

```bash
# Установка
composer require laravel/telescope --dev

# Публикация
php artisan telescope:install

# Запуск
php artisan telescope
```

Доступ: `http://your-domain.com/telescope`

### 1.4 ClickHouse Slow Query Log

```sql
-- Создание таблицы для медленных запросов
CREATE TABLE IF NOT EXISTS catvrf_analytics.slow_queries
(
    query_id String,
    query String,
    duration_ms UInt32,
    timestamp DateTime,
    tenant_id Nullable(UInt64),
    user_id Nullable(UInt64)
)
ENGINE = MergeTree()
ORDER BY (timestamp, duration_ms);

-- Материализованное представление
CREATE MATERIALIZED VIEW IF NOT EXISTS catvrf_analytics.slow_queries_mv
ENGINE = AggregatingMergeTree()
AS SELECT
    now() as timestamp,
    queryID() as query_id,
    query() as query,
    queryDuration_ms() as duration_ms,
    getTenantID() as tenant_id,
    getUserID() as user_id
FROM system.query_log
WHERE queryDuration_ms() > 100
AND type = 'QueryFinish';
```

---

## 2. Настройка Laravel Octane (RoadRunner)

### 2.1 Установка RoadRunner

```bash
# Установка RoadRunner через Composer
composer require spiral/roadrunner-cli --dev
composer require spiral/roadrunner-http --dev

# Или скачать бинарник
wget https://github.com/roadrunner-server/roadrunner/releases/latest/download/roadrunner-linux-amd64
chmod +x roadrunner-linux-amd64
mv roadrunner-linux-amd64 /usr/local/bin/rr
```

### 2.2 Конфигурация .rr.yaml

Файл `.rr.yaml` уже создан в корне проекта. Основные настройки:

```yaml
server:
  http:
    address: 0.0.0.0:8000
    max_request_size: 20971520  # 20MB

service:
  rpc:
    listen: tcp://127.0.0.1:6001

php:
  workers:
    command: "php ./vendor/bin/roadrunner-worker"
    pool:
      num_workers: auto  # Авто = CPU cores
      max_jobs: 1000
      allocate_timeout: 60s
      destroy_timeout: 60s

logs:
  mode: production
  channels:
    http:
      level: info
      mode: json
      output: stdout

metrics:
  address: 0.0.0.0:2112
```

### 2.3 Environment Variables

```env
# .env production
OCTANE_SERVER=roadrunner
OCTANE_HTTPS=false
OCTANE_GC=null
OCTANE_MAX_EXECUTION_TIME=30
OCTANE_ROADRUNNER_BINARY=rr
OCTANE_ROADRUNNER_CONFIG=.rr.yaml
```

### 2.4 Запуск Octane

```bash
# Development
php artisan octane:start --server=roadrunner --watch

# Production
php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000

# Supervisord configuration (production)
[program:catvrf-octane]
command=php /var/www/catvrf/artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000
directory=/var/www/catvrf
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/catvrf/octane.log
```

### 2.5 Systemd Service (Linux)

```ini
# /etc/systemd/system/catvrf-octane.service
[Unit]
Description=CatVRF Octane (RoadRunner)
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/catvrf
ExecStart=/usr/bin/php /var/www/catvrf/artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

```bash
# Включение и запуск
sudo systemctl enable catvrf-octane
sudo systemctl start catvrf-octane
sudo systemctl status catvrf-octane
```

---

## 3. Настройка Redis для Production

### 3.1 Установка Redis

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install redis-server

# CentOS/RHEL
sudo yum install redis

# Docker
docker run -d --name catvrf-redis -p 6379:6379 redis:7-alpine
```

### 3.2 Конфигурация Redis (/etc/redis/redis.conf)

```conf
# Максимальная память (настраивайте по доступному RAM)
maxmemory 4gb

# Политика eviction (allkeys-lru для cache)
maxmemory-policy allkeys-lru

# Persistence (AOF для durability)
appendonly yes
appendfsync everysec

# Количество соединений
maxclients 10000

# Таймауты
timeout 300
tcp-keepalive 60

# Log level
loglevel notice

# Slow log (записывать запросы > 10ms)
slowlog-log-slower-than 10000
slowlog-max-len 128
```

### 3.3 Конфигурация Laravel (config/database.php)

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'catvrf:'),
    ],

    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
        'read_timeout' => 2.0,
        'write_timeout' => 2.0,
    ],

    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

### 3.4 Environment Variables

```env
# .env production
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Cache drivers
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=redis
```

### 3.5 Redis Sentinel (High Availability)

```env
# .env production с Sentinel
REDIS_CLIENT=phpredis
REDIS_SENTINEL=redis-sentinel
REDIS_SENTINEL_MASTER=mymaster
REDIS_SENTINEL_HOSTS=127.0.0.1:26379,127.0.0.1:26380,127.0.0.1:26381
```

---

## 4. Мониторинг Redis

### 4.1 Redis CLI

```bash
# Подключение
redis-cli

# Проверка здоровья
> ping
PONG

# Информация
> info memory
> info stats
> info replication

# Медленные запросы
> slowlog get 10

# Мониторинг в реальном времени
> monitor
```

### 4.2 Prometheus Redis Exporter

```bash
# Установка
docker run -d \
  --name redis-exporter \
  -p 9121:9121 \
  oliver006/redis_exporter \
  --redis.addr=redis://localhost:6379
```

**Prometheus запросы:**

```promql
# Memory usage
redis_memory_used_bytes

# Connections
redis_connected_clients

# Commands per second
rate(redis_commands_processed_total[1m])

# Hit rate
rate(redis_keyspace_hits_total[1m]) / 
(rate(redis_keyspace_hits_total[1m]) + rate(redis_keyspace_misses_total[1m]))
```

---

## 5. Alerting

### 5.1 AlertManager Configuration

```yaml
# alertmanager.yml
groups:
  - name: catvrf_performance
    interval: 30s
    rules:
      - alert: HighTTFB
        expr: |
          histogram_quantile(0.95, 
            sum(rate(http_request_duration_seconds_bucket[5m])) by (le)
          ) > 0.15
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High TTFB detected"
          description: "95th percentile TTFB is {{ $value }}s (> 150ms)"

      - alert: HighErrorRate
        expr: |
          sum(rate(http_requests_total{status=~"5.."}[5m])) / 
          sum(rate(http_requests_total[5m])) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High error rate detected"
          description: "Error rate is {{ $value | humanizePercentage }} (> 5%)"

      - alert: RedisDown
        expr: redis_up == 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "Redis is down"
          description: "Redis instance {{ $labels.instance }} is down"

      - alert: RedisMemoryHigh
        expr: redis_memory_used_bytes / redis_memory_max_bytes > 0.9
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Redis memory usage high"
          description: "Redis memory usage is {{ $value | humanizePercentage }}"
```

### 5.2 Telegram Alerts

```yaml
# alertmanager.yml с Telegram webhook
receivers:
  - name: 'telegram-alerts'
    telegram_configs:
      - send_resolved: true
        api_url: https://api.telegram.org
        bot_token: YOUR_BOT_TOKEN
        chat_id: YOUR_CHAT_ID
        parse_mode: 'HTML'
```

---

## 6. Деплоймент с Blue-Green

### 6.1 Обновленный deployment script

Скрипт `scripts/deploy-blue-green.sh` уже обновлен с cache командами:

```bash
# Очистка и кэширование
php artisan optimize:clear
php artisan optimize
php artisan route:cache
php artisan config:cache
php artisan view:cache
php artisan event:cache
```

### 6.2 Zero-downtime reload с Octane

```bash
# Graceful reload (без прерывания запросов)
php artisan octane:reload

# Или через сигнал
kill -USR2 $(pgrep -f "php artisan octane")
```

---

## 7. Production Checklist

Перед деплоем в production убедитесь:

- [ ] Redis установлен и настроен с persistence (AOF)
- [ ] RoadRunner установлен и `.rr.yaml` сконфигурирован
- [ ] Environment variables установлены (OCTANE_SERVER=roadrunner, CACHE_DRIVER=redis)
- [ ] Composite indexes применены (`php artisan migrate`)
- [ ] Performance тесты пройдены (`./vendor/bin/pest tests/Performance/PerformanceBenchmarkTest.php`)
- [ ] Prometheus/Grafana настроены и дашборды созданы
- [ ] AlertManager настроен с правилами для TTFB и Redis
- [ ] Octane запущен через systemd/supervisord
- [ ] Nginx настроен как reverse proxy на Octane (port 8000)
- [ ] SSL сертификат установлен (Let's Encrypt или wildcard)
- [ ] Backup strategy настроена (PostgreSQL + Redis RDB)

---

## 8. Troubleshooting

### 8.1 Высокий TTFB

**Диагностика:**
```bash
# Проверить Octane workers
curl http://localhost:2112/metrics | grep rr_worker

# Проверить Redis latency
redis-cli --latency

# Проверить slow queries
redis-cli slowlog get 10
```

**Решения:**
- Увеличить количество Octane workers
- Настроить Redis persistence в AOF
- Добавить missing composite indexes
- Включить OPcache и JIT

### 8.2 Redis Out of Memory

**Диагностика:**
```bash
redis-cli info memory
```

**Решения:**
```conf
# redis.conf
maxmemory 8gb
maxmemory-policy allkeys-lru
```

### 8.3 Octane Workers Exhausted

**Диагностика:**
```bash
# Проверить worker pool
curl http://localhost:2112/metrics | grep rr_worker_pool
```

**Решения:**
```yaml
# .rr.yaml
php:
  workers:
    pool:
      num_workers: 16  # Увеличить
      max_jobs: 2000  # Увеличить
```

---

## 9. Полезные команды

```bash
# Octane
php artisan octane:start --server=roadrunner
php artisan octane:reload
php artisan octane:status

# Redis
redis-cli monitor
redis-cli info
redis-cli slowlog get 10

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Database
php artisan migrate
php artisan db:seed

# Performance тесты
./vendor/bin/pest tests/Performance/PerformanceBenchmarkTest.php
```

---

## 10. Контакты и поддержка

- **Architecture Team:** architecture@catvrf.ru
- **DevOps Team:** devops@catvrf.ru
- **Security Team:** security@catvrf.ru
- **On-call:** +7 (XXX) XXX-XX-XX (24/7)

---

**Документ обновлен:** 16.04.2026  
**Следующий обзор:** 16.07.2026
