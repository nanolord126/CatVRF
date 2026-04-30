# Алгоритм Гровера и влияние на симметричную криптографию CatVRF

**Версия:** 2.0  
**Дата:** 23.04.2026  
**Проект:** CatVRF — AI-powered Healthcare Marketplace  
**Автор:** Сенсей (ex-Amazon, Alibaba, Ozon • PhD по уязвимостям маркетплейсов)  
**Релевантно для:** Column-level encryption, behavioral vectors, KYB документы, tokens

---

## 1. Алгоритм Гровера — простыми словами от сенсея

Алгоритм Гровера (Lov Grover, 1996) — это квантовый алгоритм поиска в неструктурированной базе данных (unstructured search). Он решает задачу: «найти нужный элемент в списке из N элементов».

### 1.1 Суть алгоритма

**Классический brute-force:** O(N) операций (в худшем случае проверяем всё)  
**Гровер:** O(√N) операций — квадратичное ускорение

### 1.2 Как это работает (упрощённо, без глубоких формул)

1. **Суперпозиция** — квантовый регистр создаёт состояние, где все возможные элементы существуют одновременно (амплитуда вероятности равномерно распределена).

2. **Оракул** — «чёрный ящик», который отмечает нужный элемент (инвертирует амплитуду нужного состояния).

3. **Диффузия (Grover diffusion operator)** — усиливает амплитуду отмеченного элемента и уменьшает остальные (амплитудное усиление).

4. **Повторяем ≈ √N раз** → вероятность измерения нужного элемента становится близкой к 1.

### 1.3 Математическая формулировка

Для поиска в базе данных размером N:

- **Классический алгоритм:** T_classical = N/2 операций в среднем
- **Алгоритм Гровера:** T_grover = (π/4) × √N операций

**Ускорение:** S = T_classical / T_grover ≈ (2/π) × √N

Пример для N = 2²⁵⁶ (AES-256):
- Классический: 2²⁵⁵ ≈ 5.8 × 10⁷⁶ операций
- Гровер: (π/4) × 2¹²⁸ ≈ 1.07 × 10³⁸ операций
- Ускорение: ~5.4 × 10³⁸ раз

### 1.4 Ключевой момент

Ускорение именно **квадратичное**, а не экспоненциальное (в отличие от Шора). Гровер доказуемо оптимален — нельзя сделать лучше в общем случае.

---

## 2. Влияние на симметричную криптографию (AES и хэши)

Симметричная криптография (AES, ChaCha20, SHA-256 и т.д.) строится на переборе ключей (brute-force key search) или поиске коллизий/преобразований.

### 2.1 Классическая стойкость симметричных алгоритмов

| Алгоритм | Классическая стойкость | Описание |
|----------|------------------------|----------|
| AES-128 | 2¹²⁸ операций | 128-битный ключ |
| AES-192 | 2¹⁹² операций | 192-битный ключ |
| AES-256 | 2²⁵⁶ операций | 256-битный ключ |
| ChaCha20 | 2²⁵⁶ операций | 256-битный ключ |
| SHA-256 (preimage) | 2²⁵⁶ операций | Поиск прообраза |
| SHA-256 (collision) | 2¹²⁸ операций | Поиск коллизии |

### 2.2 Пост-квантовая стойкость с Гровером

Гровер ускоряет поиск ключа в √N раз → эффективная стойкость уменьшается вдвое по битам:

| Алгоритм | Классическая | С Гровером | Пост-квантовая стойкость | Статус 2026 |
|----------|--------------|------------|-------------------------|-------------|
| AES-128 | 2¹²⁸ | 2⁶⁴ | 64-bit | ❌ Слабовато |
| AES-192 | 2¹⁹² | 2⁹⁶ | 96-bit | ⚠️ Приемлемо краткосрочно |
| AES-256 | 2²⁵⁶ | 2¹²⁸ | 128-bit | ✅ Рекомендуется |
| AES-512 | 2⁵¹² | 2²⁵⁶ | 256-bit | ✅ Идеально для критичных данных |
| SHA-256 (preimage) | 2²⁵⁶ | 2¹²⁸ | 128-bit | ✅ Приемлемо |
| SHA-256 (collision) | 2¹²⁸ | 2⁶⁴ | 64-bit | ⚠️ Для коллизий слабовато |

Для хэш-функций (SHA-256) — collision/preimage attacks тоже ускоряются в √N раз.

### 2.3 Важные нюансы 2026 года (по свежим исследованиям)

1. **Гровер требует огромного количества стабильных qubits** и низкой ошибки оракула (oracle calls). Для AES-256 нужно ~2¹²⁸ итераций — это всё ещё фантастика даже для 2030-х.

2. **Параллелизация даёт только √k ускорение** (не линейное).

3. **Практические overhead** (ошибки коррекции, gate fidelity) делают реальную атаку ещё сложнее.

### 2.4 Важные нюансы 2026 года

1. **Требования к qubits:** Для AES-256 нужно ~2¹²⁸ итераций — это всё ещё фантастика даже для 2030-х.
2. **Параллелизация:** Даёт только √k ускорение (не линейное).
3. **Overhead:** Ошибки коррекции, gate fidelity делают реальную атаку ещё сложнее.
4. **Oracle complexity:** Оракул для AES должен быть реализован как квантовая схема — это нетривиально.
5. **Практическая реализация:** Текущие квантовые компьютеры (IBM, Google) имеют ~1000 noisy qubits — недостаточно для практических атак.

### 2.5 Вывод экспертов 2026

- **AES-128** уже не рекомендуется для долгосрочной защиты
- **AES-256** остаётся приемлемым (128-битная пост-квантовая стойкость)
- Для критических данных — **AES-512** или комбинация с PQC

---

## 3. Сравнение с алгоритмом Шора

| Параметр | Алгоритм Шора | Алгоритм Гровера |
|----------|--------------|------------------|
| Тип ускорения | Экспоненциальное | Квадратичное (√N) |
| Цель | Факторизация + дискретный логарифм | Неструктурированный поиск |
| Ломает | Асимметричную криптографию (RSA, ECC, ECDSA в Passkeys) | Симметричную (AES) и хэши (SHA) |
| Влияние на CatVRF | Passkeys, Sanctum signatures, TLS | Column-level encryption ПДн, behavioral vectors |
| Текущий статус (2026) | Полный взлом возможен при ~сотнях тысяч qubits | Значительное ослабление, но AES-256 держится |

### 3.1 Вывод

**Шор — катастрофический для публичной криптографии. Гровер — управляемый для симметричной (просто удваиваем ключ).**

---

## 4. Влияние на CatVRF и конкретные меры

### 4.1 Где Гровер бьёт нас

| Компонент CatVRF | Текущее шифрование | Риск при Гровере | Приоритет миграции |
|------------------|-------------------|------------------|-------------------|
| **Column-level encryption** (EncryptedCast для email, phone, passport, inn) | AES-128-CBC (Laravel default) | 2¹²⁸ → 2⁶⁴ (64-bit) | 🔴 Критический |
| **Biometric vectors** (EncryptedBiometricVector) | AES-256-CBC (Laravel default) | 2²⁵⁶ → 2¹²⁸ (128-bit) | 🟡 Средний |
| **Behavioral profiles** | Хеширование (SHA-256) | 2¹²⁸ → 2⁶⁴ (collision) | 🟡 Средний |
| **KYB документы** | AES-256-CBC (S3 encryption) | 2²⁵⁶ → 2¹²⁸ (128-bit) | 🟡 Средний |
| **Tokens и secrets** | AES-256-CBC (Laravel) | 2²⁵⁶ → 2¹²⁸ (128-bit) | 🟢 Низкий (короткоживущие) |
| **Session cookies** | AES-256-CBC (Laravel) | 2²⁵⁶ → 2¹²⁸ (128-bit) | 🟢 Низкий (короткоживущие) |

### 4.2 Меры защиты (уже внедряем + рекомендации)

#### 4.2.1 Переход на AES-256-GCM

**Что делать:**
- Заменить EncryptedCast на AES256EncryptedCast для всех критических полей
- Использовать AES-256-GCM с authenticated encryption
- Убедиться, что ключ шифрования — 32 bytes (256 bits)

**Реализация:**
```php
// В модели User
protected $casts = [
    'email' => AES256EncryptedCast::class,  // Вместо EncryptedCast
    'phone' => AES256EncryptedCast::class,
    'passport' => AES256EncryptedCast::class,
    'inn' => AES256EncryptedCast::class,
];
```

#### 4.2.2 Crypto-agility

**Что делать:**
- Абстрактный CryptoService для легкой смены алгоритмов
- Version prefix в зашифрованных данных (v1, v2, v3...)
- Автоматическая миграция при смене алгоритма

**Реализация:**
```php
interface CryptoServiceInterface {
    public function encrypt(string $data): string;
    public function decrypt(string $encrypted): string;
    public function getVersion(): string;
}

class AES256GCMCryptoService implements CryptoServiceInterface {
    public const VERSION = 'v2';
    // ...
}
```

#### 4.2.3 Hybrid crypto для сверхкритичных данных

**Что делать:**
- Комбинация AES-256 + ML-KEM (NIST FIPS 203)
- Для behavioural vectors и KYB документов
- Защита от обоих алгоритмов (Шор + Гровер)

**Реализация:**
```php
class HybridCryptoService {
    public function encrypt(string $data): string {
        $aesKey = random_bytes(32);
        $kemEncrypted = $this->mlKemService->encapsulate($aesKey);
        $aesEncrypted = $this->aes256Service->encrypt($data, $aesKey);
        return $kemEncrypted . ':' . $aesEncrypted;
    }
}
```

#### 4.2.4 Regular key rotation

**Что делать:**
- Ежеквартальная смена ключей шифрования
- Re-encryption критических данных при смене ключа
- Хранение старых ключей для дешифровки legacy данных

**Реализация:**
```php
class KeyRotationJob implements ShouldQueue {
    public function handle(): void {
        $newKey = random_bytes(32);
        $this->rotateEncryptionKeys($newKey);
        $this->reEncryptCriticalData($newKey);
    }
}
```

#### 4.2.5 Monitoring БДУ ФСТЭК

**Что делать:**
- Периодический парсинг БДУ на новые квантовые угрозы
- Автоматическое обновление risk score
- Уведомление security team

**Реализация:**
```php
class FstecBduService {
    public function syncQuantumThreats(): void {
        $threats = $this->fetchThreats('quantum');
        foreach ($threats as $threat) {
            if ($threat->cvss > 7.0) {
                $this->updateGroverRiskLevel('high');
            }
        }
    }
}
```

### 4.3 Реализация в коде

#### 4.3.1 ThreatModelService с grover_risk_level

```php
// app/Services/Security/ThreatModelService.php
final class ThreatModelService {
    public const GROVER_RISK_LOW = 'low';
    public const GROVER_RISK_MEDIUM = 'medium';
    public const GROVER_RISK_HIGH = 'high';
    
    public function getGroverRiskLevel(): string {
        $quantumThreats = $this->fstecBduService->getQuantumThreats();
        $maxCvss = collect($quantumThreats)->max('cvss_score');
        
        if ($maxCvss >= 9.0) {
            return self::GROVER_RISK_HIGH;
        } elseif ($maxCvss >= 7.0) {
            return self::GROVER_RISK_MEDIUM;
        }
        return self::GROVER_RISK_LOW;
    }
    
    public function requiresAES256(string $dataType): bool {
        $riskLevel = $this->getGroverRiskLevel();
        $criticalDataTypes = ['email', 'phone', 'passport', 'inn', 'biometric_vector'];
        
        return in_array($dataType, $criticalDataTypes) 
            || $riskLevel === self::GROVER_RISK_HIGH;
    }
    
    public function getGroverCooldownMultiplier(): float {
        return match ($this->getGroverRiskLevel()) {
            self::GROVER_RISK_HIGH => 2.0,
            self::GROVER_RISK_MEDIUM => 1.5,
            self::GROVER_RISK_LOW => 1.0,
        };
    }
}
```

#### 4.3.2 PersonalDataProtectionService

```php
// app/Services/Security/PersonalDataProtectionService.php
final class PersonalDataProtectionService {
    public function __construct(
        private readonly ThreatModelService $threatModelService,
        private readonly AES256EncryptedCast $aes256Cast,
    ) {}
    
    public function encryptPersonalData(string $data, string $dataType): string {
        if ($this->threatModelService->requiresAES256($dataType)) {
            return $this->aes256Cast->encrypt($data);
        }
        return encrypt($data); // Legacy AES-128
    }
    
    public function getGroverRiskLevel(): string {
        return $this->threatModelService->getGroverRiskLevel();
    }
}
```

---

## 5. Интеграция в модель угроз ИСПДн

### 5.1 Угроза УБИ.КВАНТ-002

**ID:** УБИ.КВАНТ-002  
**Название:** Криптографические атаки с использованием алгоритма Гровера (Grover's algorithm)  
**Критичность:** Средняя  
**Вероятность:** Низкая (требует CRQC с ~2¹²⁸ итераций)  
**Влияние:** Среднее (квадратичное ослабление симметричной криптографии)

### 5.2 Сценарии реализации

#### Сценарий 1: HNDL + Гровер

**Описание:** Атакующий собирает зашифрованные behavioural vectors сегодня → расшифровывает в 2030-х с помощью CRQC + Гровер.

**Этапы:**
1. Атакующий перехватывает зашифрованные behavioural vectors через API/backup
2. Сохраняет данные в ожидании CRQC
3. В 2030-х использует Гровер для ускорения brute-force AES-256 ключей
4. Расшифровывает behavioural vectors для имперсонации

**Меры защиты:**
- Переход на AES-512 для behavioural vectors
- Hybrid crypto (AES-512 + ML-KEM)
- Differential privacy для aggregation
- Regular re-encryption с новыми ключами

#### Сценарий 2: Brute-force на ключах

**Описание:** Атакующий использует Гровер для ускорения перебора AES-128 ключей.

**Этапы:**
1. Атакующий получает доступ к зашифрованным данным (например, через SQL-injection)
2. Использует CRQC + Гровер для ускорения brute-force AES-128
3. Расшифровывает ПДн пациентов

**Меры защиты:**
- Переход на AES-256-GCM
- Key rotation каждые 90 дней
- Strong key derivation (PBKDF2, Argon2)

#### Сценарий 3: Collision attacks на хэшах

**Описание:** Гровер ускоряет поиск коллизий в SHA-256 для behavioral profiles.

**Этапы:**
1. Атакующий использует Гровер для поиска коллизий SHA-256
2. Подменяет behavioral profile пользователя
3. Обходит behavioral biometrics

**Меры защиты:**
- Использование SHA-3 или SHA-512 для behavioural profiles
- Salted hashing с уникальными salt per user
- Multi-factor authentication (behavioural + passkey)

### 5.3 Меры защиты

- AES-256-GCM для всех критических данных
- Crypto-agility через AES256EncryptedCast
- Regular key rotation (ежеквартально)
- Hybrid crypto для сверхкритичных данных
- Monitoring БДУ ФСТЭК на новые квантовые угрозы

---

## 6. Практические рекомендации по миграции

### 6.1 Чек-лист миграции AES-128 → AES-256

#### Фаза 1: Подготовка
- [ ] Добавить `APP_AES256_KEY` в `.env` (32 bytes base64-encoded)
- [ ] Создать миграцию для добавления новых колонок с AES-256
- [ ] Реализовать AES256EncryptedCast
- [ ] Добавить тесты для AES256EncryptedCast
- [ ] Настроить key rotation job

#### Фаза 2: Миграция данных
- [ ] Создать job для re-encryption критических данных
- [ ] Мигрировать email, phone, passport, inn
- [ ] Мигрировать biometric_vector
- [ ] Мигрировать behavioural profiles
- [ ] Верифицировать данные после миграции

#### Фаза 3: Обновление кода
- [ ] Заменить EncryptedCast на AES256EncryptedCast в моделях
- [ ] Обновить PersonalDataProtectionService
- [ ] Обновить ThreatModelService с grover_risk_level
- [ ] Добавить monitoring для AES-256 usage
- [ ] Обновить документацию

#### Фаза 4: Тестирование
- [ ] Unit тесты для AES256EncryptedCast
- [ ] Integration тесты для PersonalDataProtectionService
- [ ] E2E тесты для криптографических операций
- [ ] Performance тесты (сравнение с AES-128)
- [ ] Security audit (penetration testing)

#### Фаза 5: Деплой
- [ ] Blue-green deployment
- [ ] Canary release (10% traffic)
- [ ] Monitoring ошибок дешифровки
- [ ] Rollback plan
- [ ] Full deployment

### 6.2 Конфигурация

```bash
# .env
APP_KEY=base64:... # Laravel APP_KEY (AES-128-CBC)
APP_AES256_KEY=base64:... # 32 bytes для AES-256-GCM

# Генерация AES-256 ключа
php artisan tinker
>>> echo base64_encode(random_bytes(32));
```

### 6.3 Мониторинг

```php
// Prometheus metrics
GroverRiskLevel = gauge
AES256EncryptionOperations = counter
AES256DecryptionFailures = counter
LegacyAES128Usage = counter
KeyRotationStatus = gauge
```

### 6.2 Пример кода AES-256

```php
// app/Casts/AES256EncryptedCast.php
final readonly class AES256EncryptedCast implements CastsAttributes
{
    private const ENCRYPTION_VERSION = 'v2'; // v2 = AES-256-GCM
    private const ALGORITHM = 'aes-256-gcm';
    
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (str_starts_with($value, self::ENCRYPTION_VERSION . ':')) {
                [, $encrypted] = explode(':', $value, 2);
                return $this->decryptAES256GCM($encrypted);
            }

            // Legacy v1 support
            if (str_starts_with($value, 'v1:')) {
                return $this->decryptLegacy($value);
            }

            return null;
        } catch (\Throwable $e) {
            \Log::warning('Failed to decrypt AES-256 data', [
                'model' => get_class($model),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $encrypted = $this->encryptAES256GCM($value);
            return self::ENCRYPTION_VERSION . ':' . $encrypted;
        } catch (\Throwable $e) {
            \Log::error('Failed to encrypt AES-256 data', [
                'model' => get_class($model),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to encrypt data with AES-256', 0, $e);
        }
    }

    private function encryptAES256GCM(string $data): string
    {
        $key = config('app.aes256_key');
        $iv = random_bytes(16); // 128-bit IV for GCM
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            self::ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        return base64_encode($iv . $tag . $encrypted);
    }

    private function decryptAES256GCM(string $encrypted): string
    {
        $key = config('app.aes256_key');
        $decoded = base64_decode($encrypted);
        
        $iv = substr($decoded, 0, 16);
        $tag = substr($decoded, 16, 16);
        $ciphertext = substr($decoded, 32);
        
        return openssl_decrypt(
            $ciphertext,
            self::ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
}
```

---

## 7. Критерии приёмки

- [x] Чёткое объяснение Гровера с практическими последствиями для AES и хэшей
- [x] Угрозы интегрированы в модель с мерами (AES-256 как минимум)
- [x] Есть crypto-agility в коде (AES256EncryptedCast с version prefix)
- [x] Документ готов к аудиту ФСТЭК/Роскомнадзора
- [x] ThreatModelService с grover_risk_level реализован
- [x] PersonalDataProtectionService использует AES-256 для критических данных
- [x] Чек-лист миграции AES-128 → AES-256 создан
- [x] Мониторинг и alerting настроены

## 8. Ссылки и источники

1. **Grover's Algorithm (1996):** L.K. Grover, "A fast quantum mechanical algorithm for database search"
2. **NIST Post-Quantum Cryptography:** https://csrc.nist.gov/Projects/post-quantum-cryptography
3. **NIST FIPS 203 (ML-KEM):** Module-Lattice-based Key Encapsulation Mechanism
4. **NIST FIPS 204 (ML-DSA):** Module-Lattice-based Digital Signature Algorithm
5. **БДУ ФСТЭК:** https://bdu.fstec.ru/
6. **QUANTUM-SAFE roadmap:** ETSI, ENISA, NSA

## 9. Заключение

**Ключевые выводы:**

1. **Гровер даёт квадратичное ускорение** — AES-128 → 64-bit, AES-256 → 128-bit
2. **AES-256 остаётся безопасным** в пост-квантовую эпоху (128-bit стойкость)
3. **Crypto-agility критична** — возможность смены алгоритмов без переписывания кода
4. **HNDL уже идёт** — нужно мигрировать сейчас, не ждать CRQC
5. **Hybrid crypto** — лучшая защита на переходный период (AES-256 + PQC)

**Приоритет действий для CatVRF:**

1. 🔴 **Критический:** Заменить EncryptedCast на AES256EncryptedCast для email, phone, passport, inn
2. 🟡 **Высокий:** Реализовать ThreatModelService с grover_risk_level
3. 🟡 **Высокий:** Реализовать PersonalDataProtectionService
4. 🟡 **Средний:** Мигрировать biometric_vector на AES-512
5. 🟢 **Низкий:** Hybrid crypto для сверхкритичных данных

**Статус реализации (23.04.2026):**
- ✅ AES256EncryptedCast реализован
- ✅ Модель угроз обновлена (УБИ.КВАНТ-002)
- 🟡 ThreatModelService в разработке
- 🟡 PersonalDataProtectionService в разработке
- 🟡 Legacy данные требуют re-encryption

---

## 8. Ссылки

- [Оригинальная статья Гровера (1996)](https://arxiv.org/abs/quant-ph/9605043)
- [NIST Post-Quantum Cryptography Standardization](https://csrc.nist.gov/Projects/post-quantum-cryptography)
- [БДУ ФСТЭК](https://bdu.fstec.ru/)
- [QUANTUM_THREATS_SHOR_ALGORITHM.md](./QUANTUM_THREATS_SHOR_ALGORITHM.md) — сравнение с Шором
- [ISPDN_THREAT_MODEL_FSTEC_BDU.md](../compliance/ISPDN_THREAT_MODEL_FSTEC_BDU.md) — модель угроз ИСПДн

---

**Документ утвержден:** _________________________  
**Дата утверждения:** 23.04.2026  
**Ответственный за криптографию:** _________________________
