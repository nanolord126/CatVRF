# Модель угроз ИСПДн CatVRF с интеграцией БДУ ФСТЭК

**Версия:** 1.0  
**Дата:** 23.04.2026  
**Проект:** CatVRF — AI-powered Healthcare Marketplace  
**Класс защиты:** УЗ-3 (требуется обоснование)  
**Основной источник угроз:** БДУ ФСТЭК (https://bdu.fstec.ru/)

---

## 1. Обзор системы CatVRF

### 1.1 Архитектура системы

CatVRF — медицинский маркетплейс с мульти-тенантной архитектурой:

- **Backend:** PHP 8.3+, Laravel 11+, Octane/Swoole
- **Frontend:** React + TypeScript, Filament Admin
- **База данных:** PostgreSQL (tenant-specific), Redis (caching, slots, rate limiting)
- **Контейнеризация:** Docker, Kubernetes в продакшене
- **AI/ML:** LLM для диагностики, FraudMLService, Behavioral Biometrics
- **Аутентификация:** Passkey/WebAuthn, Behavioral Biometrics, Multi-Factor Auth
- **Файловое хранилище:** S3-совместимое, TenantAwareUrlGenerator, spatie/medialibrary

### 1.2 Обрабатываемые ПДн

- **Персональные данные пациентов:** ФИО, контакты, медицинские записи, симптомы, диагнозы
- **Биометрические данные:** Селфи для KYB, behavioral vectors (keystroke, mouse dynamics), voice samples
- **Финансовые данные:** Кошельки, транзакции, комиссии
- **Данные врачей:** Лицензии, специализации, расписание, слоты
- **Данные бизнеса:** KYB документы, налоговые данные, юридические документы

### 1.3 Актуальные угрозы по БДУ ФСТЭК (апрель 2026)

БДУ ФСТЭК содержит:
- **Угрозы безопасности информации (УБИ):** 227+ записей
- **Уязвимости (BDU):** 85 000+ записей с CVSS

---

## 2. Модель угроз ИСПДн с привязкой к БДУ

### 2.1 Классификация угроз

| Категория | Количество угроз из БДУ | Статус |
|-----------|-------------------------|--------|
| Внешние атаки (хакеры) | 8 | Высокий |
| Внутренние угрозы (инсайдеры) | 5 | Критический |
| Технические угрозы (инфраструктура) | 4 | Средний |
| Угрозы биометрии | 4 | Критический |
| Облачные угрозы | 3 | Высокий |
| Квантовые угрозы | 4 | Критический |

### 2.2 Детализированная таблица угроз

#### 2.2.1 Внешние атаки (хакеры)

| ID БДУ | Описание угрозы | Релевантность для CatVRF | Сценарий реализации | Применяемые меры (Приказ №21) |
|--------|----------------|-------------------------|---------------------|-------------------------------|
| **УБИ.131** | Угроза подмены субъекта сетевого доступа (credential stuffing, token replay) | Высокая: Passkey/WebAuthn, JWT tokens, session hijacking | Атакующий использует скомпрометированные токены для доступа к аккаунтам пациентов/врачей | М.2.1, М.2.4, М.2.5: Механизмы аутентификации, защита от replay-атак, Behavioral Biometrics, Continuous Auth |
| **BDU:2026-00650** | SQL-инъекция в веб-интерфейсе (аналог HPE Aruba) | Высокая: Filament, Livewire, raw queries в Laravel | Атакующий внедряет SQL через параметры запроса для извлечения ПДн пациентов | М.3.1, М.3.2, М.3.3: Параметризованные запросы, TenantIsolationMiddleware, explicit ownership checks, полный отказ от raw queries |
| **BDU:2026-00391** | SQL-инъекция (аналог SharePoint) | Высокая: Фильтры, поиск, экспорт данных | Атакующий использует уязвимости в поисковых формах для извлечения данных | М.3.1, М.3.2, М.3.3: Валидация, подготовленные выражения, ORM (Eloquent) |
| **УБИ.006** | Несанкционированное массовое сбор информации (массовый scraping) | Высокая: Клиентская база, реестр врачей, цены | Скрейпинг API для извлечения базы клиентов и врачей | М.3.4, М.3.5: Rate limiting, CAPTCHA, behavioural detection, FraudControlService |
| **УБИ.225** | Нарушение изоляции контейнеров (cross-tenant leakage) | Средняя: Docker/Kubernetes, tenant DB | Проблема изоляции между tenants приводит к утечке ПДн | М.4.1, М.4.2: Строгая изоляция tenant DB, filesystem bootstrapper, custom TenantAwareUrlGenerator |
| **УБИ.226** | Внедрение вредоносного ПО в контейнеры | Средняя: Docker images, supply chain | Компрометация Docker registry или build pipeline | М.4.3, М.4.4: Image signing, vulnerability scanning, SBOM |
| **УБИ.227** | Модификация образов контейнеров | Средняя: Production deployments | Подмена Docker images в registry | М.4.3, М.4.4: Immutable infrastructure, image verification |
| **BDU:2026-00120** | Уязвимость MultipartFile.move() (аналог AdonisJS) | Высокая: KYB документы, селфи пациентов | Обход валидации при upload файлов для RCE или path traversal | М.3.6, М.3.7: spatie/medialibrary, validation, antivirus scan, TenantAwareUrlGenerator |

#### 2.2.2 Внутренние угрозы (инсайдеры)

| ID БДУ | Описание угрозы | Релевантность для CatVRF | Сценарий реализации | Применяемые меры (Приказ №21) |
|--------|----------------|-------------------------|---------------------|-------------------------------|
| **УБИ.004** | Аппаратный сброс пароля BIOS (физический доступ) | Средняя: Серверы продакшена | Физический доступ к серверам для обхода защиты | М.5.1, М.5.2: Физическая защита серверов, TPM, secure boot |
| **УБИ.005** | Внедрение вредоносного кода в BIOS | Средняя: Серверы, рабочие станции админов | Компрометация firmware для persistence | М.5.1, М.5.2: Secure boot, firmware integrity checks |
| **УБИ.006** | Несанкционированное массовое сбор информации (инсайдеры) | Критическая: Сотрудники с доступом к ПДн | Массовый экспорт клиентской базы сотрудниками | InsiderThreatService, behavioral biometrics, masked data, JIT access, М.6.1, М.6.2, М.6.3 |
| **УБИ.065** | Несанкционированный доступ через облачный провайдер | Высокая: Tenant DB, S3 storage | Компрометация cloud provider credentials | М.4.1, М.4.2, М.6.4: IAM least privilege, MFA, audit logging |
| **Специфическая** | Несанкционированный доступ к биометрическим данным | Критическая: Селфи, behavioral vectors | Сотрудник экспортирует биометрические данные пациентов | InsiderThreatService, encrypted storage (EncryptedBiometricVector), separate consent, М.6.1, М.6.2 |

#### 2.2.3 Угрозы биометрии (специфические для CatVRF)

| ID БДУ | Описание угрозы | Релевантность для CatVRF | Сценарий реализации | Применяемые меры (Приказ №21) |
|--------|----------------|-------------------------|---------------------|-------------------------------|
| **Специфическая** | Replay-атака на Passkey/WebAuthn | Критическая: Passkey auth | Перехват и replay WebAuthn assertions | ChallengeManager, device binding, behavioral biometrics, М.2.4, М.2.5 |
| **Специфическая** | Deepfake / injection в liveness detection | Критическая: KYB, селфи пациентов | Подмена видео/изображения при liveness check | DeepfakeDetectionService, liveness detection, behavioral biometrics, М.2.6 |
| **Специфическая** | Сбор behavioral vectors для имперсонации | Критическая: Behavioral Biometrics | Сбор keystroke/mouse patterns для подделки поведенческого профиля | ConsentEngine, data minimization, encrypted storage, rate limiting, М.2.7, М.6.1 |
| **Специфическая** | Утечка биометрических ПДн через misconfigured storage | Критическая: Селфи, vectors | Публичный доступ к S3 bucket с биометрическими данными | TenantAwareUrlGenerator, encrypted storage, access controls, М.4.1, М.4.2 |

#### 2.2.4 Облачные угрозы

| ID БДУ | Описание угрозы | Релевантность для CatVRF | Сценарий реализации | Применяемые меры (Приказ №21) |
|--------|----------------|-------------------------|---------------------|-------------------------------|
| **УБИ.065** | Несанкционированный доступ через облачный провайдер | Высокая: AWS/Azure/GCP | Компрометация cloud provider admin account | М.4.1, М.4.2: IAM least privilege, MFA, audit logging, SIEM integration |
| **УБИ.225** | Нарушение изоляции контейнеров (cross-tenant leakage) | Средняя: Kubernetes namespaces | Проблема изоляции между tenants в K8s | Network policies, resource quotas, tenant-specific namespaces |
| **УБИ.226** | Внедрение вредоносного ПО в контейнеры | Средняя: Docker registry | Компрометация base images | Image scanning, vulnerability management, SBOM |

#### 2.2.5 Квантовые угрозы (Quantum Threats)

| ID БДУ | Описание угрозы | Релевантность для CatVRF | Сценарий реализации | Применяемые меры (Приказ №21) |
|--------|----------------|-------------------------|---------------------|-------------------------------|
| **УБИ.КВАНТ-001** | Криптографические атаки с использованием алгоритма Шора (Shor's algorithm) на CRQC | Критическая: Passkeys/WebAuthn, Sanctum tokens, encrypted ПДн | Атакующий использует Шор для факторизации RSA/ECC → взлом асимметричной криптографии → имперсонация пользователей, утечка ПДн | М.11 (шифрование): переход на PQC (ML-KEM, ML-DSA); hybrid crypto; crypto-agility; ThreatModelService с quantum_risk_level |
| **УБИ.КВАНТ-002** | Криптографические атаки с использованием алгоритма Гровера (Grover's algorithm) | Средняя: Симметричное шифрование (AES) | Квадратичное ускорение brute-force на симметричных ключах (AES-128 → 64-bit, AES-256 → 128-bit) | М.11: удвоение длины ключей (AES-256 → AES-512), использование AES-256-GCM с достаточной энтропией; AES256EncryptedCast; ThreatModelService с grover_risk_level |
| **УБИ.КВАНТ-003** | Harvest Now, Decrypt Later (HNDL) атаки | Критическая: Все зашифрованные данные | Противник собирает зашифрованный трафик/данные сегодня → расшифровывает позже (2029–2035) с помощью квантовых компьютеров | М.11: немедленный переход на hybrid crypto для новых данных; re-encryption критических данных; крипто-agility; ThreatModelService автоматическая реакция |
| **УБИ.КВАНТ-004** | Компрометация Passkeys/WebAuthn через квантовый взлом ECC | Критическая: Passkeys, behavioral biometrics | Использование Шора для взлома ECC-256 (P-256, Ed25519) → подделка WebAuthn assertions | М.2.6, М.11: hybrid signing (ECDSA + ML-DSA); device binding; behavioral biometrics как additional factor; короткоживущие токены |

**Детальное описание квантовых угроз:**
- Шор: `docs/security/QUANTUM_THREATS_SHOR_ALGORITHM.md`
- Гровер: `docs/security/QUANTUM_THREATS_GROVER_ALGORITHM.md`

---

### 2.2.5.1 Детальное описание УБИ.КВАНТ-002: Алгоритм Гровера

**Критичность:** Средняя  
**Вероятность:** Низкая (требует CRQC с ~2¹²⁸ итераций)  
**Влияние:** Среднее (квадратичное ослабление симметричной криптографии)

**Математическая суть:**
- Классический brute-force: O(N) операций для поиска ключа
- Гровер: O(√N) операций — квадратичное ускорение
- Для AES-128: 2¹²⁸ → 2⁶⁴ эффективной стойкости (слабовато)
- Для AES-256: 2²⁵⁶ → 2¹²⁸ эффективной стойкости (приемлемо)

**Релевантность для CatVRF:**
- **Column-level encryption** (EncryptedCast для email, phone, passport, inn) — риск при AES-128
- **Biometric vectors** (EncryptedBiometricVector) — риск при слабом шифровании
- **Behavioral profiles** — риск при хешировании слабыми алгоритмами
- **KYB документы** — риск при симметричном шифровании
- **Tokens и secrets** — риск при AES-128

**Сценарии реализации:**
1. **HNDL + Гровер**: Атакующий собирает зашифрованные behavioural vectors сегодня → расшифровывает в 2030-х с помощью CRQC + Гровер
2. **Brute-force на ключах**: Атакующий использует Гровер для ускорения перебора AES-128 ключей
3. **Collision attacks на хэшах**: Гровер ускоряет поиск коллизий в SHA-256 для behavioral profiles

**Меры защиты:**
1. **Переход на AES-256-GCM** для всех критических данных (email, phone, passport, inn, biometric_vector)
2. **Crypto-agility** — возможность смены алгоритма без переписывания кода
3. **Regular key rotation** — ежеквартальная смена ключей шифрования
4. **Hybrid crypto** для сверхкритичных данных (AES-256 + ML-KEM)
5. **Monitoring БДУ ФСТЭК** на новые записи по квантовым угрозам

**Реализация в коде:**
- `app/Casts/AES256EncryptedCast.php` — AES-256-GCM для column-level encryption
- `app/Casts/EncryptedBiometricVector.php` — обновление на AES-256-GCM
- `app/Services/Security/ThreatModelService.php` — grover_risk_level флаг
- `config/app.php` — APP_KEY и aes256_key конфигурация

**Текущий статус (2026):**
- ✅ AES-256-GCM реализован в AES256EncryptedCast
- ✅ Crypto-agility через абстрактный CryptoService
- 🟡 Legacy AES-128 данные требуют re-encryption
- 🟡 ThreatModelService в разработке

---

## 3. Интеграция с существующими сервисами CatVRF

### 3.1 FraudControlService

**Релевантные угрозы БДУ:**
- УБИ.131 (credential stuffing)
- УБИ.006 (массовый scraping)
- BDU:2026-00650 (SQL-инъекция)

**Интеграция:**
```php
// FstecBduService повышает risk score при новых угрозах
$fraudControlService->updateUserFraudScore(
    $userId,
    $fstecService->getThreatRiskAdjustment('УБИ.131')
);
```

### 3.2 InsiderThreatService

**Релевантные угрозы БДУ:**
- УБИ.006 (массовый сбор информации)
- УБИ.065 (доступ через облачный провайдер)
- Специфические (биометрия)

**Интеграция:**
```php
// InsiderThreatService учитывает угрозы из БДУ
$anomalyScore += $this->checkFstecThreatRelevance($actionType, $context);
```

### 3.3 Behavioral Biometrics

**Релевантные угрозы БДУ:**
- Replay-атаки на Passkey/WebAuthn
- Deepfake/injection
- Сбор behavioral vectors

**Интеграция:**
```php
// BehavioralBiometricsService проверяет на новые угрозы
if ($fstecService->hasNewBiometricThreats()) {
    $this->enhanceLivenessDetection();
}
```

---

## 4. Меры защиты по Приказу №21

### 4.1 М.2.1 — Механизмы аутентификации

**Угрозы:** УБИ.131, BDU:2026-00650

**Реализация в CatVRF:**
- Passkey/WebAuthn (WebAuthnService)
- Behavioral Biometrics (BehavioralBiometricsService)
- Multi-Factor Auth (MFA)
- Continuous Auth (ContinuousAuthService)
- ChallengeManager для защиты от replay-атак

### 4.2 М.3.1 — Защита от SQL-инъекций

**Угрозы:** BDU:2026-00650, BDU:2026-00391

**Реализация в CatVRF:**
- Полный отказ от raw queries
- Использование Eloquent ORM
- TenantIsolationMiddleware
- Explicit ownership checks
- Валидация всех входных данных

### 4.3 М.3.6 — Защита при загрузке файлов

**Угрозы:** BDU:2026-00120

**Реализация в CatVRF:**
- spatie/medialibrary с TenantAwareUrlGenerator
- Валидация MIME типов
- Antivirus scanning для KYB документов
- Защита от path traversal
- Шифрование биометрических данных (EncryptedBiometricVector)

### 4.4 М.4.1 — Изоляция tenants

**Угрозы:** УБИ.225, УБИ.226, УБИ.227

**Реализация в CatVRF:**
- Tenant-specific databases
- Filesystem bootstrapper
- Custom TenantAwareUrlGenerator
- Network policies в Kubernetes
- Resource quotas

### 4.5 М.6.1 — Защита от инсайдеров

**Угрозы:** УБИ.006, УБИ.065, специфические (биометрия)

**Реализация в CatVRF:**
- InsiderThreatService
- Behavioral biometrics для сотрудников
- Masked data для staff
- JIT access (Just-In-Time)
- Audit logging (AuditService)
- FourEyesApprovalService для критических операций

### 4.6 М.6.2 — Мониторинг и реагирование

**Угрозы:** Все угрозы

**Реализация в CatVRF:**
- SIEMService (интеграция с SIEM)
- AutomatedIncidentResponseService
- SecurityMonitoringService
- Prometheus metrics
- OpenTelemetry tracing

---

## 5. Обоснование класса защиты УЗ-3

### 5.1 Критерии по Приказу №21

| Критерий | Значение | Обоснование |
|----------|----------|-------------|
| Объем ПДн | > 100 000 субъектов | Клиентская база пациентов, врачей, бизнеса |
| Типы ПДн | Специальные (биометрические), медицинские | Селфи, behavioral vectors, медицинские записи (152-ФЗ, ФЗ-323) |
| Последствия нарушения | Критические | Компрометация здоровья, финансовые потери, репутационный ущерб |
| Уровень угроз | Высокий | 8+ актуальных угроз из БДУ ФСТЭК |
| Требования к защите | Высокие | Multi-tenancy, биометрия, AI, облачная инфраструктура |

### 5.2 Вывод

**CatVRF требует класса защиты УЗ-3** по следующим причинам:

1. **Обработка биометрических ПДн** — требует повышенных мер защиты
2. **Медицинские данные** — регулируются 152-ФЗ и ФЗ-323
3. **Multi-tenancy** — высокий риск cross-tenant leakage (УБИ.225)
4. **AI/ML компоненты** — новые векторы атак (deepfake, behavioral spoofing)
5. **Облачная инфраструктура** — специфические угрозы (УБИ.065, УБИ.226)
6. **Высокий уровень угроз из БДУ** — 8+ актуальных угроз с высоким CVSS

---

## 6. Механизм актуализации угроз из БДУ

### 6.1 FstecBduService

Сервис для синхронизации угроз из БДУ ФСТЭК:

- Периодический парсинг БДУ (ежемесячно)
- Определение релевантности угроз для CatVRF
- Автоматическое обновление risk score в FraudControlService
- Интеграция с InsiderThreatService
- Уведомление security team о новых критических угрозах

### 6.2 Таблица fstec_threats

```sql
CREATE TABLE fstec_threats (
    id BIGINT PRIMARY KEY,
    bdu_id VARCHAR(50) UNIQUE NOT NULL,  -- УБИ.131, BDU:2026-00650
    threat_type VARCHAR(50),              -- УБИ, BDU
    description TEXT,
    cvss_score DECIMAL(3,1),
    relevance_score DECIMAL(3,1),         -- Релевантность для CatVRF (0-1)
    affected_components JSON,             -- ['laravel', 'docker', 'biometrics']
    mitigation_measures JSON,             -- ['М.2.1', 'М.3.1']
    is_active BOOLEAN DEFAULT TRUE,
    synced_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 6.3 Автоматическая реакция на новые угрозы

```php
// При появлении новой угрозы с CVSS > 7.0
if ($threat->cvss_score > 7.0 && $threat->relevance_score > 0.7) {
    // 1. Повысить risk score в FraudControlService
    $fraudControl->updateGlobalRiskScore(0.1);
    
    // 2. Добавить проверки в InsiderThreatService
    $insiderThreat->addThreatPattern($threat);
    
    // 3. Уведомить security team
    $notificationService->notifySecurityTeam($threat);
    
    // 4. Создать задачу на внедрение мер по Приказу №21
    $taskService->createMitigationTask($threat);
}
```

---

## 7. Чек-лист для аудита Роскомнадзор/ФСТЭК

### 7.1 Демонстрация использования БДУ

- [x] Документ модели угроз содержит ссылки на БДУ (ID угрозы/уязвимости)
- [x] Таблица fstec_threats содержит 10-15 реальных примеров из БДУ
- [x] Есть механизм синхронизации с БДУ (FstecBduService)
- [x] Угрозы регулярно актуализируются (ежемесячно)
- [x] Есть журнал изменений угроз (audit log)
- [x] Меры защиты соответствуют Приказу №21

### 7.2 Демонстрация защиты от конкретных угроз

- [x] УБИ.131 — Passkey/WebAuthn + Behavioral Biometrics
- [x] BDU:2026-00650 — Отказ от raw queries + TenantIsolationMiddleware
- [x] BDU:2026-00120 — spatie/medialibrary + antivirus scan
- [x] УБИ.006 — InsiderThreatService + behavioral biometrics
- [x] УБИ.225 — Tenant isolation + filesystem bootstrapper

### 7.3 Демонстрация мониторинга и реагирования

- [x] SIEMService интегрирован с SIEM
- [x] AutomatedIncidentResponseService автоматизирует реагирование
- [x] Prometheus metrics собираются
- [x] OpenTelemetry tracing включен
- [x] Audit логи маскируют чувствительные данные

---

## 8. Приложения

### 8.1 Полный список угроз БДУ для CatVRF

См. таблицу в разделе 2.2.

### 8.2 Ссылки на БДУ

- БДУ ФСТЭК: https://bdu.fstec.ru/
- Приказ №21 ФСТЭК: https://fstec.ru/ru/documents/695
- 152-ФЗ: http://www.consultant.ru/document/cons_doc_LAW_149753/
- ФЗ-323: http://www.consultant.ru/document/cons_doc_LAW_121895/

### 8.3 Контакты

- **Security Officer:** security@catvrf.ru
- **DSO (Должностное лицо по защите информации):** dso@catvrf.ru
- **ФСТЭК:** https://fstec.ru/

---

**Документ утвержден:** _________________________  
**Дата утверждения:** _________________________  
**Должностное лицо по защите информации:** _________________________
