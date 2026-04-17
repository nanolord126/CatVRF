# Security and Performance Testing Guide

## Обзор

Этот документ описывает комплексный набор тестов для проверки безопасности и производительности CatVRF, включая нагрузочные, краш, фрауд, финансовые, DDOS, стресс тесты для Sports и Medical вертикалей.

## Структура тестов

### 1. K6 Нагрузочные тесты (Performance & Load Testing)

Расположение: `k6/`

#### Sports Vertical Tests
- **crash-test-sports.js** - Краш-тесты для Sports вертикали
  - Тестирование malformed payloads
  - Тестирование null values
  - Тестирование extremely large payloads
  - Тестирование concurrent updates
  - Тестирование SQL injection attempts
  - Тестирование XSS в данных

- **ddos-test-sports.js** - DDOS атаки на Sports вертикаль
  - Симуляция 1000+ одновременных пользователей
  - Атака на bookings endpoint
  - Атака на facilities endpoint
  - Атака на availability check endpoint
  - Мониторинг response time и error rate

- **fraud-test-sports.js** - Фрауд-детекция для Sports
  - Rapid booking attempts
  - Fake facility bookings
  - Suspicious time bookings
  - Unrealistic participants
  - Double booking attempts
  - Bot user agent detection
  - Invalid payment methods

#### Medical Vertical Tests
- **crash-test-medical.js** - Краш-тесты для Medical вертикали
  - Тестирование malformed appointment payloads
  - Тестирование null values
  - Тестирование extremely large symptoms field
  - Тестирование concurrent appointment updates
  - Тестирование XSS в symptoms
  - Тестирование SQL injection attempts

- **ddos-test-medical.js** - DDOS атаки на Medical вертикаль
  - Симуляция 1000+ одновременных пользователей
  - Атака на appointments endpoint
  - Атака на doctors endpoint
  - Атака на availability check endpoint
  - Атака на diagnostic AI endpoint
  - Мониторинг response time и error rate

- **fraud-test-medical.js** - Фрауд-детекция для Medical
  - Rapid appointment attempts
  - Fake doctor appointments
  - PII detection в symptoms
  - Emergency abuse detection
  - Double booking attempts
  - Suspicious appointment types
  - XSS в symptoms
  - Medical record manipulation
  - Insurance fraud patterns

### 2. PHP E2E Тесты (End-to-End Security Testing)

Расположение: `tests/E2E/`

- **SportsFraudDetectionE2ETest.php** - E2E тесты фрауд-детекции Sports
  - Detect rapid booking attempts
  - Detect fake facility booking
  - Detect suspicious time booking
  - Detect unrealistic participants
  - Detect double booking attempt
  - Detect bot user agent
  - Detect suspicious IP
  - Detect invalid payment method
  - Detect booking cancellation abuse
  - Fraud score increases with suspicious activity

- **MedicalFraudDetectionE2ETest.php** - E2E тесты фрауд-детекции Medical
  - Detect rapid appointment attempts
  - Detect fake doctor appointment
  - Detect PII в symptoms (compliance)
  - Detect emergency abuse
  - Detect double booking attempt
  - Detect suspicious appointment type
  - Detect XSS в symptoms
  - Detect medical record manipulation
  - Detect insurance fraud pattern
  - Health score anonymization

- **PaymentAttackSimulationE2ETest.php** - Симуляция атак на платежную систему
  - Card stolen attack
  - Amount manipulation attack
  - Refund attack
  - Currency manipulation attack
  - Concurrent payment attack
  - Webhook spoofing attack
  - Idempotency attack
  - Negative amount attack
  - Zero amount attack
  - Extremely large amount attack
  - Payment timing attack
  - Session hijacking attack
  - SQL injection в payment description

- **FinancialIntegrityE2ETest.php** - Тесты финансовой целостности
  - Wallet balance integrity
  - Prevent negative balance
  - Transaction atomicity
  - Payment transaction audit trail
  - Cross-tenant isolation
  - Duplicate payment prevention
  - Payment reconciliation
  - Currency conversion integrity
  - Fee calculation integrity
  - Rollback on payment failure
  - Concurrent refund prevention

### 3. Chaos Engineering Tests

Расположение: `tests/Chaos/`

- **SportsChaosTest.php** - Chaos Engineering для Sports
  - System works when Redis is down
  - Fraud detection fallback when unavailable
  - Database slow query timeout
  - Circuit breaker on repeated failures
  - Concurrent booking conflict handling
  - Graceful degradation when DB connection exhausted
  - Partial network failure recovery
  - Slot hold timeout recovery
  - Booking cancellation during payment
  - Bulk operation failure rollback
  - Cache invalidation consistency
  - Deadlock recovery

- **MedicalChaosTest.php** - Chaos Engineering для Medical
  - System works when Redis is down
  - AI diagnostic fallback when unavailable
  - Database slow query timeout
  - Circuit breaker on repeated failures
  - Concurrent appointment conflict handling
  - PII anonymization fallback
  - Emergency flow when notification fails
  - Medical record consistency on failure
  - Health score calculation fallback
  - Concurrent diagnostic requests
  - Deadlock recovery
  - Appointment cancellation during payment
  - Bulk appointment failure rollback

### 4. Filament UI Security Tests

Расположение: `tests/Feature/Filament/`

- **FilamentSecurityTest.php** - Тесты безопасности админ-панели
  - Unauthorized access to admin panel
  - Authorized access to admin panel
  - XSS prevention in forms
  - SQL injection prevention
  - CSRF protection
  - Data exposure prevention
  - Cross-tenant isolation
  - Rate limiting on admin endpoints
  - Sensitive data not exposed in API
  - Bulk action authorization
  - File upload security
  - Export functionality security
  - Audit trail logging
  - Session timeout
  - Permission inheritance
  - API key security
  - Mass assignment prevention

## Запуск тестов

### Предварительные требования

```bash
# Установить k6 (для нагрузочных тестов)
# macOS
brew install k6

# Linux
sudo apt-get install k6

# Windows
# Скачать с https://k6.io/
```

### Запуск K6 тестов

#### Sports Vertical

```bash
# Краш-тесты
k6 run k6/crash-test-sports.js

# DDOS тесты
k6 run k6/ddos-test-sports.js

# Фрауд тесты
k6 run k6/fraud-test-sports.js

# С переменными окружения
BASE_URL=https://api.catvrf.com TOKEN=your_token k6 run k6/crash-test-sports.js
```

#### Medical Vertical

```bash
# Краш-тесты
k6 run k6/crash-test-medical.js

# DDOS тесты
k6 run k6/ddos-test-medical.js

# Фрауд тесты
k6 run k6/fraud-test-medical.js

# С переменными окружения
BASE_URL=https://api.catvrf.com TOKEN=your_token k6 run k6/crash-test-medical.js
```

### Запуск PHP E2E тестов

```bash
# Все E2E тесты
php artisan test --testsuite=E2E

# Специфичные тесты
php artisan test tests/E2E/SportsFraudDetectionE2ETest.php
php artisan test tests/E2E/MedicalFraudDetectionE2ETest.php
php artisan test tests/E2E/PaymentAttackSimulationE2ETest.php
php artisan test tests/E2E/FinancialIntegrityE2ETest.php
```

### Запуск Chaos Engineering тестов

```bash
# Все Chaos тесты
php artisan test tests/Chaos/

# Специфичные тесты
php artisan test tests/Chaos/SportsChaosTest.php
php artisan test tests/Chaos/MedicalChaosTest.php
```

### Запуск Filament Security тестов

```bash
# Filament security тесты
php artisan test tests/Feature/Filament/FilamentSecurityTest.php
```

### Запуск всех тестов безопасности

```bash
# Все security и performance тесты
php artisan test --filter="Fraud|Chaos|Security|Integrity|Payment"
```

## CI/CD Интеграция

Добавить в `.github/workflows/ci-cd.yml`:

```yaml
name: Security and Performance Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  security-tests:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
      redis:
        image: redis:alpine
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, pdo_mysql, redis
          
      - name: Install dependencies
        run: composer install --no-progress --no-interaction
        
      - name: Run migrations
        run: php artisan migrate --seed
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          
      - name: Run E2E Security Tests
        run: php artisan test tests/E2E/
        
      - name: Run Chaos Engineering Tests
        run: php artisan test tests/Chaos/
        
      - name: Run Filament Security Tests
        run: php artisan test tests/Feature/Filament/FilamentSecurityTest.php

  performance-tests:
    runs-on: ubuntu-latest
    needs: security-tests
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup k6
        run: |
          sudo gpg -k
          sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
          echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
          sudo apt-get update
          sudo apt-get install k6
          
      - name: Run Sports Performance Tests
        run: |
          k6 run k6/crash-test-sports.js
          k6 run k6/ddos-test-sports.js
          k6 run k6/fraud-test-sports.js
          
      - name: Run Medical Performance Tests
        run: |
          k6 run k6/crash-test-medical.js
          k6 run k6/ddos-test-medical.js
          k6 run k6/fraud-test-medical.js
```

## Метрики и пороги

### K6 Тесты

Пороги заданы в каждом тесте:
- **p(95) response time**: < 2000ms для обычных нагрузок, < 3000ms для DDOS
- **Error rate**: < 10% для краш-тестов, < 50% для DDOS тестов
- **Fraud detection rate**: > 80% для фрауд тестов

### PHP Тесты

Все тесты должны проходить без ошибок. Критические проверки:
- Фрауд-детекция должна блокировать подозрительные операции
- Система должна работать при падении Redis (fallback)
- Circuit breaker должен срабатывать при повторных ошибках
- PII должен быть анонимизирован перед отправкой во внешние сервисы
- Финансовые операции должны быть атомарными

## Мониторинг в продакшене

### Рекомендуемые метрики для наблюдения

1. **Performance Metrics**
   - Response time (p50, p95, p99)
   - Throughput (RPS)
   - Error rate
   - Queue depth

2. **Security Metrics**
   - Fraud score distribution
   - Blocked attempts count
   - Rate limit hits
   - Failed authentication attempts

3. **Chaos Metrics**
   - Circuit breaker state
   - Fallback service usage
   - Retry attempts
   - Deadlock occurrences

4. **Business Metrics**
   - Successful bookings/appointments
   - Payment success rate
   - Refund rate
   - User complaints

## Best Practices

1. **Запуск тестов**
   - Запускать нагрузочные тесты в staging среде
   - Не запускать DDOS тесты против production
   - Использовать тестовые данные, не production PII

2. **Анализ результатов**
   - Сравнивать результаты с предыдущими запусками
   - Ищать регрессии в производительности
   - Анализировать фрауд-паттерны

3. **Непрерывное улучшение**
   - Обновлять тесты при добавлении новых фич
   - Добавлять новые фрауд-паттерны по мере обнаружения
   - Регулярно пересматривать пороги

## Troubleshooting

### K6 тесты не запускаются

```bash
# Проверить установку k6
k6 version

# Проверить переменные окружения
echo $BASE_URL
echo $TOKEN

# Запустить с verbose выводом
k6 run --verbose k6/crash-test-sports.js
```

### PHP тесты падают

```bash
# Очистить кэш
php artisan cache:clear
php artisan config:clear

# Пересоздать базу
php artisan migrate:fresh --seed

# Запустить с verbose выводом
php artisan test --verbose tests/E2E/SportsFraudDetectionE2ETest.php
```

### Redis недоступен в тестах

```bash
# Запустить Redis
redis-server

# Или использовать тестовый double в тестах
# (уже реализовано в Chaos тестах)
```

## Дополнительные ресурсы

- [K6 Documentation](https://k6.io/docs/)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [OWASP Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
- [Chaos Engineering Principles](https://principlesofchaos.org/)
