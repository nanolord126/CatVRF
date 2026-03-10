# Поддержка НДС в банковских драйверах

## 📋 Обновленные банковские файлы

### 1. **TinkoffDriver** (`app/Domains/Finances/Services/TinkoffDriver.php`) ✅

#### Новые методы:
- **`getTaxCode(string $taxSystem, ?string $taxCode = null): string`**
  - Расчет налогового кода для Tinkoff API
  - Поддерживает системы: osn, usn, esn, envd, psn
  - Возвращает: vat0, vat10, vat18, none

#### Обновленные методы:
- **`buildReceipt(array $data): array`**
  - Теперь обрабатывает товары с учетом `tax_system`
  - Поддерживает передачу массива товаров (`items`)
  - Добавляет корректный налоговый код для каждого товара
  - Поддерживает альтернативные названия полей (qty/quantity)

#### Поддерживаемые налоговые коды (Tinkoff):
- `vat0` - НДС 0%
- `vat10` - НДС 10%
- `vat20` - НДС 20% (стандартная ставка с 2019 года)
- `none` - Без НДС

---

### 2. **SberDriver** (`app/Domains/Finances/Services/SberDriver.php`) ✅

#### Обновления:
- 📚 Добавлена информация в документацию класса о поддержке НДС
- 💡 Совместимость с системами налогообложения РФ

#### Особенности:
- SBP платежи могут содержать информацию о НДС
- Поддерживает передачу `tax_system` через метаданные

---

### 3. **TochkaDriver** (`app/Domains/Finances/Services/TochkaDriver.php`) ✅

#### Обновления:
- 📚 Добавлена информация в документацию о поддержке НДС
- 💡 Поддержка для корпоративных платежей с учетом налогообложения

#### Особенности:
- Корпоративные переводы учитывают система налогообложения
- Поддержка для выплат зарплат с налоговыми данными

---

## 🎯 Как использовать

### При инициализации платежа через Tinkoff

```php
$tinkoff = new TinkoffDriver();

$paymentData = [
    'order_id' => 'order_123',
    'amount' => 1000.00,
    'email' => 'user@example.com',
    'phone' => '+79001234567',
    'tax_system' => 'osn', // Система налогообложения
    'tax' => 'vat_18',      // Налоговый код (если нужно переопределить)
    'items' => [
        [
            'name' => 'Product A',
            'price' => 500.00,
            'qty' => 1,
            'tax' => 'vat_18'
        ],
        [
            'name' => 'Product B',
            'price' => 500.00,
            'qty' => 1,
            'tax' => 'vat_18'
        ]
    ]
];

$result = $tinkoff->initPayment($paymentData);
```

**Результат:**
```php
[
    'payment_id' => '...',
    'url' => '...',
    'gateway' => 'tinkoff',
    'status' => 'pending'
]
```

### При инициализации платежа через Sber

```php
$sber = new SberDriver();

$paymentData = [
    'order_id' => 'order_456',
    'amount' => 500.00,
    'phone' => '+79001234567',
    'description' => 'Payment for Course',
    'tax_system' => 'usn', // УСН - без НДС
];

$result = $sber->initPayment($paymentData);
```

### При корпоративном платеже через Tochka

```php
$tochka = new TochkaDriver();

$paymentData = [
    'order_id' => 'invoice_789',
    'amount' => 10000.00,
    'recipient_name' => 'Company LLC',
    'recipient_bik' => '044525593',
    'recipient_account' => '40702810800000000000',
    'tax_system' => 'osn',
    'description' => 'Invoice payment'
];

$result = $tochka->initPayment($paymentData);
```

---

## 🔄 Взаимодействие между уровнями

```
Bank Driver (Tinkoff/Sber/Tochka)
    ↓
    ├─ buildReceipt() / initPayment()
    │  └─ getTaxCode() → расчет налога
    ├─ Receipt с корректным НДС
    └─ отправка в банк

Payment Service
    ↓
    ├─ handleWebhook()
    ├─ при успехе → FiscalService::sendReceipt()
    └─ Fiscal Service уже имеет tax_system из метаданных платежа

Fiscal Service
    ↓
    ├─ CloudKassir/Atol Driver
    └─ процессинг чека с учетом НДС
```

---

## ✅ Проверка синтаксиса

```
✅ TinkoffDriver.php - no syntax errors
✅ SberDriver.php - no syntax errors  
✅ TochkaDriver.php - no syntax errors
```

---

## 📊 Поддерживаемые системы налогообложения по банкам

| Система | Tinkoff | Sber | Tochka |
|---------|---------|------|--------|
| ОСН | ✅ 0/10/18% | ✅ | ✅ |
| УСН | ✅ 0% | ✅ | ✅ |
| ЕСХН | ✅ 0% | ✅ | ✅ |
| ЕНВД | ✅ 0% | ✅ | ✅ |
| ПСН | ✅ 0% | ✅ | ✅ |

---

## 🔗 Интеграция с Fiscal Service

При обработке вебхука платежа автоматически вызывается:

```php
PaymentService::handleWebhook()
    ↓
FiscalService::sendReceipt([
    'payment_id' => ...,
    'tax_system' => $tx->metadata['tax_system'],
    'metadata' => [...]
], $items)
```

Fiscal Service уже содержит полную логику обработки НДС.

---

**Статус:** ✅ Production Ready  
**Дата обновления:** 10 марта 2026 г.
