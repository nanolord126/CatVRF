# Warehouse Vertical - Compliance Documentation

**Версия:** 1.1.0  
**Дата:** 28.04.2026  
**Статус:** Active

## Overview

Документация по compliance с российскими федеральными законами для вертикали Warehouse.

## Федеральные Законы

### 152-ФЗ (Персональные данные)

**Реализовано:**
- ✅ EncryptionService - AES-256-GCM шифрование чувствительных полей
- ✅ RightToBeForgottenService - удаление PII по требованию субъекта
- ✅ ConsentManagementService - управление согласиями на обработку ПД
- ✅ Миграции: pii_deletion_requests, pii_consents
- ✅ Полное удаление PII из всех таблиц
- ✅ Анонимизация исторических данных

**Механизмы защиты:**
```php
// Шифрование данных
$encrypted = $encryptionService->encrypt('sensitive_data');

// Удаление по требованию субъекта
$result = $rightToBeForgotten->processDeletionRequest($userId, $reason, $approverId);

// Управление согласиями
$consentId = $consentService->createConsent($userId, 'personal_data_processing', $text);
$hasConsent = $consentService->hasActiveConsent($userId, 'personal_data_processing');
```

**Политика хранения:**
- Логи: 1 год
- Инвентаризации: 5 лет
- Движения товаров: 7 лет
- Партии: до истечения срока годности + 1 год

### ФЗ-323 (Об основах охраны здоровья)

**Реализовано:**
- ✅ LicenseManagementService - проверка лицензий
- ✅ Контроль температурного режима
- ✅ Контроль срока годности с автоматической блокировкой
- ✅ Проверка требований к помещениям

**Механизмы контроля:**
```php
// Проверка лицензии склада
$licenseService->validateWarehouseLicense($warehouse, 'pharmaceutical');

// Проверка температурного режима
$licenseService->validateTemperatureRequirements($product, 5.0, 60.0);

// Проверка срока годности партии
$licenseService->validateBatchExpiry($batch);
```

**Cold Chain Configuration:**
```php
'temperature_ranges' => [
    'standard' => ['min' => 2.0, 'max' => 25.0],
    'refrigerated' => ['min' => 2.0, 'max' => 8.0],
    'frozen' => ['min' => -25.0, 'max' => -10.0],
],
```

**Осталось реализовать:**
- Интеграция с ЕГИСЗ
- Проверка рецептов для рецептурных препаратов
- Специальные требования к хранению наркотических/психотропических веществ
- Лицензионный контроль в БД

### ФЗ-61 (Обращение лекарственных средств)

**Реализовано:**
- ✅ ChestnyZnakService - полная интеграция с Честный ЗНАК
- ✅ Миграции: chestny_znak_documents, chestny_znak_webhooks
- ✅ Проверка кодов маркировки DataMatrix
- ✅ Регистрация прихода/отгрузки/списания/возврата
- ✅ Обработка webhook уведомлений
- ✅ Retry-logic с экспоненциальным backoff
- ✅ Синхронизация статусов документов

**Механизмы интеграции:**
```php
// Проверка кода маркировки
$chestnyZnak->checkMarkingCode('(01)04600000000000(21)1234567(17)260426(10)ABC123)');

// Регистрация прихода
$documentId = $chestnyZnak->registerReceipt($documentNumber, $documentDate, $markedProducts);

// Обработка webhook
$result = $chestnyZnak->handleWebhook($payload);

// Синхронизация статусов
$results = $chestnyZnak->syncDocumentStatuses();
```

// Регистрация отгрузки
$documentId = $chestnyZnak->registerShipment(
    $documentNumber,
    $documentDate,
    $receiverInn,
    $markedProducts
);
```
Устаовкстфикаодля ЭЦП
**Нассройь реализовтendpointдля паиемтаA еесылй sйЗНАК
- Синхронизация статусов документов
- Обработка ошибок и retry-logic

### Контроль наркотических/психотропных веществ

**Реализовано:**
- ✅ ControlledSubstancesService - контроль Списков I, II, III
- ✅ Двойной контроль доступа
- ✅ Лимиты (дневные и месячные)
- ✅ Логирование операций
- ✅ Генерация отчетов для ФСБ

**Механизмы контроля:**
```php
// Проверка на контролируемое вещество
$controlled = $controlledService->isControlledSubstance('narcotic');

// Определение списка
$list = $controlledService->determineControlledList('psychotropic');

// Проверка двойного контроля
$dualControl = $controlledService->requireDualControl($list);

// Логирование операции
$controlledService->logControlledOperation(
    $userId,
    $secondUserId,
    'issue',
    $productId,
    $quantity,
    $reason
);
```

**Осталось реализовать:**
- Миграция для таблицы `warehouse_controlled_substances_log`
- Интеграция с ФСБ API
- Автоматическая генерация и отправка отчетов

## RBAC (Role-Based Access Control)

**Реализовано:**
- ✅ WarehousePolicy - управление складами
- ✅ InventoryItemPolicy - управление запасами
- ✅ StockMovementPolicy - управление движениями
- ✅ InventoryCountPolicy - управление инвентаризациями
- ✅ BatchPolicy - управление партиями
- ✅ ProductPolicy - управление товарами
- ✅ BinPolicy - управление ячейками

**Segregation of Duties:**
- Создание и утверждение инвентаризации - разные роли
- Работа с контролируемыми веществами - только менеджеры
- Двойной контроль для критических операций

**Осталось реализовать:**
- Регистрация policies в AuthServiceProvider
- Создание permissions в БД
- Связь roles с permissions
- Tenant isolation в policies

## Audit Trail

**Реализовано:**
- ✅ AuditTrailService - детальный аудит операций
- ✅ Логирование всех критических операций
- ✅ Интеграция с PIIProtectionService
- ✅ Логирование попыток несанкционированного доступа

**Типы событий:**
- `warehouse_created` / `warehouse_updated`
- `stock_movement` / `batch_deducted` / `batch_quarantined`
- `inventory_count_started` / `inventory_count_completed` / `inventory_count_approved`
- `pii_access` / `unauthorized_attempt`

**Осталось реализовать:**
- Хранение audit trail в отдельной таблице
- UI для просмотра audit trail
- Экспорт audit trail для регуляторов

## Конфигурация

### Environment Variables

```bash
# PII Protection (152-ФЗ)
WAREHOUSE_PII_SALT=secure_random_string
WAREHOUSE_PII_AUTO_ANONYMIZE=true
WAREHOUSE_PII_ENCRYPT=true

# Data Retention
WAREHOUSE_RETENTION_LOGS=1 year
WAREHOUSE_RETENTION_INVENTORY_COUNTS=5 years
WAREHOUSE_RETENTION_STOCK_MOVEMENTS=7 years
WAREHOUSE_RETENTION_AUTO_CLEANUP=true

# License Management (ФЗ-323, ФЗ-61)
WAREHOUSE_CHECK_PHARMACY_LICENSE=true
WAREHOUSE_CHECK_CONTROLLED_LICENSE=true
WAREHOUSE_EXPIRY_WARNING_DAYS=30
WAREHOUSE_CHECK_TEMPERATURE=true

# Честный ЗНАК
WAREHOUSE_CHESTNY_ZNAK_ENABLED=false
WAREHOUSE_CHESTNY_ZNAK_API_URL=https://api.crpt.ru
WAREHOUSE_CHESTNY_ZNAK_API_KEY=
WAREHOUSE_CHESTNY_ZNAK_CERT_PATH=

# ЕГИСЗ
WAREHOUSE_EGISZ_ENABLED=false
WAREHOUSE_EGISZ_API_URL=https://egisz.rosminzdrav.gov.ru
WAREHOUSE_EGISZ_API_KEY=
```

## TODO

### Критически важно (блокеры для продакшена)
- [ ] Реализовать шифрование чувствительных полей в БД
- [ ] Реализовать right to be forgotten
- [ ] Добавить Consent management
- [ ] Реализовать полную интеграцию с Честный ЗНАК
- [ ] Реализовать интеграцию с ЕГИСЗ
- [ ] Создать миграцию для controlled substances log
- [ ] Зарегистрировать policies в AuthServiceProvider
- [ ] Реализовать tenant isolation в policies

### Высокий приоритет
- [ ] Создать таблицу для audit trail
- [ ] Реализовать UI для просмотра audit trail
- [ ] Создать Permission seeder
- [ ] Добавить webhook обработчик от Честный ЗНАК
- [ ] Реализовать retry-logic для API вызовов
- [ ] Добавить monitoring для compliance метрик

### Средний приоритет
- [ ] Интеграция с 1С
- [ ] Автоматическая отправка отчетов в ФСБ
- [ ] UI для управления consent
- [ ] Dashboard для compliance статуса
- [ ] Alerting для compliance нарушений

## Testing

### Unit Tests
```php
// PII Protection
$piiService->anonymizeForLogs('Иванов Иван Иванович', 'name');
// Expected: И*** И*** И***

// License Management
$licenseService->validateBatchExpiry($expiredBatch);
// Expected: throw LicenseManagementException

// Chestny ZNAK
$chestnyZnak->validateMarkingCodeFormat('(01)04600000000000(21)1234567(17)260426(10)ABC123)');
// Expected: true
```

### Integration Tests
- Тесты интеграции с Честный ЗНАК (mock API)
- Тесты RBAC policies
- Тесты audit trail
- Тесты data retention

## References

- [152-ФЗ](http://www.consultant.ru/document/cons_doc_LAW_61801/)
- [ФЗ-323](http://www.consultant.ru/document/cons_doc_LAW_121895/)
- [ФЗ-61](http://www.consultant.ru/document/cons_doc_LAW_183484/)
- [Честный ЗНАК API](https://crpt.ru/api/)
- [ЕГИСЗ](https://egisz.rosminzdrav.gov.ru/)

---

**Версия:** 1.0.0  
**Дата:** 28.04.2026  
**Автор:** CatVRF Development Team
- [ФЗ-323](http://www.consultant.ru/document/cons_doc_LAW_121895/)
- [ФЗ-61](http://www.consultant.ru/document/cons_doc_LAW_183484/)
- [Честный ЗНАК API](https://crpt.ru/api/)
- [ЕГИСЗ](https://egisz.rosminzdrav.gov.ru/)

---

**Версия:** 1.0.0  
**Дата:** 28.04.2026  
**Автор:** CatVRF Development Team
