<?php

declare(strict_types=1);

/**
 * Crypto Configuration (Post-Quantum Resistant)
 *
 * Централизованная конфигурация всех криптографических операций в CatVRF.
 * Учитывает post-quantum угрозы: Grover's algorithm (квадратичное ослабление хэшей)
 * и Shor's algorithm (ломает RSA/ECC).
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * ФСТЭК №21: Мера 11 - Шифрование
 * УБИ.КВАНТ-001: Post-Quantum Threats
 * УБИ.КВАНТ-002: Grover's algorithm mitigation
 * УБИ.КВАНТ-003: Shor's algorithm mitigation
 *
 * @see docs/security/QUANTUM_THREATS_GROVER_ALGORITHM.md
 * @see docs/security/QUANTUM_THREATS_SHOR_ALGORITHM.md
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Pepper for Hashing
    |--------------------------------------------------------------------------
    |
    | Секретный ключ приложения для усиления хэширования.
    | Добавляется к данным перед хэшированием (email, phone, behavioral vectors).
    | Предотвращает rainbow table атаки.
    |
    | Генерация: php artisan key:generate --show
    | Или: base64_encode(random_bytes(32))
    |
    | ВАЖНО: Храните pepper в защищённом хранилище (AWS Secrets Manager, Azure Key Vault).
    | Никогда не коммитьте pepper в git.
    */
    'pepper' => env('CRYPTO_PEPPER', null),

    /*
    |--------------------------------------------------------------------------
    | Pepper Rotation
    |--------------------------------------------------------------------------
    |
    | Поддержка ротации pepper для crypto-agility.
    | При ротации старый pepper остаётся в legacy_peppers для валидации старых данных.
    */
    'legacy_peppers' => array_filter(explode(',', env('CRYPTO_LEGACY_PEPPERS', ''))),

    /*
    |--------------------------------------------------------------------------
    | Password Hashing Algorithm
    |--------------------------------------------------------------------------
    |
    | Алгоритм хэширования паролей.
    |
    | Рекомендации:
    | - PASSWORD_ARGON2ID: memory-hard, устойчив к Grover лучше bcrypt
    | - PASSWORD_BCRYPT: legacy, медленнее при атаке GPU/ASIC
    |
    | Post-Quantum: Argon2id + pepper обеспечивает ~128-битную защиту
    | против Grover (эквивалент классического 256-битного уровня).
    */
    'password_algorithm' => env('CRYPTO_PASSWORD_ALGORITHM', 'argon2id'),

    /*
    |--------------------------------------------------------------------------
    | Argon2id Parameters
    |--------------------------------------------------------------------------
    |
    | Параметры для Argon2id (memory-hard KDF).
    |
    | - memory_cost: 19456 KiB (~19 MB) - защищает от GPU/ASIC атак
    | - time_cost: 2 итерации - баланс между производительностью и безопасностью
    | - threads: 1 поток - детерминированный результат
    |
    | Post-Quantum: Увеличение memory_cost до 64 MB рекомендуется при
    | высоком Grover risk level.
    */
    'argon2id' => [
        'memory_cost' => (int) env('CRYPTO_ARGON2ID_MEMORY', 19456),
        'time_cost' => (int) env('CRYPTO_ARGON2ID_TIME', 2),
        'threads' => (int) env('CRYPTO_ARGON2ID_THREADS', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Hashing Algorithm
    |--------------------------------------------------------------------------
    |
    | Алгоритм хэширования email/phone для UniqueContactService.
    |
    | - sha256: быстрый, 256-битный вывод, устойчив к коллизиям
    | - Grover mitigation: SHA-256 + pepper даёт ~128-бит post-quantum защиту
    |
    | ВАЖНО: Храним только хэш, plaintext не сохраняется в БД.
    */
    'contact_hash_algorithm' => env('CRYPTO_CONTACT_HASH_ALGORITHM', 'sha256'),

    /*
    |--------------------------------------------------------------------------
    | Behavioral Vector Hashing
    |--------------------------------------------------------------------------
    |
    | Алгоритм хэширования поведенческих векторов.
    |
    | - sha256: хэширование сериализованного вектора + user_salt + pepper
    | - Сравнение similarity делается на хэшированных представлениях
    |
    | Post-Quantum: SHA-256 + per-user salt + pepper обеспечивает защиту
    | от rainbow table и Grover attacks.
    */
    'behavioral_hash_algorithm' => env('CRYPTO_BEHAVIORAL_HASH_ALGORITHM', 'sha256'),

    /*
    |--------------------------------------------------------------------------
    | HMAC Algorithm
    |--------------------------------------------------------------------------
    |
    | Алгоритм для HMAC (integrity checking, token signing).
    |
    | - sha256: стандарт для HMAC, 256-битный тег
    | - Grover mitigation: HMAC-SHA256 даёт ~128-бит post-quantum защиту
    |
    | Используется для:
    | - Подписи API токенов
    | - Integrity проверок audit logs
    | - Webhook signatures
    */
    'hmac_algorithm' => env('CRYPTO_HMAC_ALGORITHM', 'sha256'),

    /*
    |--------------------------------------------------------------------------
    | AES-256 Key
    |--------------------------------------------------------------------------
    |
    | 32-байтный ключ для AES-256-GCM шифрования.
    |
    | Генерация: base64_encode(random_bytes(32))
    |
    | ВАЖНО: AES-256-GCM обеспечивает 128-бит post-quantum защиту
    | (Grover снижает с 256 до 128 бит, что всё ещё безопасно).
    |
    | Храните ключ в защищённом хранилище (AWS KMS, Azure Key Vault).
    */
    'aes256_key' => env('APP_AES256_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Encryption Version
    |--------------------------------------------------------------------------
    |
    | Текущая версия шифрования для crypto-agility.
    |
    | - v1: Laravel default (AES-128-CBC) - legacy
    | - v2: AES-256-GCM (post-quantum resistant) - current
    |
    | При ротации алгоритма увеличивайте версию и добавьте поддержку
    | дешифрования старых версий в cast-ах.
    */
    'encryption_version' => env('CRYPTO_ENCRYPTION_VERSION', 'v2'),

    /*
    |--------------------------------------------------------------------------
    | Quantum Risk Level
    |--------------------------------------------------------------------------
    |
    | Уровень квантовой угрозы для автоматической адаптации криптографии.
    |
    | - low: классические угрозы, стандартная криптография
    | - medium: появляются квантовые компьютеры, усиление параметров
    | - high: значительные квантовые вычисления, переход на PQC
    | - critical: Shor's algorithm ломает RSA/ECC, полный переход на PQC
    |
    | Автоматически определяется ThreatModelService на основе:
    | - Интеллекта о квантовых вычислениях
    | - NIST PQC стандартизации
    | - Рекомендаций ФСТЭК/NSA
    */
    'quantum_risk_level' => env('CRYPTO_QUANTUM_RISK_LEVEL', 'low'),

    /*
    |--------------------------------------------------------------------------
    | Quantum Risk Thresholds
    |--------------------------------------------------------------------------
    |
    | Пороги для определения quantum risk level.
    |
    | Эти значения используются ThreatModelService для автоматической
    | оценки угрозы и адаптации параметров криптографии.
    */
    'quantum_risk_thresholds' => [
        'medium' => [
            'grover_qubits' => 1000,      // Квантовых бит для Grover
            'shor_qubits' => 10000,       // Квантовых бит для Shor
            'years_to_break' => 10,       // Лет до взлома текущей криптографии
        ],
        'high' => [
            'grover_qubits' => 10000,
            'shor_qubits' => 100000,
            'years_to_break' => 5,
        ],
        'critical' => [
            'grover_qubits' => 100000,
            'shor_qubits' => 1000000,
            'years_to_break' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Adaptive Parameters by Risk Level
    |--------------------------------------------------------------------------
    |
    | Автоматическая адаптация параметров криптографии на основе quantum risk.
    |
    | При повышении risk level:
    | - Увеличивается memory_cost для Argon2id
    | - Усиливается pepper rotation
    | - Рекомендуется переход на PQC алгоритмы
    */
    'adaptive_parameters' => [
        'low' => [
            'argon2id_memory' => 19456,
            'argon2id_time' => 2,
            'contact_hash' => 'sha256',
            'encryption' => 'aes-256-gcm',
        ],
        'medium' => [
            'argon2id_memory' => 65536,   // 64 MB
            'argon2id_time' => 3,
            'contact_hash' => 'sha256',
            'encryption' => 'aes-256-gcm',
        ],
        'high' => [
            'argon2id_memory' => 131072,  // 128 MB
            'argon2id_time' => 4,
            'contact_hash' => 'sha3-256', // SHA-3 when available
            'encryption' => 'aes-256-gcm',
        ],
        'critical' => [
            'argon2id_memory' => 262144,  // 256 MB
            'argon2id_time' => 5,
            'contact_hash' => 'shake256', // SHAKE-256 (PQC candidate)
            'encryption' => 'ml-kem-1024', // NIST PQC when available
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Post-Quantum Cryptography (PQC) Readiness
    |--------------------------------------------------------------------------
    |
    | Конфигурация для перехода на post-quantum алгоритмы (NIST PQC 2024).
    |
    | - ml-kem: NIST FIPS 203 (Key Encapsulation Mechanism)
    | - ml-dsa: NIST FIPS 204 (Digital Signature Algorithm)
    |
    | ВАЖНО: PQC библиотеки будут добавлены когда станут доступными
    | стабильные реализации (liboqs, pqcrypto, etc.).
    */
    'pqc' => [
        'enabled' => env('CRYPTO_PQC_ENABLED', false),
        'kem_algorithm' => env('CRYPTO_PQC_KEM', 'ml-kem-1024'),
        'dsa_algorithm' => env('CRYPTO_PQC_DSA', 'ml-dsa-65'),
        'hybrid_mode' => env('CRYPTO_PQC_HYBRID', true), // Классический + PQC
    ],

    /*
    |--------------------------------------------------------------------------
    | Key Rotation
    |--------------------------------------------------------------------------
    |
    | Периодичность ротации ключей.
    |
    | - pepper: каждые 90 дней
    | - aes256_key: каждые 365 дней
    | - hmac_key: каждые 180 дней
    |
    | ВАЖНО: Ротация должна выполняться без downtime (blue-green deployment).
    */
    'key_rotation' => [
        'pepper_days' => (int) env('CRYPTO_PEPPER_ROTATION_DAYS', 90),
        'aes256_key_days' => (int) env('CRYPTO_AES256_ROTATION_DAYS', 365),
        'hmac_key_days' => (int) env('CRYPTO_HMAC_ROTATION_DAYS', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Логирование криптографических операций для аудита.
    |
    | Включает логирование:
    | - Хэширования контактов
    | - Шифрования/дешифрования ПДн
    | - Ротации ключей
    | - Ошибок криптографии
    */
    'audit_logging' => [
        'enabled' => env('CRYPTO_AUDIT_LOGGING', true),
        'log_hashing' => env('CRYPTO_LOG_HASHING', true),
        'log_encryption' => env('CRYPTO_LOG_ENCRYPTION', true),
        'log_decryption_failures' => env('CRYPTO_LOG_DECRYPTION_FAILURES', true),
        'log_key_rotation' => env('CRYPTO_LOG_KEY_ROTATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Types Requiring Special Protection
    |--------------------------------------------------------------------------
    |
    | Типы данных, требующие усиленной защиты (AES-256-GCM, SHA-256+pepper).
    |
    | Эти данные автоматически шифруются AES-256-GCM при high/critical
    | quantum risk level.
    */
    'protected_data_types' => [
        'biometric_vector',
        'behavioral_profile',
        'passport',
        'inn',
        'snils',
        'medical_records',
        'financial_data',
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    |
    | Настройки миграции на новые криптографические схемы.
    |
    | - batch_size: количество записей за одну итерацию
    | - throttle_delay: задержка между итерациями (ms)
    */
    'migration' => [
        'batch_size' => (int) env('CRYPTO_MIGRATION_BATCH_SIZE', 1000),
        'throttle_delay' => (int) env('CRYPTO_MIGRATION_THROTTLE', 100),
        'dry_run' => env('CRYPTO_MIGRATION_DRY_RUN', false),
    ],
];
