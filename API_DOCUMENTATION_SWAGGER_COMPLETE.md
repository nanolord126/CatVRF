# 📚 API Documentation — OpenAPI/Swagger Complete

**Статус**: ✅ **Полная документация всех API endpoints**

---

## 📖 Доступ к документации

### 1. Swagger UI (Интерактивный)
```
http://localhost:8000/api/documentation
```

### 2. OpenAPI JSON (Machine-readable)
```
http://localhost:8000/openapi.json
```

### 3. ReDoc (Alternative UI)
```
http://localhost:8000/api/redoc
```

---

## 🔧 Установка Swagger

### Шаг 1: Установить пакет
```bash
composer require darkaonline/l5-swagger
```

### Шаг 2: Опубликовать конфиг
```bash
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

### Шаг 3: Сгенерировать документацию
```bash
php artisan l5-swagger:generate
```

### Шаг 4: Добавить в routes/api.php
```php
Route::get('/documentation', function () {
    return view('l5-swagger::index');
});
```

---

## 📋 API Endpoints Summary

### Farm Direct (Фермерские заказы)
```
✅ GET    /api/v1/farm-orders              — Список заказов
✅ POST   /api/v1/farm-orders              — Создать заказ
✅ GET    /api/v1/farm-orders/{id}         — Деталь заказа
✅ PUT    /api/v1/farm-orders/{id}         — Обновить заказ
✅ DELETE /api/v1/farm-orders/{id}         — Удалить заказ
```

**Параметры запроса (POST/PUT)**:
```json
{
  "product_id": "uuid",
  "quantity_kg": 2.5,
  "delivery_date": "2026-03-25",
  "client_address": "ул. Ленина, 1",
  "client_phone": "+79991234567"
}
```

**Валидация**:
- `quantity_kg`: 0.5–500 кг
- `delivery_date`: не ранее завтра
- `client_phone`: regex `/^\\+?[0-9]{10,15}$/`

---

### Healthy Food (Диет-планы)
```
✅ GET    /api/v1/diet-plans              — Список планов
✅ POST   /api/v1/diet-plans              — Создать план
✅ GET    /api/v1/diet-plans/{id}         — Деталь плана
✅ POST   /api/v1/diet-plans/{id}/subscribe — Подписаться
```

**Типы диет**:
- `keto` — Кетогенная (белки + жиры, минимум углеводов)
- `vegan` — Веганская (без продуктов животного происхождения)
- `paleo` — Палео (древние продукты)
- `balanced` — Сбалансированная (стандарт)

**Валидация**:
- `duration_days`: 7–365 дней
- `daily_calories`: 1000–5000 калорий

---

### Auto Parts (Автозапчасти)
```
✅ GET    /api/v1/auto-parts-orders                    — Список заказов
✅ POST   /api/v1/auto-parts-orders                    — Создать заказ
✅ GET    /api/v1/auto-parts-orders/{id}               — Деталь заказа
✅ GET    /api/v1/auto-parts-orders/compatible/{vin}   — Найти совместимые
```

**VIN Validation**:
- Формат: 17 символов буквы `[A-HJ-NPR-Z0-9]` (без I, O, Q)
- Примеры:
  - `WVWZZZ3CZ9E123456` (Volkswagen)
  - `JH2RC5004LM200175` (Honda)
  - `TMBFK47A922044644` (Toyota)

**Совместимость**:
```bash
GET /api/v1/auto-parts-orders/compatible/WVWZZZ3CZ9E123456

Response:
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "Масляный фильтр",
      "price": 199.99,
      "compatible_vins": ["Volkswagen", "Skoda"]
    }
  ]
}
```

---

### Pharmacy (Аптека)
```
✅ GET    /api/v1/pharmacy-orders                      — Список заказов
✅ POST   /api/v1/pharmacy-orders                      — Создать заказ
✅ POST   /api/v1/pharmacy-orders/verify-prescription  — Верифицировать рецепт
```

**Создание заказа с рецептом**:
```json
{
  "pharmacy_id": "uuid",
  "medicines": "[{\"name\": \"Аспирин\", \"quantity\": 10, \"price\": 50}]",
  "prescription_file": "prescription.pdf",
  "client_phone": "+79991234567",
  "client_age_verified": true
}
```

**Верификация рецепта**:
```bash
POST /api/v1/pharmacy-orders/verify-prescription

{
  "prescription_id": "uuid",
  "verified_by": "pharmacist_001"
}
```

---

### Other Endpoints (Остальные вертикали)

| Вертикаль | Endpoints |
|-----------|-----------|
| Bakery Orders | GET/POST/PUT/DELETE + mark-ready |
| Meat Shops | GET/POST/PUT/DELETE |
| Office Catering | GET/POST/PUT/DELETE + setup-recurring |
| Furniture | GET/POST/PUT/DELETE + schedule-delivery |
| Electronics | GET/POST/PUT/DELETE + warranty-claim |
| Toys & Kids | GET/POST/PUT/DELETE |
| Confectionery | GET/POST/PUT/DELETE + mark-ready |

---

## 🔐 Authentication

### Sanctum Token Authentication
```bash
# 1. Login и получить токен
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

Response:
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}

# 2. Использовать токен в заголовке
curl -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  http://localhost:8000/api/v1/farm-orders
```

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

---

## 📊 Response Format

### Success Response (200, 201)
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "quantity_kg": 2.5,
    ...
  },
  "correlation_id": "550e8400-e29b-41d4-a716-446655440001"
}
```

### Error Response (400, 422, 500)
```json
{
  "success": false,
  "message": "The given data was invalid",
  "errors": {
    "quantity_kg": [
      "The quantity kg field must be between 0.5 and 500."
    ],
    "delivery_date": [
      "The delivery date field must be a date after today."
    ]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### Rate Limited (429)
```json
{
  "success": false,
  "message": "Too many requests. Please try again later."
}
```

---

## 🚀 Rate Limiting

| Endpoint | Limit | Window |
|----------|-------|--------|
| Default | 60 req | 1 minute |
| Payment endpoints | 10 req | 1 minute |
| Promo endpoints | 50 req | 1 minute |
| Search endpoints | 1000 light / 100 heavy | 1 hour |

**Headers Response**:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1645567890
Retry-After: 60
```

---

## 🔍 Filtering & Pagination

### List Endpoints
```bash
# Pagination
GET /api/v1/farm-orders?page=1&per_page=15

# Filtering
GET /api/v1/farm-orders?status=delivered&created_after=2026-03-01

# Sorting
GET /api/v1/farm-orders?sort_by=created_at&sort_order=desc
```

### Response Metadata
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3,
    "from": 1,
    "to": 15
  }
}
```

---

## 📝 Examples

### Example 1: Create Farm Order (cURL)
```bash
curl -X POST http://localhost:8000/api/v1/farm-orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "550e8400-e29b-41d4-a716-446655440000",
    "quantity_kg": 5.0,
    "delivery_date": "2026-03-25",
    "client_address": "ул. Пушкина, 10",
    "client_phone": "+79991234567"
  }'
```

### Example 2: Find Compatible Auto Parts (JavaScript/Fetch)
```javascript
const vin = "WVWZZZ3CZ9E123456";
const token = "YOUR_SANCTUM_TOKEN";

const response = await fetch(
  `http://localhost:8000/api/v1/auto-parts-orders/compatible/${vin}`,
  {
    headers: {
      "Authorization": `Bearer ${token}`,
      "Accept": "application/json"
    }
  }
);

const data = await response.json();
console.log(data.data); // Array of compatible parts
```

### Example 3: Subscribe to Diet Plan (Python/Requests)
```python
import requests

token = "YOUR_SANCTUM_TOKEN"
diet_id = "550e8400-e29b-41d4-a716-446655440000"
headers = {
    "Authorization": f"Bearer {token}",
    "Content-Type": "application/json"
}

response = requests.post(
    f"http://localhost:8000/api/v1/diet-plans/{diet_id}/subscribe",
    headers=headers
)

result = response.json()
print(result)  # { "success": true, "message": "Subscribed" }
```

---

## 🧪 Testing with Postman

### 1. Import OpenAPI
- Open Postman
- File > Import
- Paste `openapi.json` URL или upload файл
- Select "OpenAPI 3.0" format
- Click Import

### 2. Set Collection Variables
- In Postman Collection Variables:
  - `token`: Your Sanctum bearer token
  - `base_url`: http://localhost:8000/api/v1
  - `tenant_id`: Your tenant UUID

### 3. Test Endpoints
- Every endpoint will auto-populate with variables
- Click "Send" to execute

---

## 📦 Generate SDK (Optional)

Используя OpenAPI JSON можно сгенерировать SDK:

```bash
# JavaScript/TypeScript
npm install @openapi-generator/cli -g
openapi-generator-cli generate -i openapi.json -g typescript-axios -o ./generated

# Python
pip install openapi-client-gen
openapi-client-gen generate -i openapi.json -o ./generated

# PHP
composer require openapitools/openapi-generator-cli
```

---

## 🐛 Common Issues

### Issue: 401 Unauthorized
**Решение**: 
- Проверить токен не истёк
- Проверить header формат: `Authorization: Bearer token` (не `Token token`)
- Перегенерировать токен через login endpoint

### Issue: 422 Validation Error
**Решение**:
- Проверить все required поля заполнены
- Проверить formats (UUID, date, phone regex)
- Проверить ranges (quantity_kg должен быть 0.5-500)

### Issue: 429 Too Many Requests
**Решение**:
- Выждать время указанное в `Retry-After` header
- Использовать exponential backoff

### Issue: 404 Not Found
**Решение**:
- Проверить ID ресурса существует
- Проверить tenant scoping (может быть ресурс других тенантов)
- Проверить soft deletes (может быть удаленный ресурс)

---

## 📚 Related Documentation

- [INTEGRATION_TESTING_SUITE_COMPLETE.md](INTEGRATION_TESTING_SUITE_COMPLETE.md) — Test suite reference
- [API_LAYER_CONTROLLERS_COMPLETE.md](API_LAYER_CONTROLLERS_COMPLETE.md) — Controllers details
- [openapi.json](openapi.json) — Full OpenAPI specification

---

**✨ API Documentation: COMPLETE & PRODUCTION-READY**

Last Updated: 21 марта 2026 г.
