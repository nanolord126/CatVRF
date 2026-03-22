# Финансовый модуль - API документация

## REST API endpoints

### Платежи

#### Инициирование платежа

```http
POST /api/payments
Content-Type: application/json

{
  "amount": 100.50,
  "order_id": "ORD-12345",
  "user_id": 1,
  "order_type": "course_enrollment",
  "description": "Course: Laravel Mastery",
  "metadata": {
    "course_id": 123,
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
  }
}

Response 201:
{
  "status": "pending",
  "payment_id": "pay_xxxxx",
  "order_id": "ORD-12345",
  "amount": 100.50,
  "payment_url": "https://tinkoff.ru/...",
  "payment_method": "sbp|card",
  "created_at": "2026-03-10T12:00:00Z"
}
```

#### Получить статус платежа

```http
GET /api/payments/{paymentId}
Authorization: Bearer {token}

Response 200:
{
  "id": 1,
  "payment_id": "pay_xxxxx",
  "amount": 100.50,
  "status": "settled",
  "user_id": 1,
  "created_at": "2026-03-10T12:00:00Z",
  "captured_at": "2026-03-10T12:05:00Z"
}
```

#### Возврат платежа (возврат средств)

```http
POST /api/payments/{paymentId}/refund
Content-Type: application/json
Authorization: Bearer {token}

{
  "amount": 100.50,
  "reason": "Customer requested refund",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
}

Response 200:
{
  "status": "refunded",
  "refund_amount": 100.50,
  "refund_id": "ref_xxxxx",
  "processed_at": "2026-03-10T12:10:00Z"
}
```

### Платёжные карты

#### Получить сохранённые карты пользователя

```http
GET /api/wallet/cards
Authorization: Bearer {token}

Response 200:
[
  {
    "id": 1,
    "card_last_four": "1234",
    "card_brand": "VISA",
    "exp_month": 12,
    "exp_year": 2026,
    "is_active": true,
    "is_default": true
  },
  {
    "id": 2,
    "card_last_four": "5678",
    "card_brand": "MASTERCARD",
    "exp_month": 6,
    "exp_year": 2027,
    "is_active": true,
    "is_default": false
  }
]
```

#### Добавить новую карту

```http
POST /api/wallet/cards
Content-Type: application/json
Authorization: Bearer {token}

{
  "card_number": "4111111111111111",
  "exp_month": 12,
  "exp_year": 2026,
  "cvv": "123"
}

Response 201:
{
  "id": 3,
  "card_last_four": "1111",
  "card_brand": "VISA",
  "exp_month": 12,
  "exp_year": 2026,
  "is_active": true,
  "token": "tok_xxxxx"
}
```

#### Удалить карту

```http
DELETE /api/wallet/cards/{cardId}
Authorization: Bearer {token}

Response 204: No Content
```

### Подписки

#### Создать подписку

```http
POST /api/subscriptions
Content-Type: application/json
Authorization: Bearer {token}

{
  "wallet_card_id": 1,
  "amount": 500.00,
  "frequency": "monthly",
  "starts_at": "2026-03-10T00:00:00Z",
  "metadata": {
    "plan_id": 1,
    "plan_name": "Premium"
  }
}

Response 201:
{
  "id": 1,
  "user_id": 1,
  "wallet_card_id": 1,
  "amount": 500.00,
  "frequency": "monthly",
  "status": "active",
  "starts_at": "2026-03-10T00:00:00Z",
  "ends_at": null,
  "next_payment_at": "2026-04-10T00:00:00Z"
}
```

#### Получить подписки пользователя

```http
GET /api/subscriptions
Authorization: Bearer {token}

Response 200:
[
  {
    "id": 1,
    "amount": 500.00,
    "frequency": "monthly",
    "status": "active",
    "next_payment_at": "2026-04-10T00:00:00Z"
  }
]
```

#### Отменить подписку

```http
POST /api/subscriptions/{subscriptionId}/cancel
Content-Type: application/json
Authorization: Bearer {token}

{
  "reason": "Not interested anymore"
}

Response 200:
{
  "id": 1,
  "status": "cancelled",
  "ends_at": "2026-03-10T12:00:00Z"
}
```

### Вебхуки

#### Обработка вебхука платёжной системы

```http
POST /webhooks/sbp
Content-Type: application/json
X-Webhook-Signature: sha256=xxxxx

{
  "PaymentId": "pay_xxxxx",
  "Status": "CONFIRMED",
  "Amount": 10050,
  "OrderId": "ORD-12345",
  "Timestamp": "2026-03-10T12:05:00Z",
  "Signature": "xxxxx"
}

Response 200:
{
  "status": "OK"
}
```

## Коды ошибок

```
400 Bad Request
{
  "message": "Validation error",
  "errors": {
    "amount": ["Amount must be greater than 0"],
    "order_id": ["Order ID is required"]
  }
}

401 Unauthorized
{
  "message": "Unauthenticated"
}

403 Forbidden
{
  "message": "Платёж принадлежит другому тенанту"
}

404 Not Found
{
  "message": "Payment not found"
}

409 Conflict
{
  "message": "Возврат возможен только для успешных платежей"
}

500 Internal Server Error
{
  "message": "Payment processing failed",
  "error_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

## Примеры интеграции

### Python (requests)

```python
import requests

# Инициация платежа
response = requests.post('https://api.example.com/api/payments', 
  headers={'Authorization': 'Bearer token'},
  json={
    'amount': 100.50,
    'order_id': 'ORD-12345',
    'user_id': 1,
    'order_type': 'course_enrollment'
  }
)

payment = response.json()
print(f"Платёж создан: {payment['payment_url']}")
```

### JavaScript (fetch)

```javascript
const createPayment = async (amount, orderId) => {
  const response = await fetch('/api/payments', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      amount,
      order_id: orderId,
      user_id: userId,
      order_type: 'course_enrollment'
    })
  });

  const payment = await response.json();
  window.location.href = payment.payment_url;
};
```

### cURL

```bash
curl -X POST https://api.example.com/api/payments \
  -H "Authorization: Bearer token" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.50,
    "order_id": "ORD-12345",
    "user_id": 1,
    "order_type": "course_enrollment"
  }'
```

## Webhook обработка

### Подпись вебхука

Все вебхуки подписаны с использованием HMAC-SHA256:

```php
$signature = hash_hmac('sha256', $payload, config('payments.webhook_secret'));
```

Проверка:

```php
hash_equals($expectedSignature, $receivedSignature);
```

### Retry policy

Если обработка вебхука вернёт 5xx ошибку, система переправит вебхук:

- 1-я попытка: сразу
- 2-я попытка: +5 минут
- 3-я попытка: +15 минут
- 4-я попытка: +1 час
- 5-я попытка: +3 часа

### Idempotency

Одна и та же платёжная транзакция обрабатывается только один раз, даже если вебхук отправлен несколько раз.

## Rate limiting

- **30 requests/minute** для неавторизованных пользователей
- **100 requests/minute** для авторизованных пользователей
- **1000 requests/minute** для webhook-ов

## Версионирование API

API версия: **v1**

Текущая версия: `/api/v1/...`

Устаревшие версии будут удалены за 90 дней до релиза новой версии.
