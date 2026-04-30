# Чек-лист интеграции CatCRM с существующими модулями CatVRF

## Обязательные интеграции

### 1. Tenancy (Multi-tenancy)
- [x] Все CRM модели имеют `tenant_id`
- [x] Все CRM модели имеют `business_group_id` для филиалов
- [x] Global scope для tenant_id (опционально, через trait)
- [x] Изоляция данных на уровне базы данных

**Проверка:**
```php
// Проверить, что данные изолированы по tenant
$deal1 = Deal::factory()->forTenant($tenant1)->create();
$deal2 = Deal::factory()->forTenant($tenant2)->create();

tenancy()->initialize($tenant1);
$this->assertTrue(Deal::where('id', $deal1->id)->exists());
$this->assertFalse(Deal::where('id', $deal2->id)->exists());
```

### 2. KYB (Know Your Business)
- [ ] Интеграция с BusinessGroup verification
- [ ] Автоматическое создание CRM для верифицированных бизнесов
- [ ] Синхронизация бизнес-данных (ИНН, адрес)

**Реализация:**
```php
// Event Listener для BusinessGroupApproved
class BusinessGroupApprovedListener
{
    public function handle(BusinessGroupApproved $event): void
    {
        // Создать default pipeline для бизнеса
        // Создать начальный сегмент клиентов
    }
}
```

### 3. Платежи (Payment Module)
- [x] Event Listener для OrderPaid → обновление статуса сделки
- [ ] Связь сделок с PaymentRecord
- [ ] Отображение платежной истории в CRM
- [ ] Автоматическое создание задачи для follow-up после оплаты

**Действия:**
- Добавить `payment_id` в таблицу `crm_deals`
- Создать relation `payment()` в модели Deal
- Добавить payment status в Deal

### 4. Доставка (Logistics Module)
- [ ] Интеграция с courier assignment
- [ ] Отслеживание статусов доставки в CRM
- [ ] Автоматические уведомления при доставке
- [ ] Связь с DeliveryOrder

**Действия:**
- Добавить `delivery_order_id` в таблицу `crm_deals`
- Создать relation `deliveryOrder()` в модели Deal
- Event Listener для DeliveryCompleted

### 5. Аналитика (Analytics Module)
- [ ] Отправка событий CRM в AnalyticsEvent
- [ ] Dashboard с метриками CRM (конверсия, LTV, retention)
- [ ] Экспорт данных в ClickHouse
- [ ] Отчеты по воронкам

**Действия:**
- Создать CRM event types в analytics config
- Добавить трекинг событий (deal_created, deal_won, deal_lost)
- Создать аналитические view в ClickHouse

### 6. Fraud Detection (FraudML)
- [ ] Проверка fraud score при создании клиента
- [ ] Автоматическая блокировка подозрительных клиентов
- [ ] Интеграция с FraudControlService
- [ ] Alert для менеджеров при high-risk сделках

**Действия:**
- Вызов FraudControlService в CustomerService
- Добавить `fraud_score` в таблицу `crm_customers`
- Создать автоматическую задачу для high-risk клиентов

### 7. Behavioral Biometrics
- [ ] Связь взаимодействий с behavioral events
- [ ] Анализ паттернов коммуникации
- [ ] Детекция аномалий в поведении

**Действия:**
- Добавить `behavioral_session_id` в таблицу `crm_interactions`
- Интеграция с behavioral collection

### 8. Notifications
- [ ] SMS уведомления (напоминания о записях, доставках)
- [ ] Email уведомления (follow-up, отчеты)
- [ ] Push уведомления (для мобильных приложений)
- [ ] Telegram/Webhook интеграции

**Действия:**
- Настроить notification channels для CRM
- Создать notification templates
- Интеграция с существующим notification модулем

### 9. Device Binding & SplitKey
- [ ] Привязка CRM сессий к device_id
- [ ] Двухфакторная аутентификация для CRM доступа
- [ ] Session management для Tenant Panel

**Действия:**
- Добавить device binding при входе в Tenant Panel
- Интеграция с SplitKey для критических действий

### 10. User Management (RBAC)
- [ ] Роли для CRM (Owner, Manager, Staff)
- [ ] Permissions для CRM действий
- [ ] Интеграция с существующим RBAC модулем
- [ ] Team presence (кто онлайн)

**Действия:**
- Добавить CRM permissions в permissions config
- Проверка прав при действиях в CRM
- Отображение онлайн статуса менеджеров

## Опциональные интеграции

### 11. AI Constructor
- [ ] AI-генерация описаний сделок
- [ ] AI-рекомендации следующего действия
- [ ] Автоматическая классификация клиентов
- [ ] Sentiment анализ взаимодействий

### 12. Bonuses & Loyalty
- [ ] Интеграция с loyalty tiers
- [ ] Начисление бонусов за сделки
- [ ] Использование бонусов в CRM

### 13. Recommendations
- [ ] Рекомендация товаров/услуг на основе истории
- [ ] Cross-sell / Upsell предложения
- [ ] Персонализированные предложения

### 14. Geo & GeoLogistics
- [ ] Геолокация клиентов
- [ ] Оптимизация маршрутов доставки
- [ ] Поиск ближайших сервисов

### 15. Voice Biometrics
- [ ] Запись звонков в CRM
- [ ] Voice authentication клиентов
- [ ] Анализ тональности звонков

## Инфраструктурные интеграции

### 16. Queue & Horizon
- [x] Использование queue для тяжелых операций
- [ ] Мониторинг CRM jobs в Horizon
- [ ] Retry политика для failed jobs
- [ ] Rate limiting для массовых операций

### 17. Cache & Cache Tags
- [ ] Кэширование статистики воронок
- [ ] Инвалидация кэша при изменениях
- [ ] Cache tags для tenant-specific данных

### 18. ClickHouse (Big Data)
- [ ] Экспорт CRM данных в ClickHouse
- [ ] Аналитические запросы к историческим данным
- [ ] Feature store для ML моделей

### 19. Monitoring & Observability
- [ ] OpenTelemetry трассировка CRM операций
- [ ] Prometheus метрики (deals_created, conversion_rate)
- [ ] Health checks для CRM модуля
- [ ] Alert rules для критических метрик

### 20. Logging
- [ ] Структурированные логи CRM операций
- [ ] Audit лог для всех изменений
- [ ] Correlation ID для трассировки
- [ ] Логи в ClickHouse для анализа

## Тестирование интеграций

### Unit Tests
- [ ] Тесты DealService
- [ ] Тесты CustomerService
- [ ] Тесты TaskService
- [ ] Тесты Event Listeners

### Integration Tests
- [ ] Тест интеграции с OrderCreated
- [ ] Тест интеграции с OrderPaid
- [ ] Тест интеграции с Payment
- [ ] Тест интеграции с Fraud Detection

### E2E Tests
- [ ] Полный цикл: заказ → сделка → оплата → win
- [ ] Тест vertical-specific сценариев
- [ ] Тест multi-tenancy изоляции
- [ ] Тест performance под нагрузкой

## Deployment Checklist

### Pre-deployment
- [ ] Все миграции протестированы
- [ ] Event Listeners зарегистрированы
- [ ] Permissions настроены
- [ ] Мониторинг настроен
- [ ] Backup базы данных создан

### Post-deployment
- [ ] Проверить создание воронок для существующих tenants
- [ ] Проверить синхронизацию заказов
- [ ] Проверить performance
- [ ] Проверить логи на ошибки
- [ ] Мониторинг метрик

## Документация

- [x] README с инструкциями по установке
- [x] Чек-лист интеграции
- [ ] API документация
- [ ] Архитектурная документация
- [ ] Руководство для разработчиков
- [ ] Руководство для бизнеса

## Priority Matrix

| Интеграция | Priority | Effort | Impact |
|------------|----------|--------|--------|
| Tenancy | P0 | Low | Critical |
| Платежи | P0 | Medium | High |
| Доставка | P0 | Medium | High |
| Аналитика | P1 | High | High |
| Fraud Detection | P1 | Medium | High |
| Notifications | P1 | Medium | High |
| RBAC | P0 | Low | Critical |
| KYB | P2 | Low | Medium |
| Behavioral | P2 | High | Medium |
| AI Constructor | P3 | High | High |

## Статус

**Общий прогресс:** 60%  
- Core функционал: 100%
- Вертикальные модули: 100%
- Интеграции: 40%
- UI (Filament): 0%
- Тесты: 20%
- Документация: 80%
