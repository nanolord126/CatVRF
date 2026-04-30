# Чек-лист аудита квантовых угроз и PQC миграции

**Версия:** 1.0  
**Дата:** 23.04.2026  
**Проект:** CatVRF — AI-powered Healthcare Marketplace

---

## 1. Проверка модели угроз

### 1.1 Документация

- [ ] Модель угроз ИСПДн включает раздел квантовых угроз (УБИ.КВАНТ-001–004)
- [ ] Детальное описание алгоритма Шора (QUANTUM_THREATS_SHOR_ALGORITHM.md)
- [ ] Актуальные оценки ресурсов на 2026 год включены
- [ ] Релевантность для CatVRF описана (Passkeys, Sanctum, encrypted ПДн)
- [ ] Сценарии реализации и последствия описаны
- [ ] Меры защиты по Приказу №21 указаны (М.11 — шифрование)

**Проверка:**
```bash
# Проверить наличие документов
ls -la docs/security/QUANTUM_THREATS_SHOR_ALGORITHM.md
ls -la docs/compliance/ISPDN_THREAT_MODEL_FSTEC_BDU.md

# Проверить наличие квантовых угроз в модели
grep -n "КВАНТ" docs/compliance/ISPDN_THREAT_MODEL_FSTEC_BDU.md
```

### 1.2 Интеграция в ThreatModelService

- [ ] Метод `getQuantumRiskLevel()` реализован
- [ ] Метод `getQuantumRiskCategory()` реализован
- [ ] Метод `getQuantumResponseActions()` реализован
- [ ] Метод `getQuantumThreats()` реализован (private)
- [ ] Константы риск-уровней определены (LOW, MEDIUM, HIGH, CRITICAL)
- [ ] Quantum risk кэшируется (TTL = 1 час)
- [ ] Год-based progression риска реализован (2027+ HIGH, 2029+ CRITICAL)

**Проверка:**
```bash
# Проверить наличие методов в ThreatModelService
grep -n "getQuantumRiskLevel\|getQuantumRiskCategory\|getQuantumResponseActions" app/Services/Security/ThreatModelService.php

# Проверить константы
grep -n "QUANTUM_RISK_" app/Services/Security/ThreatModelService.php

# Запустить тест
php artisan test --filter ThreatModelService
```

---

## 2. Проверка Hybrid Crypto

### 2.1 HybridCryptoService

- [ ] Класс HybridCryptoService реализован
- [ ] Метод `encryptHybrid()` реализован с quantum risk check
- [ ] Метод `decryptHybrid()` реализован с auto-detection
- [ ] Метод `signHybrid()` реализован
- [ ] Метод `verifyHybrid()` реализован
- [ ] Метод `generateHybridJwt()` реализован
- [ ] Метод `verifyHybridJwt()` реализован
- [ ] Метод `getCryptoConfiguration()` реализован
- [ ] Версионирование шифрования (v1: classical, v2: hybrid)
- [ ] Priority-based encryption (biometrics > PII > KYB > tokens)
- [ ] Fallback на classical при ошибках PQC

**Проверка:**
```bash
# Проверить наличие класса
ls -la app/Services/Security/HybridCryptoService.php

# Проверить методы
grep -n "encryptHybrid\|decryptHybrid\|signHybrid\|verifyHybrid" app/Services/Security/HybridCryptoService.php

# Запустить тест
php artisan test --filter HybridCryptoService
```

### 2.2 Тестирование HybridCryptoService

- [ ] Unit-тесты для encrypt/decrypt (classical)
- [ ] Unit-тесты для encrypt/decrypt (hybrid)
- [ ] Unit-тесты для sign/verify (classical)
- [ ] Unit-тесты для sign/verify (hybrid)
- [ ] Unit-тесты для JWT (classical)
- [ ] Unit-тесты для JWT (hybrid)
- [ ] Performance тесты (latency < 50ms)
- [ ] Compatibility тесты с существующими данными

**Пример теста:**
```php
// tests/Unit/Security/HybridCryptoServiceTest.php
test('encrypts and decrypts data with hybrid encryption', function () {
    $service = app(HybridCryptoService::class);
    $plaintext = 'sensitive_data';
    
    $encrypted = $service->encryptHybrid($plaintext, 'critical');
    $decrypted = $service->decryptHybrid($encrypted);
    
    expect($decrypted)->toBe($plaintext);
});

test('quantum risk level affects encryption mode', function () {
    $service = app(HybridCryptoService::class);
    
    // Force hybrid encryption
    $encrypted = $service->encryptHybrid('data', 'critical', forceHybrid: true);
    $metadata = $service->getEncryptionMetadata($encrypted);
    
    expect($metadata['is_quantum_resistant'])->toBeTrue();
});
```

---

## 3. Проверка ThreatModelService

### 3.1 Quantum Risk Scoring

- [ ] `getQuantumRiskLevel()` возвращает 0-3
- [ ] `getQuantumRiskCategory()` возвращает 'low'/'medium'/'high'/'critical'
- [ ] Risk кэшируется на 1 час
- [ ] Year-based progression работает
- [ ] Quantum threats из БД учитываются
- [ ] External indicators placeholder готов

**Проверка:**
```bash
# Запустить artisan команду для проверки
php artisan tinker
>>> app(\App\Services\Security\ThreatModelService::class)->getQuantumRiskLevel()
>>> app(\App\Services\Security\ThreatModelService::class)->getQuantumRiskCategory()
```

### 3.2 Quantum Response Actions

- [ ] `getQuantumResponseActions()` возвращает actions для CRITICAL risk
- [ ] Actions включают: force_hybrid_encryption, reencrypt_critical_data, disable_classical_only
- [ ] Actions включают: require_mfa_all, executive_notification
- [ ] Actions для HIGH risk включают: trigger_cooldown, require_fresh_passkey
- [ ] Actions включают: increase_behavioral_sampling, security_team_alert

**Проверка:**
```bash
# Проверить response actions
php artisan tinker
>>> app(\App\Services\Security\ThreatModelService::class)->getQuantumResponseActions()
```

### 3.3 Compliance Report

- [ ] `getComplianceReport()` включает quantum_threats_monitored
- [ ] Report включает quantum_risk_level
- [ ] Report включает quantum_risk_category
- [ ] Report включает pqc_migration_status

**Проверка:**
```bash
# Проверить compliance report
php artisan tinker
>>> app(\App\Services\Security\ThreatModelService::class)->getComplianceReport()
```

---

## 4. Проверка УЗ-3 акта

### 4.1 Квантовые угрозы в акте

- [ ] Раздел 2.4 "Критерий 4: Квантовые угрозы" добавлен
- [ ] 4 квантовые угрозы описаны (УБИ.КВАНТ-001–004)
- [ ] Актуальные оценки ресурсов на 2026 год включены
- [ ] Релевантность для CatVRF описана
- [ ] Доказательства реализации указаны
- [ ] Меры защиты описаны (crypto-agility, hybrid crypto, PQC roadmap)
- [ ] Заключение: "Требуется УЗ-3 или выше"

**Проверка:**
```bash
# Проверить наличие раздела в акте
grep -n "Критерий 4: Квантовые угрозы" docs/compliance/UZ3_ACT_TEMPLATE.md
grep -n "УБИ.КВАНТ" docs/compliance/UZ3_ACT_TEMPLATE.md
```

---

## 5. Проверка PQC Migration Roadmap

### 5.1 Документация

- [ ] Документ PQC_MIGRATION_ROADMAP_2026_2030.md создан
- [ ] 5 фаз миграции описаны
- [ ] Текущее состояние (2026 Q1) описано
- [ ] Критерии завершения для каждой фазы
- [ ] Риски и митигации описаны
- [ ] Бюджет на 5 лет ($390k)
- [ ] Метрики успеха определены
- [ ] План отката (Rollback Plan)

**Проверка:**
```bash
# Проверить наличие документа
ls -la docs/security/PQC_MIGRATION_ROADMAP_2026_2030.md

# Проверить структуру
grep -n "Фаза \|Критерии завершения\|Риски\|Бюджет" docs/security/PQC_MIGRATION_ROADMAP_2026_2030.md
```

---

## 6. Проверка интеграции с существующими системами

### 6.1 Passkeys/WebAuthn

- [ ] WebAuthnRegistrationService поддерживает hybrid signing
- [ ] WebAuthnAuthenticationService поддерживает hybrid verification
- [ ] Metadata о версии криптографии сохраняется
- [ ] Backward compatibility с classical Passkeys

**Проверка:**
```bash
# Проверить WebAuthn сервисы
grep -n "HybridCryptoService\|hybrid" app/Services/Auth/WebAuthn/*.php
```

### 6.2 Sanctum Tokens

- [ ] JWT токены используют hybrid signing при high quantum risk
- [ ] Quantum risk level включён в payload
- [ ] TTL токенов уменьшен (5-15 минут)
- [ ] Refresh token rotation реализован

**Проверка:**
```bash
# Проверить JWT генерацию
grep -n "HybridCryptoService\|quantum_risk" app/Providers/AuthServiceProvider.php
```

### 6.3 Column-level Encryption

- [ ] EncryptedCast использует HybridCryptoService
- [ ] Priority-based encryption для разных типов данных
- [ ] Metadata об алгоритме шифрования сохраняется
- [ ] Backward compatibility с classical encryption

**Проверка:**
```bash
# Проверить EncryptedCast
grep -n "HybridCryptoService\|encryptHybrid" app/Casts/EncryptedCast.php
```

### 6.4 Behavioral Biometrics

- [ ] Behavioral vectors используют AES-256-GCM (симметричная криптография)
- [ ] Key derivation через KDF (PBKDF2, Argon2)
- [ ] Additional entropy (device fingerprint, IP geolocation)
- [ ] Continuous re-sampling

**Проверка:**
```bash
# Проверить behavioral biometrics
grep -n "AES-256\|KDF\|Argon2" app/Services/Security/BehavioralBiometricsService.php
```

---

## 7. Security Audit

### 7.1 Code Review

- [ ] HybridCryptoService прошел security code review
- [ ] ThreatModelService прошел security code review
- [ ] Нет hardcoded ключей
- [ ] Нет insecure random (используется random_bytes, sodium)
- [ ] Proper error handling без утечки информации
- [ ] Audit logging для всех криптографических операций

**Проверка:**
```bash
# Проверить hardcoded ключи
grep -r "private_key\|secret_key\|API_KEY" app/Services/Security/ --exclude-dir=vendor

# Проверить insecure random
grep -r "rand\|mt_rand" app/Services/Security/ --exclude-dir=vendor
```

### 7.2 Dependency Audit

- [ ] liboqs или php-pqc добавлен в composer.json
- [ ] Sodium extension установлен
- [ ] OpenSSL version актуален
- [ ] Нет уязвимых зависеностей (composer audit)

**Проверка:**
```bash
# Проверить composer.json
grep -n "liboqs\|php-pqc\|sodium" composer.json

# Проверить sodium
php -m | grep sodium

# Проверить уязвимости
composer audit
```

---

## 8. Performance Testing

### 8.1 Latency Tests

- [ ] Hybrid encryption latency < 50ms
- [ ] Hybrid decryption latency < 50ms
- [ ] Hybrid signing latency < 30ms
- [ ] Hybrid verification latency < 30ms
- [ ] Classical operations baseline измерен

**Проверка:**
```bash
# Запустить performance тест
php artisan test --filter HybridCryptoPerformanceTest
```

### 8.2 Throughput Tests

- [ ] 1000 encryption/sec для hybrid
- [ ] 1000 decryption/sec для hybrid
- [ ] 2000 signing/sec для hybrid
- [ ] 2000 verification/sec для hybrid

**Проверка:**
```bash
# Запустить нагрузочный тест
k6 run k6/quantum-crypto-load-test.js
```

---

## 9. Compliance Testing

### 9.1 ФСТЭК Приказ №21

- [ ] Мера М.11 (шифрование) реализована с PQC
- [ ] Crypto-agility документирован
- [ ] Key rotation реализован
- [ ] Audit logging для криптографических операций

### 9.2 152-ФЗ

- [ ] ПДн зашифрованы с quantum-safe алгоритмами
- [ ] Хранение ключей на территории РФ
- [ ] Consent на обработку ПДн включает криптографию

### 9.3 NIST Standards

- [ ] ML-KEM (Kyber) соответствует NIST FIPS 203
- [ ] ML-DSA (Dilithium) соответствует NIST FIPS 204
- [ ] Используются NIST-approved параметры

---

## 10. Monitoring and Alerting

### 10.1 Metrics

- [ ] PQC usage rate в Prometheus
- [ ] Quantum risk level в Prometheus
- [ ] Hybrid crypto latency в Prometheus
- [ ] Encryption/decryption error rate

**Проверка:**
```bash
# Проверить metrics
grep -n "quantum\|pqc\|hybrid" app/Services/Metrics/*.php
```

### 10.2 Alerts

- [ ] Alert при quantum risk level = CRITICAL
- [ ] Alert при quantum risk level = HIGH
- [ ] Alert при PQC operation failures
- [ ] Alert при high latency (> 100ms)

**Проверка:**
```bash
# Проверить alert rules
grep -n "quantum\|pqc" docs/grafana/alerts.yml
```

---

## 11. Documentation

### 11.1 Developer Documentation

- [ ] PQC migration guide для разработчиков
- [ ] HybridCryptoService API documentation
- [ ] Best practices для crypto-agility
- [ ] Troubleshooting guide

### 11.2 Operator Documentation

- [ ] PQC monitoring guide
- [ ] Incident response playbook для quantum threats
- [ ] Rollback procedures
- [ ] Key management procedures

---

## 12. Final Checklist

### 12.1 Перед продакшеном

- [ ] Все тесты проходят (unit, integration, performance)
- [ ] Security audit пройден
- [ ] Code review завершён
- [ ] Documentation обновлена
- [ ] Monitoring настроен
- [ ] Alerts настроены
- [ ] Rollback plan протестирован
- [ ] Team обучена

### 12.2 После миграции

- [ ] PQC adoption rate > 90%
- [ ] Performance impact < 20ms
- [ ] No security incidents
- [ ] Compliance audit пройден
- [ ] Post-mortem документирован

---

## Критерии приёмки

**Минимум:**
- [ ] Все 4 квантовые угрозы добавлены в модель угроз
- [ ] HybridCryptoService реализован и протестирован
- [ ] ThreatModelService с quantum risk scoring работает
- [ ] УЗ-3 акт обновлён с квантовыми угрозами
- [ ] PQC migration roadmap создан

**Рекомендовано:**
- [ ] Unit-тесты coverage > 90%
- [ ] Performance tests пройдены
- [ ] Security audit пройден
- [ ] Monitoring и alerts настроены
- [ ] Team обучена

**Идеально:**
- [ ] Все тесты автоматизированы в CI/CD
- [ ] PQC библиотеки production-ready
- [ ] HSM интегрирован
- [ ] Full PQC migration запущена
- [ ] Zero-downtime re-encryption протестирован

---

**Аудитор:** _________________________  
**Дата аудита:** _________________________  
**Статус:** _________________________  
**Замечания:** _________________________
