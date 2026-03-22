# Security & API Documentation — CatVRF 2026

## Table of Contents

1. [API Authentication](#api-authentication)
2. [Rate Limiting](#rate-limiting)
3. [Webhook Protection](#webhook-protection)
4. [Input Validation](#input-validation)
5. [RBAC & Authorization](#rbac--authorization)
6. [API Versioning](#api-versioning)
7. [Monitoring & Alerts](#monitoring--alerts)
8. [Best Practices](#best-practices)

---

## API Authentication

### Authentication Methods

CatVRF поддерживает два метода аутентификации:

#### 1. Sanctum Tokens (для фронтенда)

```bash
# Получить токен после логина
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

# Ответ
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_at": "2026-03-24T12:00:00Z"
}

# Использование в запросах
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### 2. API Keys (для B2B интеграций)

```bash
# Генерировать API ключ в личном кабинете
POST /api/v1/api-keys
Content-Type: application/json
Authorization: Bearer {sanctum_token}

{
  "name": "External Integration",
  "abilities": ["read", "write", "payments"],
  "expires_at": "2027-03-17"
}

# Ответ
{
  "id": 123,
  "key": "sk_live_xxxxxxxxxxxxxxxxxxxx",
  "key_preview": "sk_live_xxxx",
  "abilities": ["read", "write", "payments"],
  "created_at": "2026-03-17T12:00:00Z"
}

# Использование в запросах
X-API-Key: sk_live_xxxxxxxxxxxxxxxxxxxx
```

### Required Headers

Все API запросы должны содержать:

```
Content-Type: application/json
X-Correlation-ID: {uuid}                    # Уникальный ID для отслеживания
Authorization: Bearer {token} ИЛИ X-API-Key: {key}
```

### Token Security

- Токены автоматически истекают через 24 часа
- API ключи требуют явного обновления или ревокации
- Никогда не сохраняйте токены в локальном хранилище браузера без шифрования
- Используйте HTTPS для всех запросов

---

## Rate Limiting

### Rate Limit Levels

| Операция | Лимит | Окно |
|----------|-------|------|
| Платёж (инициация) | 10 запросов | 1 минута |
| Применение промо | 50 запросов | 1 минута |
| Оплата вишлиста | 20 запросов | 1 минута |
| Поиск (лёгкий) | 1000 запросов | 1 час |
| Поиск (ML) | 100 запросов | 1 час |
| Реферальное заявление | 5 запросов | 1 час |
| Webhook retry | 100 попыток | 1 час |

### Rate Limit Response

Когда лимит превышен:

```json
HTTP/1.1 429 Too Many Requests

{
  "error": "Too many requests",
  "message": "Rate limit exceeded for this endpoint",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
}

Headers:
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1711270260
Retry-After: 60
```

### Burst Protection

Если превышено >3 отказа подряд за 60 секунд:

- Пользователь/тенант временно блокируется на **5 минут**
- Все запросы отклоняются с HTTP 429
- Событие логируется в канал `fraud_alert`

### Best Practices

- Кэшируйте результаты, когда возможно
- Используйте exponential backoff при retry
- Уважайте заголовки `X-RateLimit-*`
- Для ML поисков: используйте async API с polling

---

## Webhook Protection

### Signature Verification

Все webhooks защищены криптографической подписью:

#### Tinkoff

```php
// Проверка выполняется автоматически
$signature = $request->header('X-Signature');
// HMAC-SHA256(payload, secret_key)
```

#### Sber

```php
// Либо HMAC-SHA256, либо сертификат
$signature = $request->header('X-Signature');
// или сертификат-based
```

#### СБП

```php
// IP whitelist + HMAC-SHA256
$clientIp = $request->ip();  // Должен быть в 195.68.0.0/14
$signature = $request->header('X-Signature');
```

### Invalid Signature Response

```json
HTTP/1.1 401 Unauthorized

{
  "error": "Invalid signature",
  "message": "Webhook signature verification failed"
}
```

### Webhook Retry Policy

- Автоматическое пересоздание при ошибке: 5, 15, 60 минут
- Максимум 10 попыток
- Отключение после постоянного отказа

### Testing Webhooks Locally

```bash
# Использовать ngrok для tunneling
ngrok http 8000

# Обновить webhook URL в конфигурации платёжной системы
# Проверить логи в storage/logs/webhooks.log
tail -f storage/logs/webhooks.log
```

---

## Input Validation

### Validation Rules

Все входные данные валидируются на сервере:

```bash
POST /api/v1/payments
Content-Type: application/json
Authorization: Bearer {token}

{
  "amount": 10000,                    # integer, 100-50000000 копеек
  "currency": "RUB",                  # string, in: RUB,USD,EUR
  "description": "Покупка услуги",    # string, 3-255 символов
  "customer_email": "user@test.com",  # email
  "return_url": "https://...",        # valid URL
  "hold": false                       # boolean, optional
}
```

### Validation Errors

```json
HTTP/1.1 422 Unprocessable Entity

{
  "error": "Validation failed",
  "errors": {
    "amount": ["Amount must be at least 100 kopecks"],
    "currency": ["Currency must be one of: RUB, USD, EUR"]
  },
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Common Validations

- **Email**: Must be valid email format
- **URL**: Must be HTTPS (in production)
- **Integer**: Must be positive, within range
- **Enum**: Must be one of predefined values
- **UUID**: Must match UUID format (v4)
- **JSON**: Must be valid JSON if provided

---

## RBAC & Authorization

### User Roles

| Роль | Описание | Доступ |
|------|---------|--------|
| `user` | Обычный пользователь | Покупки, поиск, отзывы |
| `business_owner` | Владелец бизнеса | Управление бизнесом, выплаты, HR |
| `business_admin` | Администратор бизнеса | Все кроме финансов |
| `finance_manager` | Финансовый менеджер | Платежи, выплаты, отчёты |
| `support_admin` | Админ поддержки | Поддержка пользователей, модерация |
| `platform_admin` | Админ платформы | Полный доступ |

### Business Resources Access

**Только для `business_owner` и выше:**

- HR (сотрудники)
- Зарплата
- Выплаты
- Финансовые отчёты

**Заблокировано для обычных пользователей:**

```
GET /api/v1/tenants/{id}/employees
GET /api/v1/tenants/{id}/payroll
POST /api/v1/tenants/{id}/payouts
GET /api/v1/tenants/{id}/financials
```

Результат: **HTTP 403 Forbidden**

### Authorization Checks

```php
// В контроллерах
public function index()
{
    $this->authorize('viewAny', Employee::class);  // Проверит Policy
    
    $employees = Employee::all();
    return $employees;
}

// В Filament Resources
protected static function getPages(): array
{
    return [
        'index' => Pages\ListEmployees::route('/'),
        'create' => Pages\CreateEmployee::route('/create'),
        'edit' => Pages\EditEmployee::route('/{record}/edit'),
    ];
}

public function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('tenant_id', auth()->user()->tenant_id);
}
```

---

## API Versioning

### Version Support

- **v1** (текущая, поддерживаемая):
  - All current endpoints
  - Breaking changes only in major versions

- **v2** (будущая):
  - New endpoints
  - Deprecated v1 endpoints marked in headers

### Using Versioned Endpoints

```bash
# Текущая версия
GET /api/v1/payments

# Будущая версия (когда будет)
GET /api/v2/payments
```

### Version Deprecation Policy

```
Deprecation Timeline:
1. Announce 6 months before deprecation
2. Add X-API-Deprecated header to deprecated endpoints
3. Include sunset date in header
4. Support both old and new for 6 months
5. Disable old version
```

Example header:

```
X-API-Deprecated: true
X-API-Sunset: Sun, 15 Sep 2026 12:00:00 GMT
X-API-Replacement: /api/v2/payments
```

---

## Monitoring & Alerts

### Audit Logging

Все критичные операции логируются в файл `storage/logs/audit.log`:

```
[2026-03-17 12:00:00] audit.INFO: Payment initiated
  correlation_id: 550e8400-e29b-41d4-a716-446655440000
  user_id: 123
  amount: 10000
  tenant_id: 456
```

### Security Alerts

Подозрительная активность логируется в `storage/logs/fraud_alert.log`:

```
[2026-03-17 12:00:00] fraud_alert.WARNING: Rate limit exceeded
  key: rate_limit:payment_init:456:123
  operation: payment_init
  rejection_count: 4
```

### Monitoring Tools

1. **Sentry** (errors & exceptions):
   - Real-time error tracking
   - Performance monitoring
   - Security alerts

2. **Datadog** (metrics):
   - Rate limit violations
   - API latency
   - Failed authentications

3. **Slack** (notifications):
   - Critical security events
   - Rate limit storms
   - Webhook failures

---

## Best Practices

### For API Clients

✅ **DO:**

- Use `X-Correlation-ID` for debugging
- Implement exponential backoff for retries
- Cache responses when possible
- Monitor rate limit headers
- Use HTTPS only
- Rotate API keys regularly

❌ **DON'T:**

- Expose tokens/keys in client-side code
- Retry immediately on rate limit
- Store sensitive data in logs
- Ignore security headers
- Use same key for multiple services

### For Developers

✅ **DO:**

- Check correlation_id in all logs
- Use FormRequest for validation
- Implement RateLimiter checks
- Log all sensitive operations
- Use strong encryption for keys
- Test security with OWASP tools

❌ **DON'T:**

- Skip input validation
- Log sensitive data (passwords, PII)
- Ignore rate limit requirements
- Hardcode secrets
- Use default credentials
- Expose stack traces to clients

### For Webhooks

✅ **DO:**

- Verify signatures on ALL webhooks
- Check IP whitelist
- Implement idempotency handling
- Retry failed webhooks
- Log all webhook activity
- Monitor delivery latency

❌ **DON'T:**

- Trust webhook data without verification
- Process duplicates twice
- Fail on missing optional fields
- Log sensitive webhook content
- Ignore webhook errors silently

---

## Security Contacts

- **Security Team**: <security@catvrf.ru>
- **Report Vulnerabilities**: <https://catvrf.ru/.well-known/security.txt>
- **Bug Bounty Program**: <https://catvrf.ru/bounty>

---

## Change Log

### v1.0 (2026-03-17)

- ✅ API Authentication (Sanctum + API Keys)
- ✅ Rate Limiting (Sliding Window + Burst Protection)
- ✅ Webhook Signature Verification
- ✅ Input Validation (FormRequest)
- ✅ RBAC & Authorization
- ✅ IP Whitelisting
- ✅ Comprehensive Logging

---

**Last Updated**: 2026-03-17
**Status**: Production Ready
