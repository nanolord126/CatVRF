# 🚀 CatVRF Marketplace API - Полная документация

**Версия:** 1.0.0  
**Дата:** 24 марта 2026  
**Статус:** Production Ready ✅

---

## 📋 Оглавление

1. [Введение](#введение)
2. [Аутентификация](#аутентификация)
3. [API Endpoints](#api-endpoints)
4. [Примеры использования](#примеры-использования)
5. [Обработка ошибок](#обработка-ошибок)
6. [Лимитирование запросов](#лимитирование-запросов)
7. [Webhook интеграция](#webhook-интеграция)

---

## Введение

### Базовый URL

```
Production: https://api.catvrf.ru
Staging:    https://staging.catvrf.ru
```

### Формат ответа

Все ответы возвращаются в формате JSON:

```json
{
  "success": true,
  "data": {...},
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "timestamp": "2026-03-24T10:30:00Z"
}
```

### Headers обязательные

```
Content-Type: application/json
Authorization: Bearer {token}
X-Correlation-ID: {uuid}  // опционально, генерируется если не отправлен
```

---

## Аутентификация

### Bearer Token (OAuth 2.0)

```bash
curl -X POST https://api.catvrf.ru/oauth/token \
  -d "grant_type=personal_access_token" \
  -d "client_id={client_id}" \
  -d "client_secret={client_secret}" \
  -d "username={email}" \
  -d "password={password}"
```

**Ответ:**

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 31536000,
  "refresh_token": "def50200..."
}
```

### API Key (для серверного взаимодействия)

```bash
curl -X GET https://api.catvrf.ru/api/v1/products \
  -H "X-API-Key: {api_key}"
```

---

## API Endpoints

### 🏠 Health & Status

#### GET /api/health

Проверка статуса API.

**Параметры:** Нет

**Ответ:**

```json
{
  "status": "ok",
  "timestamp": "2026-03-24T10:30:00Z",
  "database": "connected",
  "redis": "connected",
  "elasticsearch": "connected"
}
```

---

### 💳 Payments API

#### POST /api/v1/payments/init

Инициировать новый платёж.

**Параметры:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|---------|
| amount | integer | ✓ | Сумма в копейках (мин 10000 = 100 ₽) |
| currency | string | ✓ | Валюта (RUB) |
| description | string | ✓ | Описание платежа |
| order_id | integer | ✓ | ID заказа |
| idempotency_key | string | ✓ | Уникальный ключ для идемпотентности |

**Пример запроса:**

```bash
curl -X POST https://api.catvrf.ru/api/v1/payments/init \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 123,
    "amount": 50000,
    "currency": "RUB",
    "description": "Оплата заказа #123",
    "idempotency_key": "order-123-2026-03-24-001"
  }'
```

**Ответ (201 Created):**

```json
{
  "success": true,
  "data": {
    "payment_id": "pymt_550e8400",
    "status": "pending",
    "amount": 50000,
    "currency": "RUB",
    "created_at": "2026-03-24T10:30:00Z",
    "expires_at": "2026-03-24T11:30:00Z",
    "payment_url": "https://checkout.catvrf.ru/pymt_550e8400"
  },
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Коды ошибок:**

- 409: Платёж с таким idempotency_key уже обработан
- 429: Превышен лимит платежей (max 10/мин)
- 422: Ошибка валидации

---

#### GET /api/v1/payments/{payment_id}

Получить статус платежа.

**Пример запроса:**

```bash
curl -X GET https://api.catvrf.ru/api/v1/payments/pymt_550e8400 \
  -H "Authorization: Bearer {token}"
```

**Ответ:**

```json
{
  "success": true,
  "data": {
    "id": "pymt_550e8400",
    "status": "captured",
    "amount": 50000,
    "captured_at": "2026-03-24T10:35:00Z"
  }
}
```

---

#### POST /api/v1/payments/{payment_id}/refund

Возврат платежа.

**Параметры:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|---------|
| amount | integer | - | Сумма возврата (если не указана - полный возврат) |
| reason | string | ✓ | Причина возврата |

**Пример запроса:**

```bash
curl -X POST https://api.catvrf.ru/api/v1/payments/pymt_550e8400/refund \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Заказ отменен по просьбе клиента"
  }'
```

---

### 💰 Wallet API

#### GET /api/v1/wallets

Получить текущий баланс кошелька.

**Ответ:**

```json
{
  "success": true,
  "data": {
    "id": "wlt_550e8400",
    "current_balance": 150000,
    "hold_amount": 25000,
    "available_balance": 125000,
    "currency": "RUB"
  }
}
```

---

#### POST /api/v1/wallets/{wallet_id}/deposit

Пополнить баланс.

**Параметры:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|---------|
| amount | integer | ✓ | Сумма в копейках |
| source_type | string | ✓ | Источник (payment, promo, referral, bonus) |

**Пример запроса:**

```bash
curl -X POST https://api.catvrf.ru/api/v1/wallets/wlt_550e8400/deposit \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100000,
    "source_type": "payment"
  }'
```

---

#### POST /api/v1/wallets/{wallet_id}/withdraw

Вывести средства.

**Параметры:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|---------|
| amount | integer | ✓ | Сумма в копейках |
| card_number | string | ✓ | Номер карты (последние 4 цифры) |

---

### 🎟️ Promo API

#### POST /api/v1/promos/apply

Применить промо-код.

**Параметры:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|---------|
| code | string | ✓ | Промо-код (до 50 символов) |
| amount | integer | ✓ | Сумма заказа в копейках |

**Пример запроса:**

```bash
curl -X POST https://api.catvrf.ru/api/v1/promos/apply \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "SUMMER2026",
    "amount": 50000
  }'
```

**Ответ:**

```json
{
  "success": true,
  "data": {
    "code": "SUMMER2026",
    "discount_type": "percent",
    "discount_value": 10,
    "discount_amount": 5000,
    "final_amount": 45000
  }
}
```

---

### 🔍 Search API

#### GET /api/v1/search

Глобальный поиск по всем вертикалям.

**Параметры:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|---------|
| q | string | ✓ | Поисковый запрос (мин 2 символа) |
| vertical | string | - | Фильтр по вертикали (beauty, food, hotels, auto, ...) |
| category | string | - | Фильтр по категории |
| min_price | integer | - | Минимальная цена |
| max_price | integer | - | Максимальная цена |
| geo | string | - | Географический фильтр (lat,lng,radius_km) |
| sort | string | - | Сортировка (relevance, rating, price_asc, price_desc, newest) |
| page | integer | - | Страница результатов (по умолчанию 1) |
| per_page | integer | - | Результатов на странице (макс 100, по умолчанию 20) |

**Пример запроса:**

```bash
curl -X GET "https://api.catvrf.ru/api/v1/search?q=салон+красоты&vertical=beauty&sort=rating&page=1" \
  -H "Authorization: Bearer {token}"
```

**Ответ:**

```json
{
  "success": true,
  "data": {
    "total": 152,
    "per_page": 20,
    "current_page": 1,
    "results": [
      {
        "id": "salon_550e8400",
        "vertical": "beauty",
        "type": "salon",
        "title": "Салон красоты 'Золотая линия'",
        "rating": 4.8,
        "review_count": 245,
        "image": "https://...",
        "location": {
          "lat": 55.7558,
          "lng": 37.6173,
          "address": "Москва, улица Пушкина, д. 10"
        }
      }
    ]
  }
}
```

---

#### GET /api/v1/search/suggestions

Автодополнение поиска.

**Параметры:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|---------|
| q | string | ✓ | Частичный запрос (мин 1 символ) |
| vertical | string | - | Фильтр по вертикали |
| limit | integer | - | Максимум подсказок (макс 10) |

---

## 📊 B2B API

Отдельный API для бизнес-партнёров.

### Аутентификация B2B

```bash
curl -X POST https://api.catvrf.ru/oauth/token \
  -d "grant_type=client_credentials" \
  -d "client_id={b2b_client_id}" \
  -d "client_secret={b2b_client_secret}" \
  -d "scope=b2b"
```

### Основные endpoints

```
GET    /api/v1/b2b/dashboard                 // Дашборд
GET    /api/v1/b2b/orders                    // Список заказов
POST   /api/v1/b2b/orders/{id}/confirm       // Подтверждение заказа
POST   /api/v1/b2b/orders/{id}/ship          // Отправка заказа
GET    /api/v1/b2b/inventory                 // Управление складом
POST   /api/v1/b2b/inventory/{id}/reserve    // Резервирование товара
GET    /api/v1/b2b/payouts                   // История выплат
POST   /api/v1/b2b/products/import           // Импорт товаров (CSV)
GET    /api/v1/b2b/analytics/revenue         // Аналитика по доходам
```

---

## Примеры использования

### PHP (Laravel)

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)
    ->post('https://api.catvrf.ru/api/v1/payments/init', [
        'order_id' => 123,
        'amount' => 50000,
        'currency' => 'RUB',
        'description' => 'Payment',
        'idempotency_key' => 'order-123-001',
    ]);

if ($response->successful()) {
    $payment = $response->json('data');
    // Обработка результата
}
```

### Python

```python
import requests
import uuid

token = "your_access_token"
headers = {
    "Authorization": f"Bearer {token}",
    "X-Correlation-ID": str(uuid.uuid4()),
}

response = requests.post(
    "https://api.catvrf.ru/api/v1/payments/init",
    headers=headers,
    json={
        "order_id": 123,
        "amount": 50000,
        "currency": "RUB",
        "description": "Payment",
        "idempotency_key": f"order-123-{uuid.uuid4()}",
    }
)

print(response.json())
```

### JavaScript (Fetch)

```javascript
const token = "your_access_token";
const correlationId = crypto.randomUUID();

const response = await fetch("https://api.catvrf.ru/api/v1/payments/init", {
  method: "POST",
  headers: {
    "Authorization": `Bearer ${token}`,
    "Content-Type": "application/json",
    "X-Correlation-ID": correlationId,
  },
  body: JSON.stringify({
    order_id: 123,
    amount: 50000,
    currency: "RUB",
    description: "Payment",
    idempotency_key: `order-123-${Date.now()}`,
  }),
});

const data = await response.json();
console.log(data);
```

---

## Обработка ошибок

### Структура ошибки

```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "amount": ["The amount must be at least 10000"],
    "code": ["The code is invalid"]
  },
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Коды ошибок HTTP

| Код | Описание |
|-----|---------|
| 200 | OK - Успешный запрос |
| 201 | Created - Ресурс создан |
| 400 | Bad Request - Ошибка в параметрах |
| 401 | Unauthorized - Требуется аутентификация |
| 403 | Forbidden - Доступ запрещен |
| 404 | Not Found - Ресурс не найден |
| 409 | Conflict - Конфликт (дубликат) |
| 422 | Unprocessable Entity - Ошибка валидации |
| 429 | Too Many Requests - Превышен лимит |
| 500 | Internal Server Error - Ошибка сервера |

---

## Лимитирование запросов

### Rate Limits по типам операций

| Операция | Лимит | Период |
|----------|-------|--------|
| Платежи | 10 | 1 минута |
| Промо-коды | 50 | 1 минута |
| Поиск (лёгкий) | 1000 | 1 час |
| Поиск (тяжёлый) | 100 | 1 час |
| API (общий) | 5000 | 1 час |

### Headers ответов

```
X-RateLimit-Limit: 5000
X-RateLimit-Remaining: 4985
X-RateLimit-Reset: 1711270800
Retry-After: 60
```

Если превышен лимит, получите 429 ответ с заголовком `Retry-After`.

---

## Webhook интеграция

### Регистрация webhook

```bash
curl -X POST https://api.catvrf.ru/api/v1/webhooks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "payment.captured",
    "url": "https://yoursite.com/webhooks/payment",
    "active": true
  }'
```

### Проверка подписи

Все webhooks подписаны HMAC-SHA256:

```php
$signature = $_SERVER['HTTP_X_CATVRF_SIGNATURE'] ?? '';
$body = file_get_contents('php://input');
$expected = hash_hmac('sha256', $body, 'your_webhook_secret');

if (!hash_equals($signature, $expected)) {
    http_response_code(403);
    die('Invalid signature');
}
```

### События webhook

```
payment.initiated       // Платёж инициирован
payment.captured        // Платёж успешно проведён
payment.refunded        // Платёж возвращён
payment.failed          // Платёж не прошёл
order.created           // Заказ создан
order.completed         // Заказ выполнен
order.cancelled         // Заказ отменен
```

---

## Документация

- **OpenAPI Spec:** https://api.catvrf.ru/api/docs/openapi.json
- **Swagger UI:** https://api.catvrf.ru/api/docs/swagger
- **Postman Collection:** https://api.catvrf.ru/api/docs/postman
- **Support:** support@catvrf.ru

---

**Обновлено:** 24 марта 2026 | Версия: 1.0.0
