# CRM KPI и Задачи для Менеджеров - Руководство

**Дата:** 27.04.2026  
**Версия:** 1.0  
**Модуль:** CatCRM

## Обзор

Система CRM CatVRF теперь включает полноценную систему управления задачами и KPI для менеджеров, адаптивную для всех вертикалей и поддерживающую B2B/B2C контекст.

## Что было реализовано

### 1. Система Задач (Tasks)

**Таблица:** `crm_tasks` (существовала ранее, расширена)

**Основные поля:**
- `tenant_id`, `business_group_id` - мультитенантность
- `assigned_to_id` - ответственный менеджер
- `deal_id`, `customer_id` - связь с сделками и клиентами
- `type` - тип задачи (call, email, meeting, follow_up, document, payment, delivery, custom)
- `status` - статус (pending, in_progress, completed, cancelled)
- `priority` - приоритет (low, medium, high, urgent)
- `due_date` - дедлайн
- `progress` - прогресс выполнения (0-100)

**B2B/B2C поддержка:**
- `business_type` - b2b, b2c, both
- `supplier_id` - поставщик для B2B задач
- `tender_id` - связанный тендер
- `b2b_order_id` - связанный B2B заказ
- `vertical_id` - вертикаль для кросс-вертикальных задач

**KPI интеграция:**
- `kpi_tracked` - учитывается ли задача в KPI
- `kpi_weight` - вес задачи в KPI
- `kpi_period_start`, `kpi_period_end` - период KPI

**Подзадачи:**
- `parent_task_id` - поддержка иерархии задач

### 2. Система KPI Менеджеров

**Таблица:** `crm_manager_kpi` (новая)

**Основные поля:**
- `manager_id` - менеджер
- `period_type` - тип периода (daily, weekly, monthly, quarterly, yearly)
- `period_start`, `period_end` - период KPI
- `targets` - цели KPI (JSON)
- `actuals` - фактические значения (JSON)
- `score` - общий балл (0-100)
- `status` - статус (pending, in_progress, completed, failed)

**Статистика задач:**
- `tasks_assigned` - назначено задач
- `tasks_completed` - выполнено задач
- `tasks_on_time` - вовремя выполнено
- `tasks_overdue` - просрочено

**B2B/B2C поддержка:**
- `business_type` - b2b, b2c, both
- `vertical_id` - вертикаль (nullable для всех вертикалей)

**История:**
- Таблица `crm_manager_kpi_history` для отслеживания изменений

## Использование

### Создание задачи через TaskService

```php
use Modules\CatCRM\Application\Services\TaskService;
use Modules\CatCRM\Domain\Enums\TaskType;
use Modules\CatCRM\Domain\Enums\TaskPriority;

$taskService = app(TaskService::class);

// B2C задача
$task = $taskService->createTask([
    'tenant_id' => 1,
    'business_group_id' => null,
    'vertical_id' => null, // или ID вертикали
    'assigned_to_id' => 123,
    'title' => 'Позвонить клиенту',
    'description' => 'Обсудить новый заказ',
    'type' => TaskType::Call,
    'priority' => TaskPriority::High,
    'due_date' => now()->addDay(),
    'business_type' => 'b2c',
    'kpi_tracked' => true,
    'kpi_weight' => 1.0,
]);

// B2B задача для поставщика
$task = $taskService->createTask([
    'tenant_id' => 1,
    'vertical_id' => 5, // ID вертикали супермаркетов
    'supplier_id' => 456,
    'tender_id' => 789,
    'assigned_to_id' => 123,
    'title' => 'Проверить поставку',
    'description' => 'Проверить доставку от поставщика',
    'type' => TaskType::Custom,
    'priority' => TaskPriority::Urgent,
    'due_date' => now()->addHours(4),
    'business_type' => 'b2b',
    'kpi_tracked' => true,
]);
```

### Создание KPI периода

```php
use Modules\CatCRM\Application\Services\ManagerKPIService;

$kpiService = app(ManagerKPIService::class);

$kpi = $kpiService->createKPIPeriod([
    'tenant_id' => 1,
    'manager_id' => 123,
    'vertical_id' => null, // для всех вертикалей
    'period_type' => 'monthly',
    'period_start' => now()->startOfMonth(),
    'period_end' => now()->endOfMonth(),
    'business_type' => 'both',
    'targets' => [
        'tasks_completed' => [
            'value' => 50,
            'weight' => 1.0,
            'description' => 'Количество выполненных задач',
        ],
        'tasks_on_time' => [
            'value' => 45,
            'weight' => 1.2,
            'description' => 'Вовремя выполненные задачи',
        ],
        'customer_satisfaction' => [
            'value' => 4.5,
            'weight' => 0.8,
            'description' => 'Удовлетворенность клиентов',
        ],
    ],
]);
```

### Обновление KPI при завершении задачи

```php
// Автоматически вызывается в TaskService при завершении задачи
$kpiService->updateKPIOnTaskCompletion($task);

// Или при назначении
$kpiService->updateKPIOnTaskAssignment($task);
```

### Получение статистики KPI

```php
$stats = $kpiService->getManagerKPIStats(123, 1, 'monthly');

/*
[
    'average_score' => 87.5,
    'total_tasks_assigned' => 150,
    'total_tasks_completed' => 142,
    'total_tasks_on_time' => 135,
    'total_tasks_overdue' => 7,
    'completion_rate' => 94.67,
    'on_time_rate' => 95.07,
    'periods' => Collection,
]
*/
```

## Filament Админка

### ManagerKPI Resource

**Путь:** CRM → KPI Менеджеров

**Функции:**
- Создание KPI периодов
- Редактирование целей
- Пересчет балла
- Завершение периода
- Фильтрация по менеджеру, филиалу, вертикали, типу бизнеса, статусу

**Действия:**
- `Пересчитать` - пересчитать KPI score на основе actuals
- `Завершить период` - завершить период и создать запись в истории

## Миграции

### Новые миграции

1. `2026_04_27_000001_create_crm_manager_kpi_table.php`
   - Создает таблицу `crm_manager_kpi`
   - Создает таблицу `crm_manager_kpi_history`

### Существующие миграции (уже были)

1. `2026_04_23_000005_create_crm_tasks_table.php` - базовая таблица задач
2. `2024_04_27_000028_extend_crm_tasks_for_suppliers.php` - расширение для B2B/B2C и KPI
3. `2026_04_23_000012_create_crm_task_comments_table.php` - комментарии к задачам

## Модели

### ManagerKPI

**Путь:** `modules/CatCRM/Domain/Entities/ManagerKPI.php`

**Методы:**
- `calculateScore()` - рассчитать KPI score
- `updateActual($key, $value)` - обновить actual значение
- `complete()` - завершить период
- `getTaskCompletionRate()` - процент выполнения задач
- `getOnTimeRate()` - процент своевременного выполнения

### Task

**Путь:** `modules/CatCRM/Domain/Entities/Task.php`

**Методы:**
- `complete()` - завершить задачу
- `cancel()` - отменить задачу
- `start()` - начать выполнение
- `updateProgress($progress)` - обновить прогресс
- `isOverdue()` - проверка просрочки

## Сервисы

### ManagerKPIService

**Путь:** `modules/CatCRM/Application/Services/ManagerKPIService.php`

**Методы:**
- `createKPIPeriod(array $data)` - создать KPI период
- `getActiveKPI(int $managerId, int $tenantId)` - получить активный KPI
- `updateKPIOnTaskCompletion(Task $task)` - обновить KPI при завершении задачи
- `updateKPIOnTaskAssignment(Task $task)` - обновить KPI при назначении
- `getManagerKPIStats(int $managerId, int $tenantId, string $periodType)` - статистика
- `completeKPIPeriod(ManagerKPI $kpi)` - завершить период
- `updateKPITargets(ManagerKPI $kpi, array $targets)` - обновить цели

### TaskService

**Путь:** `modules/CatCRM/Application/Services/TaskService.php`

**Методы:**
- `createTask(array $data)` - создать задачу
- `completeTask(Task $task)` - завершить задачу
- `cancelTask(Task $task)` - отменить задачу
- `startTask(Task $task)` - начать выполнение
- `getUserTasks(int $userId, int $tenantId)` - задачи пользователя
- `getOverdueTasks(int $tenantId)` - просроченные задачи
- `getTodayTasks(int $tenantId)` - задачи на сегодня
- `getHighPriorityTasks(int $tenantId)` - высокие приоритеты

## Адаптивность для Вертикалей

Система полностью адаптивна для всех вертикалей:

1. **Без вертикали:** Укажите `vertical_id = null` для задач/KPI по всем вертикалям
2. **Конкретная вертикаль:** Укажите `vertical_id` для задач/KPI конкретной вертикали
3. **Кросс-вертикальные задачи:** Задача может быть связана с любой вертикалью

## B2B/B2C Поддержка

### B2C задачи
```php
'business_type' => 'b2c',
'customer_id' => 123,
```

### B2B задачи
```php
'business_type' => 'b2b',
'supplier_id' => 456,
'tender_id' => 789,
'b2b_order_id' => 101,
```

### Смешанные задачи
```php
'business_type' => 'both',
```

## Следующие шаги

1. Запустить миграции: `php artisan migrate`
2. Создать KPI периоды для менеджеров через Filament
3. Интегрировать создание задач в бизнес-процессы вертикалей
4. Настроить автоматическое обновление KPI при завершении задач
5. Добавить уведомления для просроченных задач

## Архитектура

- **Clean Architecture:** Domain → Application → Infrastructure
- **DDD:** Entities, Value Objects, Services
- **Audit Logging:** Все действия логируются через AuditService
- **Multitenancy:** Полная поддержка tenant_id и business_group_id
- **Soft Deletes:** Поддержка мягкого удаления

## Комплайенс

- **152-ФЗ:** Анонимизация PII данных перед отправкой во внешние системы
- **Audit Logging:** Все действия менеджеров логируются
- **Security:** Проверка прав доступа на уровне tenant/business_group
