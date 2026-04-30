# Чек-лист соответствия 152-ФЗ и ФСТЭК №21 для CatVRF

## Обзор

CatVRF реализует комплексную систему защиты персональных данных в соответствии с:
- **Федеральный закон № 152-ФЗ** от 27.07.2006 «О персональных данных»
- **Постановление Правительства РФ № 1119** от 01.11.2012 (определение УЗ)
- **Приказ ФСТЭК № 21** от 18.02.2013 (15 групп мер защиты)

---

## 1. Уровень защищённости ИСПДн

### Определение уровня (Постановление № 1119)

| Тип данных | Объём субъектов | Уровень защищённости | Статус |
|------------|----------------|---------------------|--------|
| Иные ПДн | > 100 000 | УЗ-3 | ✅ Реализовано |
| Биометрические ПДн | > 100 000 | УЗ-3 | ✅ Реализовано |

**Конфигурация:** `config/personal-data.php` → `protection_level = 3`

---

## 2. 15 групп мер ФСТЭК №21

### Организационные меры

| # | Мера | Статус | Компонент |
|---|------|--------|-----------|
| 1 | Идентификация и аутентификация | ✅ | Passkeys, 2FA, Behavioral Biometrics |
| 2 | Разграничение доступа | ✅ | Spatie Permission, RoleIsolationService |
| 3 | Управление доступом | ✅ | RoleLimitService, InsiderThreatService |
| 4 | Регистрация и учёт действий | ✅ | PersonalDataAccessAudit, ClickHouse |
| 5 | Защита от НСД | ✅ | TenantIsolationMiddleware, Global Scopes |
| 6 | Антивирусная защита | ✅ | ClamAV, ServerAntivirus |
| 7 | Обновление ПО | ✅ | GitHub Actions, Dependabot, Snyk |
| 8 | Резервное копирование | ✅ | EncryptedBackupJob, S3/Glacier |
| 9 | Уничтожение ПДн | ✅ | PurgePersonalDataJob, ConsentEngine |
| 10 | Контроль за действиями | ✅ | BehavioralBiometrics, FraudControl |

### Технические меры (УЗ-3)

| # | Мера | Статус | Компонент |
|---|------|--------|-----------|
| 11 | Шифрование | ✅ | EncryptedCast, AES-256-GCM, Key Rotation |
| 12 | Защита каналов | ✅ | TLS 1.3, HSTS, mTLS |
| 13 | Средства защиты информации | ✅ | Cloudflare WAF, PostgreSQL Firewall, Suricata |
| 14 | Сегментация сети | ✅ | VPC, Security Groups, Subnets |
| 15 | Контроль целостности | ✅ | AIDE, ConfigHashCheck |

**Конфигурация:** `config/personal-data.php` → `fstec21_measures`

---

## 3. Биометрические данные (ст. 11 152-ФЗ)

### Требования

| Требование | Статус | Реализация |
|------------|--------|------------|
| Отдельное письменное согласие | ✅ | ConsentEngine, biometric_consent_form_template.md |
| УКЭП или письменная подпись | ✅ | signature_method: 'ukep'/'written' |
| Хранение в зашифрованном виде | ✅ | EncryptedCast, biometric_face_vector |
| Уничтожение при отзыве | ✅ | PurgePersonalDataJob (30 дней) |

**Конфигурация:** `config/personal-data.php` → `biometric`

---

## 4. Чек-лист для Роскомнадзора

### Перед подачей уведомления

- [x] Определён уровень защищённости (УЗ-3)
- [x] Реализованы 15 групп мер ФСТЭК №21
- [x] Подготовлен Акт определения уровня защищённости
- [x] Назначен ответственный за обработку ПДн
- [x] Разработана Политика обработки ПДн
- [x] Подготовлены формы согласий (в т.ч. на биометрию)
- [x] Обеспечена локализация данных на территории РФ
- [x] Реализовано шифрование данных (at-rest + in-transit)
- [x] Настроен immutable audit лог
- [x] Реализован механизм уничтожения ПДн

### Документы для подачи

- [x] Уведомление об обработке ПДн (форма Роскомнадзора)
- [x] Акт определения уровня защищённости
- [x] Политика обработки персональных данных
- [x] Приказ о назначении ответственного
- [x] Формы согласий на обработку ПДн
- [x] Сведения о мерах защиты информации

### После подачи уведомления

- [ ] Получен регистрационный номер в реестре Роскомнадзора
- [ ] Внесён номер в `.env` → `ROSKOMNADZOR_REGISTRY_NUMBER`
- [ ] Настроен quarterly аудит
- [ ] Подготовлен план реагирования на утечки

---

## 5. Техническая реализация

### Компоненты защиты

```
app/
├── Casts/
│   ├── EncryptedCast.php          # Column-level encryption
│   └── MaskedCast.php             # Data masking for staff
├── Services/
│   ├── PersonalData/
│   │   ├── PersonalDataProtectionService.php  # Central protection service
│   │   ├── ConsentEngine.php                   # Consent management
│   │   └── PersonalDataAccessAudit.php        # Immutable audit
│   └── Security/
│       └── InsiderThreatService.php           # Insider threat monitoring
└── Jobs/
    └── PersonalData/
        └── PurgePersonalDataJob.php           # Data destruction
```

### Миграции

```
database/
├── migrations/
│   ├── 2026_04_23_000001_create_cooldown_periods_table.php
│   ├── 2026_04_23_000002_create_user_consents_table.php
│   ├── 2026_04_23_000003_add_biometric_fields_to_users_table.php
│   └── 2026_04_23_000004_create_insider_threat_monitoring_table.php
└── clickhouse/
    └── migrations/
        └── personal_data_audit.sql  # Immutable audit schema
```

### Конфигурация

```php
// config/personal-data.php
'protection_level' => 3,  // УЗ-3 для биометрии
'fstec21_measures' => [...],  // 15 групп мер
'biometric' => [
    'requires_separate_consent' => true,
    'signature_methods' => ['ukep', 'written', 'click'],
    'storage_encrypted' => true,
    'destruction_days' => 30,
],
```

---

## 6. Тестирование

### Покрытие тестами

- [x] Биометрия без согласия → 403
- [x] Отзыв consent → уничтожение (30 дней)
- [x] Staff access → masked data
- [x] User access → unmasked data
- [x] JIT access → unmasked data
- [x] Анонимизация для внешней обработки
- [x] Data export с согласием

**Запуск тестов:**
```bash
php artisan test tests/Feature/PersonalData/PersonalDataProtectionTest.php
```

---

## 7. Мониторинг и аудит

### Immutable Audit (ClickHouse)

- Таблица: `personal_data_audit`
- TTL: 7 лет
- Partitioning: по месяцам
- Materialized views:
  - `insider_threat_monitoring`
  - `biometric_collection_monitoring`
  - `personal_data_access_daily`

### Insider Threat Detection

- Порог доступа: 50 запросов/час
- Cross-tenant: 3 попытки → критический alert
- Mass export: 10 попыток/час → критический alert
- Biometric access: 20 попыток/день → high alert

---

## 8. Quarterly Audit Checklist

### Ежеквартально

- [ ] Проверка актуальности мер защиты
- [ ] Аудит доступа сотрудников к ПДн
- [ ] Проверка логов на аномалии
- [ ] Обновление документации
- [ ] Проверка ротации ключей шифрования
- [ ] Тестирование процедуры уничтожения ПДн
- [ ] Обучение сотрудников по 152-ФЗ

### Ежегодно

- [ ] Пересмотр уровня защищённости
- [ ] Обновление модели угроз
- [ ] Проверка актуальности согласий
- [ ] Аттестация ИСПДн (при необходимости)

---

## 9. Реагирование на утечки

### При обнаружении утечки

1. **0-24 часа:** Определение масштаба утечки
2. **24-72 часа:** Уведомление Роскомнадзора (если > 1000 субъектов)
3. **72 часа:** Уведомление пострадавших субъектов

### Шаблон уведомления

См. `docs/compliance/152-fz/breach_notification_template.md`

---

## 10. Контакты

### Ответственный за обработку ПДн

- **Имя:** {{RESPONSIBLE_PERSON_NAME}}
- **Должность:** {{RESPONSIBLE_PERSON_POSITION}}
- **Email:** {{RESPONSIBLE_PERSON_EMAIL}}
- **Телефон:** {{RESPONSIBLE_PERSON_PHONE}}

### Юридический отдел

- **Email:** legal@catvrf.ru
- **Телефон:** +7 (495) XXX-XX-XX

---

## Документация

- [Политика обработки ПДн](./privacy_policy_template.md)
- [Форма согласия на биометрию](./biometric_consent_form_template.md)
- [Акт определения УЗ](./protection_level_act_template.md)
- [Приказ о назначении ответственного](./order_responsible_person_template.md)
- [Production Guide](./production_guide.md)

---

**Версия документа:** 1.0  
**Дата последнего обновления:** 23.04.2026  
**Статус:** Production Ready
