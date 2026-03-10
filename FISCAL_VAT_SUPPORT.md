# Поддержка НДС по системам налогообложения в Fiscal Services

## Общие сведения

Реализована полная поддержка учета НДС при фискализации чеков согласно ФЗ-54. Система поддерживает все основные российские системы налогообложения:

- **ОСН** (Общая система): НДС 0%, 10%, 18%, 20%
- **УСН** (Упрощенная система): без НДС
- **ЕСХН** (Единый сельскохозяйственный налог): без НДС
- **ЕНВД** (Единый налог на вмененный доход): без НДС
- **ПСН** (Патентная система): без НДС

## Обновленные файлы

### 1. **AtolFiscalDriver** (`app/Domains/Finances/Services/Fiscal/AtolFiscalDriver.php`)

#### Новые методы:
- **`getTaxRate(string $taxSystem, ?string $taxCode = null): array`**
  - Расчет налоговой ставки по системе налогообложения
  - Возвращает `['rate' => float, 'type' => string]`
  - Поддерживает системы: OSN, USN_INCOME, USN_INCOME_MINUS_EXPENSE, ENVD, ESN, PSN

- **`processItemsWithTax(array $items, string $taxSystem): array`**
  - Обработка товаров с добавлением налоговых данных
  - Преобразует товары в формат для Atol API

#### Обновленные методы:
- **`sendReceipt()`**
  - Теперь получает `tax_system` из данных транзакции
  - Обрабатывает товары через `processItemsWithTax()` перед отправкой

- **`validateItems(array $items): array`**
  - Изменен возвращаемый тип с `bool` на `array`
  - Возвращает `['valid' => bool, 'errors' => array]`
  - Проверяет наличие и валидность налоговых кодов

- **`getSupportedTaxes(): array`**
  - Полный список поддерживаемых налоговых кодов
  - Коды: VAT_0, VAT_10, VAT_18, VAT_20, NO_VAT

---

### 2. **CloudKassirFiscalDriver** (`app/Domains/Finances/Services/Fiscal/CloudKassirFiscalDriver.php`)

#### Новые методы:
- **`getTaxRate(string $taxSystem, ?string $taxCode = null): string`**
  - Расчет налоговой ставки для CloudKassir API
  - Возвращает строку с кодом налога (Vat0, Vat10, Vat18, NoVat, и т.д.)

- **`processItemsWithTax(array $items, string $taxSystem): array`**
  - Обработка товаров с добавлением правильного налогового кода
  - Поддерживает варианты названия полей (qty/quantity)

#### Обновленные методы:
- **`sendReceipt()`**
  - Добавлена обработка `tax_system` из данных транзакции
  - Вызывает `processItemsWithTax()` для преобразования товаров

- **`refundReceipt(string $fiscalId, float $amount, array $data = []): array`**
  - Изменена сигнатура (добавлен параметр `$amount` и `$data`)
  - Поддерживает налоговые данные при возврате

- **`validateItems(array $items): array`**
  - Изменен возвращаемый тип с `bool` на `array`
  - Проверяет валидность всех полей товаров
  - Проверяет наличие и валидность налоговых кодов

#### Улучшенная поддержка:
- CloudKassir коды налогов: Vat0, Vat10, Vat18, NoVat, VatMixedStandard
- CloudKassir системы налогообложения: OMS, UsnIncome, UsnIncomeMinusExpense, Envd, Esn, Patent

---

### 3. **FiscalService** (`app/Domains/Finances/Services/Fiscal/FiscalService.php`)

#### Обновленная документация:
- Добавлено описание поддержки НДС по различным системам налогообложения
- Указаны методы каждого драйвера для работы с налогами

#### Обновленные методы:
- **`refundReceipt(string $fiscalId, float $amount, array $data = []): array`**
  - Добавлена поддержка параметра `$data` для передачи налоговых данных
  - Поддерживает `tax_system`, `tax`, `reason`, `correlation_id` в данных возврата

---

### 4. **FiscalServiceInterface** (`app/Domains/Finances/Interfaces/FiscalServiceInterface.php`)

#### Обновления:
- Добавлена документация по поддержке НДС
- **`sendReceipt()`** - теперь принимает отдельно `$transactionData` и `$items`
- **`refundReceipt()`** - сигнатура изменена на `refundReceipt(string $fiscalId, float $amount, array $data = [])`
- Добавлены параметры для передачи `tax_system` в данных

---

### 5. **PaymentService** (`app/Domains/Finances/Services/PaymentService.php`)

#### Обновления:
- Добавлена документация с указанием поддержки фискализации с учетом системы налогообложения
- **`handleWebhook()`** - при отправке чека теперь передается `tax_system`:
  ```php
  $this->fiscal->sendReceipt([
      'payment_id' => $tx->payment_id,
      'amount' => $tx->amount,
      'correlation_id' => $correlationId,
      'tax_system' => $tx->metadata['tax_system'] ?? config('fiscal.common.taxation_system', 'OSN'),
      'metadata' => [
          'email' => $tx->metadata['customer_email'] ?? null,
          'phone' => $tx->metadata['customer_phone'] ?? null,
      ],
  ], $tx->metadata['items'] ?? []);
  ```

---

### 6. **FiscalDriverInterface** (`app/Domains/Finances/Interfaces/FiscalDriverInterface.php`)

✅ **Интерфейс уже содержит нужные методы и параметры:**
- `sendReceipt(array $tx, array $items): array`
- `refundReceipt(string $fiscalId, float $amount, array $data = []): array`
- `validateItems(array $items): array`

---

## Как использовать

### 1. При инициализации платежа

Передавайте `tax_system` в метаданных:

```php
$paymentService->initPayment([
    'amount' => 1000.00,
    'order_id' => 123,
    'metadata' => [
        'tax_system' => 'OSN', // Общая система
        'items' => [
            [
                'name' => 'Product A',
                'price' => 500.00,
                'qty' => 1,
                'tax' => 'vat_20'
            ],
            [
                'name' => 'Product B',
                'price' => 500.00,
                'qty' => 1,
                'tax' => 'vat_18'
            ]
        ],
        'customer_email' => 'user@example.com',
        'customer_phone' => '+79001234567',
    ]
]);
```

### 2. При возврате платежа

Передавайте информацию о налогах:

```php
$fiscalService->refundReceipt(
    'fiscal_id_xxxxx',
    500.00,
    [
        'tax_system' => 'OSN',
        'tax' => 'vat_20',
        'reason' => 'Customer refund',
        'correlation_id' => 'uuid'
    ]
);
```

### 3. Валидация товаров

```php
$items = [
    [
        'name' => 'Course',
        'price' => 100.00,
        'qty' => 1,
        'tax' => 'vat_18'
    ]
];

$driver = new AtolFiscalDriver();
$validation = $driver->validateItems($items);

if (!$validation['valid']) {
    // Обработка ошибок
    foreach ($validation['errors'] as $error) {
        Log::error($error);
    }
}
```

---

## Поддерживаемые налоговые коды

### Для Atol API:
- `VAT_0` - НДС 0%
- `VAT_10` - НДС 10%
- `VAT_18` - НДС 18%
- `VAT_20` - НДС 20%
- `NO_VAT` - Без НДС

### Для CloudKassir API:
- `Vat0` - НДС 0%
- `Vat10` - НДС 10%
- `Vat18` - НДС 18%
- `NoVat` - Без НДС
- `VatMixedStandard` - Смешанный НДС

---

## Примеры по системам налогообложения

### ОСН (Общая система)
- Полная поддержка налоговых ставок: 0%, 10%, 18%, 20%
- Обязательно указывать конкретную ставку в товарах
- По умолчанию: 20%

### УСН/ЕСХН/ЕНВД/ПСН
- Автоматически устанавливается `no_vat`
- Указанные в товарах налоговые коды игнорируются и переопределяются на `no_vat`
- Поддержка для compliance с российским законодательством

---

## Тестирование

Для тестирования можно использовать метод `validateItems()`:

```php
$atol = new AtolFiscalDriver();
$validation = $atol->validateItems([...items...]);

if ($validation['valid']) {
    echo "Товары валидны";
} else {
    echo "Ошибки: " . implode("; ", $validation['errors']);
}
```

---

## Миграция существующего кода

Если у вас есть вызовы старого API:

### Было:
```php
$fiscal->sendReceipt(['order_id' => 123, 'total' => 100]);
```

### Стало:
```php
$fiscal->sendReceipt([
    'payment_id' => 'pay_123',
    'amount' => 100,
    'tax_system' => 'OSN',
    'metadata' => ['email' => 'user@example.com']
], [
    ['name' => 'Product', 'price' => 100, 'qty' => 1, 'tax' => 'vat_18']
]);
```

---

**Дата обновления:** 10 марта 2026 г.  
**Статус:** Production Ready ✅
