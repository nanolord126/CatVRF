# Staff HRM — 9-ти Слойная Архитектура

**Дата:** 2026-04-27  
**Версия:** 1.0  
**Модуль:** CatCRM / Staff HRM

## Обзор

Staff HRM реализован с соблюдением **9-ти слойной архитектуры** для максимальной разделения ответственности и тестируемости.

---

## Слои Архитектуры

### Layer 1: Presentation/UI Layer (Слой представления)

**Назначение:** UI компоненты для отображения данных и пользовательского ввода.

**Файлы:**
- `Filament/Resources/StaffEmployeeResource.php` — Управление сотрудниками
- `Filament/Resources/StaffBadgeResource.php` — Управление бейджами
- `Filament/Resources/StaffShiftResource.php` — Управление сменами

**Технологии:** Filament 3/4, Blade, Livewire

---

### Layer 2: API/HTTP Layer (Слой API)

**Назначение:** HTTP контроллеры и маршруты для REST API.

**Файлы:**
- `Infrastructure/Http/Controllers/Api/StaffController.php` — API контроллер
- `routes/api/staff.php` — API маршруты

**Эндпоинты:**
- `GET /api/staff` — Список сотрудников
- `POST /api/staff` — Создать сотрудника
- `GET /api/staff/{id}` — Профиль сотрудника
- `PUT /api/staff/{id}` — Обновить сотрудника
- `POST /api/staff/{id}/analyze-performance` — AI анализ
- `POST /api/staff/{id}/predict-burnout` — Предсказание выгорания
- `GET /api/staff/leaderboard` — Лидерборд

---

### Layer 3: Application Services Layer (Слой прикладных сервисов)

**Назначение:** Оркестрация бизнес-процессов, координация доменных сервисов.

**Файлы:**
- `Application/Services/Staff/CRMStaffIntegrationService.php` — Основной оркестратор
- `Application/Services/Staff/StaffPerformanceAnalyzerService.php` — AI анализ производительности
- `Application/Services/Staff/BurnoutPredictionService.php` — AI предсказание выгорания
- `Application/Services/Staff/SkillMatchingService.php` — Сопоставление навыков
- `Application/Services/Staff/TrainingRecommendationService.php` — Рекомендации обучения

**Ответственность:**
- Координация между доменными сервисами
- Управление транзакциями
- Вызов асинхронных Jobs

---

### Layer 4: Domain Services Layer (Слой доменных сервисов)

**Назначение:** Бизнес-логика, не привязанная к конкретной сущности.

**Файлы:**

**Геймификация:**
- `Application/Services/Staff/Gamification/BadgeService.php`
- `Application/Services/Staff/Gamification/AchievementService.php`
- `Application/Services/Staff/Gamification/LeaderboardService.php`
- `Application/Services/Staff/Gamification/ChallengeService.php`
- `Application/Services/Staff/Gamification/LevelProgressService.php`

**Социальные:**
- `Application/Services/Staff/Social/PeerReviewService.php`
- `Application/Services/Staff/Social/MentoringService.php`
- `Application/Services/Staff/Social/SocialNetworkService.php`
- `Application/Services/Staff/Social/GratitudeService.php`

**Обучение:**
- `Application/Services/Staff/Training/TrainingCourseService.php`

**График:**
- `Application/Services/Staff/Schedule/ShiftScheduleService.php`
- `Application/Services/Staff/Schedule/LeaveManagementService.php`

**Благополучие:**
- `Application/Services/Staff/Wellness/WorkLifeBalanceService.php`

**Аналитика:**
- `Application/Services/Staff/Analytics/TeamDynamicsAnalyzerService.php`

**Коммуникация:**
- `Application/Services/Staff/Communication/InternalChatService.php`

---

### Layer 5: Domain Entities Layer (Слой доменных сущностей)

**Назначение:** Сущности предметной области (DDD), Value Objects, Enums.

**Файлы:**

**Сущности (readonly):**
- `Domain/Staff/Employee.php`
- `Domain/Staff/Skill.php`
- `Domain/Staff/Badge.php`
- `Domain/Staff/Achievement.php`
- `Domain/Staff/Mentorship.php`
- `Domain/Staff/PeerReview.php`
- `Domain/Staff/TrainingCourse.php`
- `Domain/Staff/Certification.php`
- `Domain/Staff/Shift.php`
- `Domain/Staff/Leave.php`
- `Domain/Staff/WellnessMetrics.php`

**Value Objects:**
- `Domain/Staff/ValueObjects/EmployeeId.php`
- `Domain/Staff/ValueObjects/SkillId.php`
- `Domain/Staff/ValueObjects/BadgeId.php`
- `Domain/Staff/ValueObjects/AchievementId.php`
- `Domain/Staff/ValueObjects/MentorshipId.php`
- `Domain/Staff/ValueObjects/PeerReviewId.php`
- `Domain/Staff/ValueObjects/TrainingCourseId.php`
- `Domain/Staff/ValueObjects/CertificationId.php`
- `Domain/Staff/ValueObjects/ShiftId.php`
- `Domain/Staff/ValueObjects/LeaveId.php`
- `Domain/Staff/ValueObjects/WellnessMetricsId.php`

**Enums:**
- `Domain/Staff/ValueObjects/EmployeeStatus.php`
- `Domain/Staff/ValueObjects/EmployeeRole.php`
- `Domain/Staff/ValueObjects/SkillLevel.php`
- `Domain/Staff/ValueObjects/MentorshipStatus.php`
- `Domain/Staff/ValueObjects/PeerReviewStatus.php`
- `Domain/Staff/ValueObjects/CertificationStatus.php`
- `Domain/Staff/ValueObjects/ShiftStatus.php`
- `Domain/Staff/ValueObjects/LeaveType.php`
- `Domain/Staff/ValueObjects/LeaveStatus.php`

**Принципы:**
- Readonly классы
- Immutability
- Rich domain model

---

### Layer 6: Repository Interfaces Layer (Слой интерфейсов репозиториев)

**Назначение:** Абстракции для доступа к данным (Dependency Inversion Principle).

**Файлы:**
- `Domain/Staff/Repositories/EmployeeRepositoryInterface.php`
- `Domain/Staff/Repositories/SkillRepositoryInterface.php`
- `Domain/Staff/Repositories/BadgeRepositoryInterface.php`
- `Domain/Staff/Repositories/AchievementRepositoryInterface.php`
- `Domain/Staff/Repositories/MentorshipRepositoryInterface.php`
- `Domain/Staff/Repositories/PeerReviewRepositoryInterface.php`
- `Domain/Staff/Repositories/ShiftRepositoryInterface.php`
- `Domain/Staff/Repositories/LeaveRepositoryInterface.php`
- `Domain/Staff/Repositories/WellnessMetricsRepositoryInterface.php`

**Принципы:**
- Интерфейсы в Domain слое
- Реализации в Infrastructure слое

---

### Layer 7: Repository Implementations Layer (Слой реализаций репозиториев)

**Назначение:** Конкретные реализации доступа к данным (Eloquent ORM).

**Файлы:**
- `Infrastructure/Repositories/EloquentEmployeeRepository.php`
- `Infrastructure/Repositories/EloquentSkillRepository.php`
- `Infrastructure/Repositories/EloquentBadgeRepository.php`
- `Infrastructure/Repositories/EloquentAchievementRepository.php`
- `Infrastructure/Repositories/EloquentMentorshipRepository.php`
- `Infrastructure/Repositories/EloquentPeerReviewRepository.php`
- `Infrastructure/Repositories/EloquentShiftRepository.php`
- `Infrastructure/Repositories/EloquentLeaveRepository.php`
- `Infrastructure/Repositories/EloquentWellnessMetricsRepository.php`

**Технологии:** Laravel Query Builder / Eloquent

---

### Layer 8: Infrastructure/External Services Layer (Слой инфраструктуры)

**Назначение:** Внешние сервисы, очереди, event listeners, service binding.

**Файлы:**

**Async Jobs:**
- `Infrastructure/Jobs/AnalyzeStaffPerformanceJob.php` — AI анализ (async)
- `Infrastructure/Jobs/PredictBurnoutRiskJob.php` — Предсказание выгорания (async)
- `Infrastructure/Jobs/MatchSkillsToTaskJob.php` — Сопоставление навыков (async)
- `Infrastructure/Jobs/GenerateTrainingRecommendationsJob.php` — Рекомендации (async)

**Service Provider:**
- `Providers/StaffServiceProvider.php` — Binding интерфейсов к реализациям

**Принципы:**
- Async LLM calls через Queue
- Dependency Injection через Service Provider
- Circuit breaker и retries

---

### Layer 9: Database/Persistence Layer (Слой персистентности)

**Назначение:** Модели базы данных и миграции.

**Файлы:**

**Eloquent Models:**
- `Domain/Staff/Models/EmployeeModel.php`
- `Domain/Staff/Models/SkillModel.php`
- `Domain/Staff/Models/BadgeModel.php`
- `Domain/Staff/Models/AchievementModel.php`
- `Domain/Staff/Models/MentorshipModel.php`
- `Domain/Staff/Models/PeerReviewModel.php`
- `Domain/Staff/Models/ShiftModel.php`
- `Domain/Staff/Models/LeaveModel.php`
- `Domain/Staff/Models/WellnessMetricsModel.php`
- `Domain/Staff/Models/TrainingCourseModel.php`

**Migrations:**
- `database/migrations/2026_04_27_000001_create_staff_employees_table.php`
- `database/migrations/2026_04_27_000002_create_staff_skills_table.php`
- `database/migrations/2026_04_27_000003_create_staff_badges_table.php`
- `database/migrations/2026_04_27_000004_create_staff_achievements_table.php`
- `database/migrations/2026_04_27_000005_create_staff_mentorships_table.php`
- `database/migrations/2026_04_27_000006_create_staff_peer_reviews_table.php`
- `database/migrations/2026_04_27_000007_create_staff_training_courses_table.php`
- `database/migrations/2026_04_27_000008_create_staff_shifts_table.php`
- `database/migrations/2026_04_27_000009_create_staff_leaves_table.php`
- `database/migrations/2026_04_27_000010_create_staff_wellness_metrics_table.php`

**Технологии:** MySQL, Laravel Migrations

---

## Поток Данных (Data Flow)

```
┌─────────────────────────────────────────────────────────────────┐
│ Layer 1: Presentation (Filament / Livewire)                      │
└────────────────────────┬────────────────────────────────────────┘
                         │ HTTP Request
┌────────────────────────▼────────────────────────────────────────┐
│ Layer 2: API/HTTP (Controllers)                                  │
└────────────────────────┬────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────┐
│ Layer 3: Application Services (Orchestration)                     │
└────────────────────────┬────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────┐
│ Layer 4: Domain Services (Business Logic)                        │
└────────────────────────┬────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────┐
│ Layer 5: Domain Entities (Rich Domain Model)                     │
└────────────────────────┬────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────┐
│ Layer 6: Repository Interfaces (Abstractions)                     │
└────────────────────────┬────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────┐
│ Layer 7: Repository Implementations (Eloquent)                    │
└────────────────────────┬────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────┐
│ Layer 9: Database (MySQL Tables)                                 │
└──────────────────────────────────────────────────────────────────┘

         ┌────────────────────────────────────────┐
         │ Layer 8: Infrastructure (Jobs, Queue)  │
         └────────────────────────────────────────┘
```

---

## Соответствие CatVRF Правилам

✅ **Clean Architecture + DDD** — Чёткое разделение слоёв  
✅ **WithAuditLogging trait** — Все сервисы используют trait  
✅ **Async LLM calls** — AI/ML через Jobs (Layer 8)  
✅ **Medical compliance** — PII анонимизация в BurnoutPredictionService  
✅ **Cache with tags** — Cache::tags() с инвалидацией  
✅ **DB transactions** — DB::transaction для write операций  
✅ **Strict typing** — declare(strict_types=1), final классы  
✅ **Dependency Inversion** — Repository interfaces в Domain слое  

---

## Статус Реализации

| Слой | Статус | Количество файлов |
|------|--------|-------------------|
| Layer 1: Presentation | ✅ | 3 |
| Layer 2: API/HTTP | ✅ | 2 |
| Layer 3: Application Services | ✅ | 5 |
| Layer 4: Domain Services | ✅ | 16 |
| Layer 5: Domain Entities | ✅ | 30 |
| Layer 6: Repository Interfaces | ✅ | 9 |
| Layer 7: Repository Implementations | ✅ | 9 |
| Layer 8: Infrastructure | ✅ | 5 |
| Layer 9: Database | ✅ | 20 |
| **ИТОГО** | **✅** | **99 файлов** |

---

## Следующие шаги

1. Запустить миграции: `php artisan migrate`
2. Зарегистрировать API routes в bootstrap/app.php
3. Создать Unit тесты для Domain слоя
4. Создать Feature тесты для API endpoints
5. Создать Livewire компоненты для интерактивных интерфейсов
