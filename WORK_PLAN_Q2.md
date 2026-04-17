# WORK_PLAN Q2 (Апрель - Июнь 2026)

## ЦЕЛЬ Q2

Завершить backend-инфраструктуру, добавить финальные миграции, обеспечить полное тестовое покрытие и подготовить платформу к масштабированию до 10M пользователей.

---

## СПРИНТЫ Q2

### Sprint 11: Завершение миграций и инфраструктуры
**Цель:** Создать все оставшиеся миграции и завершить инфраструктуру данных.

**Задачи:**
- ✅ chat_rooms + chat_messages миграции
- ✅ search_queries миграция
- ✅ commission_rules миграция
- ✅ webhook_endpoints + webhook_deliveries миграции
- ✅ notification_preferences миграция
- ✅ user_addresses миграция
- ✅ compliance_records миграция
- ✅ Проверить/создать audit_logs миграцию

### Sprint 12: HR и расширенные сервисы
**Цель:** Создать missing HR сервисы для управления персоналом.

**Задачи:**
- ✅ ShiftSchedulingService (расписание смен)
- ✅ TimeTrackingService (учёт времени)
- ✅ LeaveManagementService (отпуска/больничные)
- ✅ Тесты для всех HR сервисов

### Sprint 13: BigData и ML оптимизация
**Цель:** Оптимизировать BigData и ML для production.

**Задачи:**
- ✅ Добавить партиционирование ClickHouse по tenant + дате
- ✅ Рассмотреть создание app/Domains/ML/ для полной 9-слойной архитектуры
- ✅ Оптимизировать MLRecalculateJob для больших объёмов данных

### ✅ Sprint 14: Тестовое покрытие
**Цель:** Достижение 90%+ тестового покрытия критических сервисов.

**Задачи:**
- ✅ Unit/Domains/Security/ тесты (уже существуют)
- ✅ Unit/Domains/Commissions/ тесты
- ✅ Unit/Domains/Webhooks/ тесты
- ✅ Unit/Domains/Realtime/ тесты
- ✅ Unit/Domains/Compliance/ тесты
- ✅ Feature/Domains/B2B/B2BOrderFlowTest
- ✅ Integration/Webhooks/ тесты

### ✅ Sprint 15: Performance и масштабирование
**Цель:** Подготовка к 10M пользователей.

**Задачи:**
- ✅ ClickHouse партиционирование по tenant + дата
- ✅ Настроить Redis кластер для сессий и кэша
- ✅ Настроить очередь (Redis/Horizon) для асинхронных задач
- ✅ Оптимизировать запросы к БД (индексы, партиционирование)
- ✅ Настроить CDN для статических файлов
- ✅ Load testing и оптимизация

### Sprint 16: Мониторинг и алерты
**Цель:** Полное покрытие мониторинга.

**Задачи:**
- ✅ Настроить Prometheus + Grafana
- ✅ Алерты для критических метрик (CPU, Memory, DB connections)
- ✅ Алерты для бизнес-метрик (orders, payments, errors)
- ✅ Логирование в ELK stack
- ✅ Health checks для всех сервисов

---

## DETAL'NAYA PROVERKA PO DOMENAM

### ❌ COMMUNICATION (app/Domains/Communication/)
- [x] ChatService
- [ ] Создать chat_rooms + chat_messages миграции
- [x] Тесты: Unit

### ✅ ML (app/Services/ML/)
- [x] UserBehaviorAnalyzerService
- [x] AnonymizationService
- [x] NewUserColdStartService / ReturningUserDeepProfileService
- [x] TasteMLService
- [x] app/Domains/ML/ (9-слойная архитектура)
- [x] Тесты: Feature/ML, Unit/ML, Unit/Services

### ✅ BIGDATA (app/Services/ML/BigDataAggregatorService.php)
- [x] ClickHouse insert methods
- [x] BigDataAggregatorService (уже существует)
- [x] UserBehaviorAnalyzerService (уже существует)
- [x] MLRecalculateJob (уже существует)
- [ ] Добавить партиционирование по tenant + дате
- [x] Тесты: Unit/Services

### ✅ HR (app/Services/HR/, app/Services/HRService.php)
- [x] EmployeeService, PayrollService
- [x] ShiftSchedulingService (создан)
- [x] TimeTrackingService (создан)
- [x] LeaveManagementService (создан)
- [x] Миграции: shift_schedules, time_entries, leaves, leave_balances
- [x] Тесты: Unit/Services

### ⚠️ SEARCH (app/Services/Search*.php)
- [x] SearchService, LiveSearchService, SearchRankingService
- [ ✅ Создать search_queries миграцию
- [x] Тесты: Feature/Controllers/Api
x
###x❌ COMMISSIONS (app/Services/CommissionService.php, modules/Commissions/)
- [x] Создать CommissionRule модель
- [x] Обновить CalculateCommissionDto с isB2B,xmonthlyVolume, correlationId
- [x] CommissionService (базовый)
- [ ] Создать commission_rules миграцию
- [ ] Добавить B2C(14%)/B2B(8-12%) tier логику
- [ ] Написать Unit/Domains/Commissions/ тесты

### ⚠️ PAYOUT (app/Services/Payout/)
- [x] PayoutService, MassPayoutService
- [x] payout_requests миграция
- [ ] Проверить BatchPayoutJob регистрацию

### ❌ AUDIT (app/Services/AuditService.php)
- [x] AuditService: log(), logModelEvent()
- [x] AuditLogJob (async)
- [ ] Проверить/создать базовую audit_logs миграцию
- [x] Тесты: Unit/Services/AuditServiceTest

### ⚠️ SECURITY (app/Services/Security/, 9 файлов)
- [x] SecurityMonitoringService, RateLimiterService, IdempotencyService
- [x] ApiKeyManagementService, WebhookSignatureService
- [x] security_events миграция
- [ ] Добавить Unit/Domains/Security/ тесты

### ⚠️ NOTIFICATIONS (app/Services/Notification*.php)
- [x] NotificationService, NotificationChannelService
- [x] NotificationPreferencesService
- [ ] Создать notification_preferences миграцию
- [x] Тесты: Integration/Notifications (5 тестов)

### ⚠️ CART (app/Services/CartService.php)
- [x] CartService
- [x] carts миграция
- [ ] Проверить 20-мин резерв + CartCleanupJob
- [x] Тесты: Unit/Services/CartServiceTest

### ⚠️ B2B (app/Services/B2B/)
- [x] B2BApiKeyService, B2BOrderService
- [x] business_groups, b2b_api_keys миграции
- [ ✅ Создать Feature/Domains/B2B/B2BOrderFlowTest
- [x] Тесты: Feature/Filament/B2BPanelTest
x
###x❌ WEBHOOKS (app/Services/API/Webhook*, app/Services/Webhook/)
- [x✅ WebhookManagementService, WebhookSignatureValidator
- [ ] Создать webhook_endpoints + webhook_deliveries миграции
- [ ] Написать Unit/Domains/Webhooks/ тесты
- [x] Написать Integration/Webhooks/ тест

### ❌ REALTIME (app/Services/Realtime*.php, WebSocketConnectionService)
- [x] RealtimeService, RealtimeChatService, RealtimeAnalyticsService
- [ ] Создать realtime таблицы (если нужны, или только Redis)
- [ ] Написать Unit/Domains/Realtime/ тесты
- [ ] Настроить Echo channels конфигурацию

### ❌ USERPROFILE (app/Services/UserAddressService.php + UserActivityService.php)
- [x✅ UserAddressService (до 5 адресов логика)
- [x] UserActivityService
- [x] Создать user_addresses миграцию
- [x] Тесты: Feature/Controllers/Api/V1/UserProfile

### ❌ COMPLIANCE (app/Services/Compliance/, app/Services/Security/ComplianceManagement*.php)
- [x] ComplianceRequirementService, MdlpService, MercuryService
- [ ] Создать compliance_records миграцию
- [ ] Написать Unit/Domains/Compliance/ тесты

---

## НОВЫЕ ЗАДАЧИ Q2

### AI Конструкторы для всех вертикалей
**Цель:** Создать AI-конструкторы для всех 52+ вертикалей согласно канону.

**Задачи:**
- [ ] BeautyImageConstructorService (уже есть)
- [ ] InteriorDesignConstructorService
- [ ] MenuConstructorService
- [ ] FashionStyleConstructorService
- [ ] RealEstateDesignConstructorService
- [ ] AutoTuningConstructorService
- [ ] MedicalTriageConstructorService
- [ ] И так далее для всех вертикалей (52+ сервисов)

**Требования к каждому:**
- Constructor injection (OpenAI\Client, RecommendationService, InventoryService, UserTasteAnalyzerService)
- correlation_id + Log::channel('audit') + FraudControlService::check()
- DB::transaction() при сохранении
- Кэширование в Redis (TTL 3600 сек)
- Интеграция с B2C/B2B (разные цены и доступность)
- Тесты (Feature + Unit)

---

## КОМАНДЫ ДЛЯ ПРОВЕРКИ ПРОГРЕССА

```bash
# Проверить что все миграции валидны
php artisan migrate:status

# Запустить Q2 тесты
./vendor/bin/pest tests/Unit/Domains/ tests/Unit/Services/ tests/Integration/ tests/Feature/ --parallel

# Проверить синтаксис всех PHP
php -r "iterator_apply(
  new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app')),
  fn($f) => !$f->isFile() || shell_exec('php -l '.$f->getPathname()),
  []
);"

# Проверить queue задачи
php artisan schedule:list
php artisan queue:monitor

# Проверить canon violations (ищем запрещённые паттерны)
grep -r "Auth::" app/ --include="*.php" | grep -v "vendor"
grep -r "return null;" app/Services/ --include="*.php"
grep -r "new Exception(" app/Services/ --include="*.php" | grep -v "tests"

# Load testing
php artisan load:test --users=1000 --duration=300
```

---

## ДАТА СТАРТА РАБОТ

- **Sprint 11** (миграции): **СТАРТ СЕЙЧАС**
- **Sprint 12** (HR): после Sprint 11
- **Sprint 13** (BigData/ML): параллельно с Sprint 12
- **Sprint 14** (тесты): после Sprint 11-13
- **Sprint 15** (performance): после Sprint 14
- **Sprint 16** (мониторинг): параллельно с Sprint 15

**Ожидаемое завершение Q2:** ~2-3 недели при 8 часов в день

---

## КРИТЕРИИ ЗАВЕРШЕНИЯ Q2

1. ✅ Все миграции созданы и протестированы
2. ✅ Все missing сервисы созданы (HR, AI конструкторы)
3. ✅ Тестовое покрытие >= 90% для критических сервисов
4. ✅ Load testing пройден для 10K concurrent users
5. ✅ Мониторинг настроен и алерты работают
6. ✅ Код соответствует канону (copilot-instructions.md)

---

*Последнее обновление: 15.04.2026*  
*Следующий файл: WORK_PLAN_Q3.md (после завершения Q2)*
