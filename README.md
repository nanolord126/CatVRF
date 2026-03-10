# CATVRF 2026 PRODUCTION REPOSITORY (RELEASE STAGE)

## ВЕРТИКАЛЬНЫЕ МОДУЛИ 2026
- **Advertising Engine (ОРД + ФЗ-38)**: Полный цикл с ERID-токнами.
- **Education & Events**: Вебинары (WebRTC), бронирование, трансляции.
- **Retail & Inventory**: Многоскладской учет, уведомления о низком остатке.
- **Restaurants & Food**: QR-заказы, интеграция с кухней.
- **Taxi & Logistics**: Тепловые карты спроса, AI-распределитель.
- **Clinical & Vet**: ТВ-видеоконсультации (Appointments API).

## ZERO TRUST SECURITY & ARCHITECTURE
- **Multi-tenancy**: Strict Schema Isolation (`stancl/tenancy` v3).
- **Secrets Management**: Doppler Enforced (Zero Trust 2026). No `.env` files.
- **Audit Trace**: Full Traceability via `correlation_id` on every mutation.
- **Isolation**: BaseSecurityPolicy enforced on all Domain resources.
- **Auth**: 2FA (TOTP/SMS) for all administrative roles.
- **Rate Limiting**: Throttling for Payments, Imports, and Notifications.

## ТЕХНИЧЕСКИЙ СТЕК
- **Secrets**: Doppler CLI.
- **Backend**: Laravel 12, Filament 3.2.
- **Queues**: Redis + Laravel Horizon.
- **Search**: Laravel Scout + Typesense (Vector Search ready).
- **Monitoring**: Sentry + Spatie Health + Horizon Dash.

## ЗАПУСК И ДЕПЛОЙ (PRODUCTION)
1. `doppler setup` (Configure project and config)
2. `doppler run -- composer install --optimize-autoloader --no-dev`
3. `doppler run -- php artisan migrate --force` (Central)
4. `doppler run -- php artisan tenants:run migrate --force` (Tenants)
5. `doppler run -- php artisan horizon` (Start Queue Processing)
6. `doppler run -- php artisan generate:docs` (Scribe OpenAPI)
7. `doppler run -- php artisan serve` (Local Development)

### ТЕСТИРОВАНИЕ
- `doppler run -- php artisan test`: Полный прогон Pest-тестов.
- `doppler run -- php artisan test --profile`: Проверка производительности тяжелых ML-запросов.

---
*Проект завершен и закален для релиза 2026. Zero Trust Enforced.*
*ВАЖНО: Использование .env запрещено. Все секреты управляются через Doppler.*
