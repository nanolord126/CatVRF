# План миграции на Post-Quantum Cryptography (PQC) — Roadmap 2026–2030

**Версия:** 1.0  
**Дата:** 23.04.2026  
**Проект:** CatVRF — AI-powered Healthcare Marketplace  
**Автор:** Сенсей (ex-Amazon, Alibaba, Ozon • PhD по уязвимостям маркетплейсов)

---

## 1. Обзор

### 1.1 Цель миграции

Обеспечить защиту CatVRF от квантовых угроз (Shor's algorithm, Grover's algorithm, HNDL) путём перехода на Post-Quantum Cryptography (PQC) стандартизованный NIST.

### 1.2 Критичность

- **Критичность:** Критическая
- **Приоритет:** Высокий
- **Дедлайн:** 2028–2029 (до ожидаемого появления CRQC)

### 1.3 Стандарты NIST PQC

- **NIST FIPS 203** — Module-Lattice-Based Key-Encapsulation Mechanism (ML-KEM) — для обмена ключами
- **NIST FIPS 204** — Module-Lattice-Based Digital Signature (ML-DSA) — для цифровых подписей
- **NIST SP 800-208** — Stateful Hash-Based Signatures — для долгосрочных подписей

---

## 2. Текущее состояние (2026 Q1)

### 2.1 Классическая криптография

| Компонент | Алгоритм | Статус | Квантовая уязвимость |
|-----------|----------|--------|---------------------|
| Passkeys/WebAuthn | ECDSA P-256, Ed25519 | ✅ Используется | Критическая (Шор) |
| Sanctum Tokens | RSA-2048, ECDSA P-256 | ✅ Используется | Критическая (Шор) |
| Column-level Encryption | RSA-4096 envelope + AES-256-GCM | ✅ Используется | Критическая (Шор) |
| TLS | TLS 1.3 (ECDHE, RSA) | ✅ Используется | Критическая (Шор) |
| Behavioral Vectors | AES-256-GCM | ✅ Используется | Средняя (Гровер) |
| Database Encryption | AES-256-GCM | ✅ Используется | Средняя (Гровер) |

### 2.2 PQC-подготовка

| Компонент | Статус | Примечания |
|-----------|--------|------------|
| HybridCryptoService | ✅ Реализован | v2: AES-256 + ML-KEM (симуляция) |
| ThreatModelService quantum risk | ✅ Реализован | getQuantumRiskLevel, getQuantumRiskCategory |
| Crypto-agility | 🟡 Частично | Интерфейсы есть, полная миграция нужна |
| PQC библиотеки | 🔴 Не установлены | Требуется liboqs или php-pqc |

---

## 3. План миграции по фазам

### Фаза 1: Подготовка и пилот (2026 Q2–Q3)

**Цель:** Подготовить инфраструктуру и протестировать PQC в изолированной среде.

#### Задачи:

**Инфраструктура:**
- [ ] Установить liboqs или php-pqc extension
- [ ] Настроить тестовое окружение для PQC
- [ ] Создать PQC-compatible key management system
- [ ] Интегрировать с HSM (если поддерживает PQC)

**Разработка:**
- [ ] Реализовать реальные ML-KEM (Kyber-768) в HybridCryptoService
- [ ] Реализовать реальные ML-DSA (Dilithium-3) для подписей
- [ ] Создать PQC-compatible интерфейсы для всех криптографических операций
- [ ] Написать unit-тесты для PQC операций

**Тестирование:**
- [ ] Performance тестирование (latency, throughput)
- [ ] Compatibility тестирование с существующими системами
- [ ] Security audit PQC реализации
- [ ] Interoperability тестирование с внешними сервисами

**Документация:**
- [ ] Обновить архитектурную документацию
- [ ] Создать PQC migration guide для разработчиков
- [ ] Обновить security guidelines

**Критерии завершения:**
- PQC библиотеки установлены и работают
- Unit-тесты проходят (coverage > 90%)
- Performance penalty < 50ms per operation
- Security audit пройден

---

### Фаза 2: Hybrid режим для новых данных (2026 Q4–2027 Q2)

**Цель:** Включить hybrid encryption для всех новых данных.

#### Задачи:

**Passkeys/WebAuthn:**
- [ ] Внедрить hybrid signing (ECDSA + ML-DSA) для новых Passkeys
- [ ] Обновить WebAuthnRegistrationService
- [ ] Обновить WebAuthnAuthenticationService
- [ ] Добавить metadata о версии криптографии в базу

**Sanctum Tokens:**
- [ ] Внедрить hybrid JWT signing для новых токенов
- [ ] Обновить токен генерацию в AuthServiceProvider
- [ ] Добавить quantum risk level в payload токенов
- [ ] Уменьшить TTL токенов до 5-15 минут

**Column-level Encryption:**
- [ ] Внедрить hybrid envelope encryption для новых ПДн
- [ ] Обновить EncryptedCast для использования HybridCryptoService
- [ ] Приоритет: biometrics, PII, KYB документы
- [ ] Добавить metadata об алгоритме шифрования

**TLS:**
- [ ] Исследовать PQC TLS cipher suites (draft-ietf-tls-hybrid-design)
- [ ] Подготовить к внедрению hybrid TLS
- [ ] Тестировать с поддерживающими клиентами

**Мониторинг:**
- [ ] Добавить метрики PQC usage в Prometheus
- [ ] Мониторить performance impact
- [ ] Алертинг при quantum risk level changes

**Критерии завершения:**
- Все новые Passkeys используют hybrid signing
- Все новые Sanctum tokens используют hybrid signing
- Все новые ПДн используют hybrid encryption
- Performance impact < 100ms per request
- Нет regressions в функциональности

---

### Фаза 3: Re-encryption существующих данных (2027 Q3–2028 Q2)

**Цель:** Перешифровать критические данные с помощью hybrid crypto.

#### Задачи:

**Приоритизация данных:**

**Приоритет 1 (Критический):**
- [ ] Биометрические векторы (behavioral profiles)
- [ ] KYB документы (селфи, паспорта)
- [ ] Медицинские записи (симптомы, диагнозы)

**Приоритет 2 (Высокий):**
- [ ] Email, phone, passport
- [ ] Финансовые данные (кошельки, транзакции)
- [ ] Passkey credentials

**Приоритет 3 (Средний):**
- [ ] Адреса, контакты
- [ ] Логи с чувствительными данными
- [ ] Архивные данные

**Процесс re-encryption:**
- [ ] Создать background job для batch re-encryption
- [ ] Реализовать zero-downtime re-encryption (double-write)
- [ ] Добавить rollback механизм
- [ ] Тестировать на staging с реальным объёмом данных

**Мониторинг:**
- [ ] Отслеживать прогресс re-encryption
- [ ] Мониторить нагрузку на базу данных
- [ ] Алертинг при ошибках
- [ ] Проверять целостность данных после re-encryption

**Критерии завершения:**
- 100% критических данных перешифрованы
- 80% высокоприоритетных данных перешифрованы
- Нет data loss или corruption
- Re-encryption завершён без простоя

---

### Фаза 4: Полный переход на PQC (2028 Q3–2029 Q2)

**Цель:** Отключить классическую криптографию, использовать только PQC.

#### Задачи:

**Deprecation классических алгоритмов:**
- [ ] Отключить RSA-only encryption для новых данных
- [ ] Отключить ECDSA-only signing для новых токенов
- [ ] Оставить classical только для backward compatibility

**Переход на чистый PQC:**
- [ ] Оценить готовность экосистемы (браузеры, библиотеки)
- [ ] Тестировать pure PQC (без classical fallback)
- [ ] Планировать отключение classical в 2029–2030

**TLS:**
- [ ] Внедрить PQC TLS cipher suites
- [ ] Тестировать с основными браузерами и клиентами
- [ ] Обновить load balancer configuration

**Ключи:**
- [ ] Внедрить PQC key rotation
- [ ] Обновить HSM для поддержки PQC ключей
- [ ] Реализовать key escrow для PQC

**Критерии завершения:**
- Classical crypto disabled для новых данных
- PQC TLS работает для 95% трафика
- PQC key rotation автоматизирован
- Security audit пройден

---

### Фаза 5: Оптимизация и мониторинг (2029 Q3–2030)

**Цель:** Оптимизировать PQC производительность и мониторить новые угрозы.

#### Задачи:

**Оптимизация:**
- [ ] Оптимизировать PQC операции (hardware acceleration)
- [ ] Кэширование PQC операций где возможно
- [ ] Batch processing для PQC операций

**Мониторинг квантовых угроз:**
- [ ] Интеграция с NIST PQC timeline updates
- [ ] Мониторинг академических исследований
- [ ] Threat intelligence от NSA/CISA
- [ ] Обновление ThreatModelService с новыми индикаторами

**Обучение:**
- [ ] Обучить security team по PQC
- [ ] Обучить разработчиков по crypto-agility
- [ ] Создать playbooks для quantum incidents

**Критерии завершения:**
- PQC performance penalty < 20ms
- Quantum threat monitoring автоматизирован
- Team обучен работе с PQC

---

## 4. Риски и митигации

| Риск | Вероятность | Влияние | Митигация |
|------|------------|---------|-----------|
| PQC библиотеки не готовы | Средняя | Высокое | Использовать hybrid crypto как fallback |
| Performance degradation | Высокая | Среднее | Оптимизация, кэширование, hardware acceleration |
| Совместимость с клиентами | Средняя | Высокое | Graceful degradation, version negotiation |
| Ошибки в PQC реализации | Низкая | Критическое | Security audit, code review, testing |
| HNDL до завершения миграции | Средняя | Критическое | Приоритизация критических данных, ускорение миграции |

---

## 5. Ресурсы

### 5.1 Команда

- **Security Lead:** 1 FTE
- **Backend Developers:** 2 FTE
- **DevOps Engineer:** 1 FTE (частичная занятость)
- **Security Auditor:** Внешний консультант

### 5.2 Бюджет

| Категория | 2026 | 2027 | 2028 | 2029 | 2030 | Итого |
|-----------|------|------|------|------|------|-------|
| Разработка | $50k | $40k | $30k | $20k | $10k | $150k |
| Инфраструктура (HSM) | $30k | $20k | $10k | $5k | $5k | $70k |
| Security Audit | $20k | $15k | $10k | $10k | $5k | $60k |
| Обучение | $10k | $5k | $5k | $5k | $5k | $30k |
| Резерв | $20k | $20k | $20k | $10k | $10k | $80k |
| **Итого** | **$130k** | **$100k** | **$75k** | **$50k** | **$35k** | **$390k** |

---

## 6. Метрики успеха

### 6.1 Технические метрики

- **PQC adoption rate:** % данных зашифровано с PQC
- **Performance impact:** latency increase в ms
- **Security coverage:** % компонентов с PQC
- **Migration progress:** % данных перешифровано

### 6.2 Бизнес-метрики

- **Time to migration:** месяцы до завершения
- **Budget variance:** отклонение от бюджета в %
- **Incident rate:** security incidents related to migration
- **Compliance:** соответствие ФСТЭК/152-ФЗ

---

## 7. Критические зависимости

### 7.1 Внешние

- **NIST PQC standards:** FIPS 203, FIPS 204 должны быть финализованы
- **PQC библиотеки:** liboqs, php-pqc должны быть production-ready
- **HSM vendors:** поддержка PQC в HSM
- **Browser support:** PQC в основных браузерах

### 7.2 Внутренние

- **Team availability:** доступность security и backend команд
- **Budget:** финансирование на 5 лет
- **Management support:** приоритет проекта
- **Legacy systems:** совместимость с существующими системами

---

## 8. План отката (Rollback Plan)

### 8.1 Сценарии отката

**Сценарий 1: PQC библиотеки не работают**
- Откатиться на classical crypto
- Использовать hybrid mode как fallback
- Увеличить quantum risk monitoring

**Сценарий 2: Performance degradation**
- Отключить PQC для non-critical данных
- Оптимизировать PQC операции
- Рассмотреть hardware acceleration

**Сценарий 3: Security issue в PQC**
- Откатиться на classical crypto
- Сообщить в NIST о уязвимости
- Использовать альтернативные PQC алгоритмы

---

## 9. Ссылки

### 9.1 Стандарты

- [NIST FIPS 203: ML-KEM](https://csrc.nist.gov/pubs/fips/203/final)
- [NIST FIPS 204: ML-DSA](https://csrc.nist.gov/pubs/fips/204/final)
- [NIST SP 800-208: Stateful Hash-Based Signatures](https://csrc.nist.gov/pubs/sp/800/208/final)

### 9.2 Документация CatVRF

- [Quantum Threats: Shor's Algorithm](docs/security/QUANTUM_THREATS_SHOR_ALGORITHM.md)
- [ISPDN Threat Model](docs/compliance/ISPDN_THREAT_MODEL_FSTEC_BDU.md)
- [HybridCryptoService](app/Services/Security/HybridCryptoService.php)
- [ThreatModelService](app/Services/Security/ThreatModelService.php)

### 9.3 Внешние ресурсы

- [NIST PQC Project](https://csrc.nist.gov/projects/post-quantum-cryptography)
- [NSA/CNSA 2.0 Quantum Readiness](https://www.nsa.gov/News-Features/Feature-Stories/Article-View/Article/3351605/the-cnsa-2.0-algorithm-suite)
- [Open Quantum Safe](https://openquantumsafe.org/)

---

**Документ утвержден:** _________________________  
**Дата утверждения:** _________________________  
**CISO:** _________________________  
**CTO:** _________________________
