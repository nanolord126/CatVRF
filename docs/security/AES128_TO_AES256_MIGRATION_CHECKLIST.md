# Чек-лист миграции AES-128 → AES-256 (Защита от алгоритма Гровера)

**Версия:** 1.0  
**Дата:** 23.04.2026  
**Проект:** CatVRF — AI-powered Healthcare Marketplace  
**Цель:** Миграция на AES-256-GCM для защиты от Grover's algorithm (УБИ.КВАНТ-002)

---

## Обзор

Этот чек-лист предназначен для систематической миграции всех симметрично зашифрованных данных с AES-128 на AES-256-GCM. Это необходимо для защиты от квадратичного ускорения brute-force атак с помощью алгоритма Гровера на будущих квантовых компьютерах.

**Релевантность для CatVRF:**
- Column-level encryption ПДн (email, phone, passport, inn)
- Biometric vectors (behavioral profiles)
- KYB документы
- Tokens и secrets

**Ссылка:** `docs/security/QUANTUM_THREATS_GROVER_ALGORITHM.md`

---

## Фаза 1: Подготовка и планирование

### 1.1 Аудит текущего состояния
- [ ] **Сканировать все модели** на использование `EncryptedCast` (legacy AES-128)
  ```bash
  grep -r "EncryptedCast" app/Models/
  ```
- [ ] **Идентифицировать все PII поля** с симметричным шифрованием
  - email, phone, passport, inn, biometric_vector, behavioral_profile
- [ ] **Оценить объём данных** для миграции
  ```sql
  SELECT table_name, COUNT(*) FROM information_schema.columns 
  WHERE table_schema = 'catvrf' AND column_name LIKE '%encrypted%';
  ```
- [ ] **Создать inventory** всех зашифрованных полей
  ```json
  {
    "users": ["email", "phone", "passport"],
    "behavioral_profiles": ["vector"],
    "kyb_documents": ["document_data"]
  }
  ```

### 1.2 Конфигурация окружения
- [ ] **Сгенерировать AES-256 ключ** (32 bytes / 256 bits)
  ```bash
  php artisan key:generate --aes256
  # Или вручную:
  openssl rand -base64 32
  ```
- [ ] **Добавить в .env**
  ```env
  APP_AES256_KEY=<base64-encoded-32-byte-key>
  SECURITY_HAS_LEGACY_AES128_DATA=true
  SECURITY_USES_LEGACY_ENCRYPTION_CAST=true
  SECURITY_LAST_KEY_ROTATION_DATE=2026-04-23
  ```
- [ ] **Обновить config/app.php**
  ```php
  'aes256_key' => env('APP_AES256_KEY'),
  ```

### 1.3 Планирование миграции
- [ ] **Определить порядок миграции** (критичные данные → менее критичные)
  1. biometric_vector, behavioral_profile (наивысший приоритет)
  2. passport, inn (высокий приоритет)
  3. email, phone (средний приоритет)
  4. остальные PII (низкий приоритет)
- [ ] **Оценить время простоя** (downtime) для каждой таблицы
- [ ] **Создать backup** всех затрагиваемых таблиц
- [ ] **Подготовить rollback plan** на случай проблем

---

## Фаза 2: Реализация AES-256-GCM

### 2.1 Разработка AES256EncryptedCast
- [ ] **Создать `app/Casts/AES256EncryptedCast.php`**
  - ✅ Уже создан (см. `app/Casts/AES256EncryptedCast.php`)
  - Использует AES-256-GCM с authenticated encryption
  - Поддержка legacy v1 (Laravel default encryption)
  - Version prefix для crypto-agility (v2:)

### 2.2 Обновление моделей
- [ ] **Заменить `EncryptedCast` на `AES256EncryptedCast`** в критичных моделях
  ```php
  // app/Models/User.php
  protected $casts = [
      'email' => AES256EncryptedCast::class, // было: EncryptedCast::class
      'phone' => AES256EncryptedCast::class,
      'passport' => AES256EncryptedCast::class,
      'inn' => AES256EncryptedCast::class,
  ];
  ```
- [ ] **Обновить `EncryptedBiometricVector`** для использования AES-256-GCM
  ```php
  // app/Casts/EncryptedBiometricVector.php
  // Обновить на использование AES-256-GCM
  ```

### 2.3 Тестирование нового cast
- [ ] **Написать unit тесты** для AES256EncryptedCast
  - Тест шифрования/дешифрования
  - Тест legacy support (v1)
  - Тест ошибок аутентификации (tag mismatch)
- [ ] **Протестировать на staging** с реальными данными
- [ ] **Проверить производительность** (benchmark vs legacy)

---

## Фаза 3: Миграция существующих данных

### 3.1 Создание миграционного скрипта
- [ ] **Создать Artisan команду** для миграции
  ```bash
  php artisan make:command MigrateToAES256
  ```
- [ ] **Реализовать логику миграции**
  ```php
  // app/Console/Commands/MigrateToAES256.php
  public function handle()
  {
      // 1. Читаем данные с legacy шифрованием
      // 2. Дешифруем с v1
      // 3. Шифруем с AES-256-GCM (v2)
      // 4. Обновляем в базе
      // 5. Логируем прогресс
  }
  ```

### 3.2 Миграция по таблицам
- [ ] **Мигрировать behavioral_profiles** (наивысший приоритет)
  ```bash
  php artisan migrate:aes256 --table=behavioral_profiles --column=vector
  ```
- [ ] **Мигрировать users** (passport, inn)
  ```bash
  php artisan migrate:aes256 --table=users --column=passport
  php artisan migrate:aes256 --table=users --column=inn
  ```
- [ ] **Мигрировать users** (email, phone)
  ```bash
  php artisan migrate:aes256 --table=users --column=email
  php artisan migrate:aes256 --table=users --column=phone
  ```
- [ ] **Мигрировать остальные PII поля**

### 3.3 Валидация миграции
- [ ] **Проверить целостность данных** (sample verification)
  ```php
  // Случайная выборка для проверки
  $users = User::inRandomOrder()->limit(100)->get();
  foreach ($users as $user) {
      $this->assertNotNull($user->email);
      $this->assertNotNull($user->phone);
  }
  ```
- [ ] **Сравнить хэши до/после** (если применимо)
- [ ] **Проверить производительность запросов**

---

## Фаза 4: Деплой в продакшн

### 4.1 Подготовка к деплою
- [ ] **Обновить конфигурацию в проде**
  ```env
  APP_AES256_KEY=<production-key>
  SECURITY_HAS_LEGACY_AES128_DATA=false
  SECURITY_USES_LEGACY_ENCRYPTION_CAST=false
  ```
- [ ] **Задеплоить новый код** (AES256EncryptedCast, обновлённые модели)
- [ ] **Запустить миграцию данных** в период низкой нагрузки
  ```bash
  php artisan migrate:aes256 --all
  ```

### 4.4 Мониторинг после деплоя
- [ ] **Мониторить логи ошибок** дешифрования
- [ ] **Проверить метрики производительности**
- [ ] **Мониторить ThreatModelService** (grover_risk_level должен упасть)
- [ ] **Проверить функциональность** (login, KYB, behavioral auth)

---

## Фаза 5: Пост-миграционные действия

### 5.1 Очистка
- [ ] **Удалить legacy EncryptedCast** (если больше не используется)
- [ ] **Обновить документацию**
- [ ] **Обновить архитектурные диаграммы**

### 5.2 Key Rotation
- [ ] **Настроить автоматическую ротацию ключей** (ежеквартально)
  ```bash
  php artisan security:rotate-encryption-keys
  ```
- [ ] **Обновить SECURITY_LAST_KEY_ROTATION_DATE**
- [ ] **Добавить в cron** для автоматической ротации

### 5.3 Аудит
- [ ] **Запустить аудит криптографии** (см. `tests/Feature/CryptographyAuditTest.php`)
- [ ] **Обновить акт УЗ-3** с новым статусом миграции
- [ ] **Отправить отчёт в security team**

---

## Чек-лист для аудита ФСТЭК/Роскомнадзора

### Доказательства миграции
- [ ] **Инвентарный список** всех зашифрованных полей
- [ ] **Логи миграции** с timestamp и количеством записей
- [ ] **Unit тесты** для AES256EncryptedCast
- [ ] **Benchmark** производительности до/после
- [ ] **Конфигурация** .env с AES-256 ключом
- [ ] **Обновлённый акт УЗ-3** с разделом по Гроверу

### Соответствие требованиям
- [ ] **М.11 (шифрование)** — AES-256-GCM реализован
- [ ] **УБИ.КВАНТ-002** — Grover risk mitigated
- [ ] **Crypto-agility** — возможность смены алгоритма
- [ ] **Key rotation** — автоматическая ротация настроена

---

## Критерии приёмки

Миграция считается завершённой, когда:

- [ ] Все PII поля используют AES256EncryptedCast
- [ ] Все существующие данные мигрированы на AES-256-GCM
- [ ] Legacy EncryptedCast удалён или не используется
- [ ] Unit тесты проходят (100% coverage для криптографии)
- [ ] Performance overhead < 10% vs legacy
- [ ] ThreatModelService::getGroverRiskLevel() возвращает 'low'
- [ ] Key rotation настроен и протестирован
- [ ] Документация обновлена
- [ ] Акт УЗ-3 обновлён с новым статусом

---

## Rollback Plan

Если миграция не удалась:

1. **Остановить миграцию** (Ctrl+C или kill process)
2. **Восстановить backup** базы данных
3. **Откатить код** на предыдущую версию
4. **Восстановить конфигурацию** .env
5. **Проанализировать логи ошибок**
6. **Исправить проблемы**
7. **Повторить миграцию** после исправлений

---

## Ссылки

- [Алгоритм Гровера](./QUANTUM_THREATS_GROVER_ALGORITHM.md)
- [Модель угроз ИСПДн](../compliance/ISPDN_THREAT_MODEL_FSTEC_BDU.md)
- [Акт УЗ-3](../compliance/UZ3_ACT_TEMPLATE.md)
- [AES256EncryptedCast](../../app/Casts/AES256EncryptedCast.php)
- [ThreatModelService](../../app/Services/Security/ThreatModelService.php)

---

**Document Owner:** Security Team  
**Last Updated:** 2026-04-23  
**Next Review:** 2026-07-23
