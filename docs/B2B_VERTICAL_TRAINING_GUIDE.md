# B2B Обучение по бизнес-вертикалям

## Обзор

Функционал B2B обучения по бизнес-вертикалям позволяет создавать специализированные курсы обучения для различных бизнес-вертикалей (Beauty, Hotels, Flowers, Auto, Medical, Fitness, Restaurants, Pharmacy) и управлять обучением сотрудников компаний.

## Поддерживаемые вертикали

- **beauty** - Бьюти-салоны
- **hotels** - Гостиницы
- **flowers** - Флористика
- **auto** - Автосервис
- **medical** - Медицина
- **fitness** - Фитнес
- **restaurants** - Рестораны
- **pharmacy** - Аптеки

## Установка

### Запуск миграций

```bash
php artisan migrate
```

### Запуск seeder для начальных данных

```bash
php artisan db:seed --class=B2BVerticalCoursesSeeder
```

## Модели

### VerticalCourse

Модель для связи курсов с бизнес-вертикалями.

**Поля:**
- `uuid` - уникальный идентификатор
- `tenant_id` - ID тенанта
- `course_id` - ID курса
- `vertical` - бизнес-вертикаль (beauty, hotels, flowers, и т.д.)
- `target_role` - целевая роль (manager, master, receptionist, и т.д.)
- `difficulty_level` - уровень сложности (beginner, intermediate, advanced)
- `duration_hours` - продолжительность в часах
- `is_required` - обязательный курс
- `prerequisites` - предварительные требования (JSON)
- `learning_objectives` - цели обучения (JSON)
- `metadata` - метаданные (JSON)
- `correlation_id` - ID корреляции для трассировки

## API Endpoints

### Получение курсов по вертикали

```http
GET /api/education/b2b/v1/verticals/{vertical}/courses?target_role={role}&difficulty_level={level}
```

**Пример:**
```bash
curl -X GET "http://localhost/api/education/b2b/v1/verticals/beauty/courses?target_role=master&difficulty_level=beginner" \
  -H "Authorization: Bearer {token}"
```

### Получение обязательных курсов по вертикали

```http
GET /api/education/b2b/v1/verticals/{vertical}/courses/required
```

### Получение рекомендованных курсов по роли

```http
GET /api/education/b2b/v1/verticals/{vertical}/roles/{role}/recommendations
```

### Зачисление сотрудника на обязательные курсы

```http
POST /api/education/b2b/v1/verticals/{vertical}/enroll-employee
```

**Body:**
```json
{
  "employee_id": 123,
  "contract_id": 456
}
```

### Зачисление сотрудника на конкретный курс

```http
POST /api/education/b2b/v1/verticals/{vertical}/courses/{course}/enroll
```

**Body:**
```json
{
  "employee_id": 123,
  "contract_id": 456
}
```

### Получение прогресса сотрудника по вертикали

```http
GET /api/education/b2b/v1/verticals/{vertical}/employees/{employee}/progress
```

**Ответ:**
```json
{
  "vertical": "beauty",
  "total_courses": 5,
  "completed_courses": 2,
  "in_progress_courses": 1,
  "not_started_courses": 2,
  "average_progress_percent": 60.0,
  "completion_rate_percent": 40.0
}
```

### Получение прогресса компании по вертикали

```http
GET /api/education/b2b/v1/verticals/{vertical}/company/progress?tenant_id={tenant_id}
```

### Создание курса вертикали

```http
POST /api/education/b2b/v1/vertical-courses
```

**Body:**
```json
{
  "course_id": 789,
  "vertical": "beauty",
  "target_role": "master",
  "difficulty_level": "intermediate",
  "duration_hours": 40,
  "is_required": true,
  "prerequisites": {
    "basic_hygiene": "Знание основ гигиены"
  },
  "learning_objectives": {
    "cutting": "Мастерство стрижки",
    "styling": "Укладка волос"
  }
}
```

### Обновление курса вертикали

```http
PUT /api/education/b2b/v1/vertical-courses/{id}
```

### Удаление курса вертикали

```http
DELETE /api/education/b2b/v1/vertical-courses/{id}
```

## Сервис B2BVerticalTrainingService

### Основные методы

```php
use App\Domains\Education\Services\B2BVerticalTrainingService;

$service = app(B2BVerticalTrainingService::class);

// Получить курсы для вертикали
$courses = $service->getCoursesForVertical('beauty', 'master', 'beginner');

// Получить обязательные курсы
$requiredCourses = $service->getRequiredCoursesForVertical('beauty');

// Зачислить сотрудника на обязательные курсы
$enrollments = $service->enrollEmployeeInRequiredCourses(
    $employee,
    'beauty',
    $contract,
    $correlationId
);

// Зачислить сотрудника на конкретный курс
$enrollment = $service->enrollEmployeeInCourse(
    $employee,
    $course,
    $contract,
    $correlationId
);

// Получить прогресс сотрудника
$progress = $service->getEmployeeProgressForVertical($employee, 'beauty');

// Получить прогресс компании
$companyProgress = $service->getCompanyProgressForVertical($tenantId, 'beauty');

// Создать курс вертикали
$verticalCourse = $service->createVerticalCourse($data, $correlationId);

// Обновить курс вертикали
$updatedCourse = $service->updateVerticalCourse($verticalCourse, $data);

// Удалить курс вертикали
$result = $service->deleteVerticalCourse($verticalCourse);

// Получить рекомендованные курсы по роли
$recommendations = $service->getRecommendedCoursesForRole('beauty', 'master');
```

## События

### EmployeeEnrolledInVerticalCourse

Событие генерируется при зачислении сотрудника на курс вертикали.

```php
use App\Domains\Education\Events\EmployeeEnrolledInVerticalCourse;

Event::listen(EmployeeEnrolledInVerticalCourse::class, function ($event) {
    $employee = $event->employee;
    $verticalCourse = $event->verticalCourse;
    $enrollment = $event->enrollment;
    
    // Обработка события
});
```

### VerticalCourseCreated

Событие генерируется при создании курса вертикали.

### VerticalCourseUpdated

Событие генерируется при обновлении курса вертикали.

### VerticalCourseDeleted

Событие генерируется при удалении курса вертикали.

## Политики доступа

Политика `VerticalCoursePolicy` управляет доступом к операциям с курсами вертикалей:

- `viewAny` - просмотр списка курсов
- `view` - просмотр конкретного курса
- `create` - создание курса
- `update` - обновление курса
- `delete` - удаление курса
- `enroll` - зачисление сотрудников
- `viewProgress` - просмотр прогресса обучения

## Filament Admin

Функционал доступен в админ-панели Filament:

**Навигация:** Образование → Курсы по вертикалям

Возможности:
- Создание курсов вертикалей
- Редактирование курсов вертикалей
- Удаление курсов вертикалей
- Фильтрация по вертикали, уровню сложности, обязательности
- Поиск по названию курса и роли

## Frontend Компоненты

### B2BVerticalTrainingPanel.vue

Основной компонент для управления B2B обучением.

**Использование:**

```vue
<script setup>
import B2BVerticalTrainingPanel from '@/Components/Business/Education/B2BVerticalTrainingPanel.vue'
</script>

<template>
  <B2BVerticalTrainingPanel vertical="beauty" />
</template>
```

**Props:**
- `vertical` - бизнес-вертикаль (beauty, hotels, flowers, и т.д.)

**Функции:**
- Отображение курсов вертикали с фильтрацией
- Отображение прогресса сотрудников компании
- Зачисление сотрудников на курсы

## Тестирование

Запуск unit тестов:

```bash
php artisan test --filter=B2BVerticalTrainingServiceTest
```

## Роли сотрудников

Поддерживаемые роли для различных вертикалей:

### Beauty
- manager - Менеджер
- master - Мастер
- receptionist - Ресепшионист
- administrator - Администратор

### Hotels
- manager - Менеджер
- receptionist - Ресепшионист
- housekeeper - Горничная
- concierge - Консьерж

### Flowers
- florist - Флорист
- manager - Менеджер
- delivery - Курьер
- administrator - Администратор

### Auto
- mechanic - Механик
- manager - Менеджер
- administrator - Администратор
- advisor - Консультант

### Medical
- doctor - Врач
- nurse - Медсестра
- administrator - Администратор
- receptionist - Ресепшионист

### Fitness
- trainer - Тренер
- manager - Менеджер
- receptionist - Ресепшионист
- administrator - Администратор

### Restaurants
- waiter - Официант
- chef - Повар
- manager - Менеджер
- administrator - Администратор

### Pharmacy
- pharmacist - Провизор
- manager - Менеджер
- administrator - Администратор
- assistant - Помощник

## Уровни сложности

- **beginner** - Начинающий
- **intermediate** - Средний
- **advanced** - Продвинутый

## Логирование

Все операции логируются с correlation_id для трассировки:

```php
Log::info('B2B Vertical Training: Operation completed', [
    'vertical' => 'beauty',
    'correlation_id' => $correlationId,
]);
```

## Корпоративные контракты

Для зачисления сотрудников на курсы используется корпоративный контракт (CorporateContract). Контракт должен иметь доступные слоты (`slots_available > 0`).

## Интеграция с существующими вертикалями

Для интеграции B2B обучения с существующей вертикалью:

1. Создайте курсы через Filament Admin или API
2. Свяжите курсы с вертикалью через VerticalCourse
3. Создайте корпоративный контракт для компании
4. Зачислите сотрудников на курсы через API или сервис

## Поддержка

При возникновении проблем проверьте:
1. Корректность миграций: `php artisan migrate:status`
2. Наличие необходимых прав доступа
3. Доступность слотов в корпоративном контракте
4. Корректность correlation_id в логах
