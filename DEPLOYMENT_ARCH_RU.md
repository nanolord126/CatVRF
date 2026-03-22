# CAT-VRF 2026: MULTI-ZONE FEDERATED ARCHITECTURE (FZ-152)

Реализация архитектуры с разделением ПДн (РФ) и бизнес-логики (Global) согласно требованиям 2026 года.

## 🏛️ СХЕМА РАЗМЕЩЕНИЯ (CORE-ZONE vs RU-ZONE)

### 1. RU-ZONE (Yandex Cloud / SberCloud) — "Хранилище персональных данных"

- **Юрисдикция**: Российская Федерация (ФЗ-152, ФЗ-54).
- **Компоненты**:
  - `database-ru`: Postgres (Таблицы: `users`, `personal_access_tokens`, `orders_pii`).
  - `redis-auth`: Хранение сессий пользователей (для авторизации в РФ).
  - `fiscal-proxy`: Sidecar для АТОЛ/СБЕР (ККТ e платежи).
- **Данные**: ФИО, email, телефон, адрес, логины, пароли, платежи.

### 2. CORE-ZONE (Hetzner / Oracle Cloud) — "Бизнес-интеллект (AI)"

- **Юрисдикция**: Global (GDPR Compliance).
- **Компоненты**:
  - `app-backend`: Основной Laravel 12 бэкенд (AI логика, B2B, Supply Chain).
  - `vector-search`: Typesense / Milvus (Векторный поиск, AI Embeddings).
  - `redis-cache`: Кэш приложений, очереди (без ПДн).
- **Данные**: Бизнес-логика, AI модели, складские остатки, логистики, инвойсы (анонимизированные).

## 🔐 БЕЗОПАСНАЯ СВЯЗЬ (WIREGUARD)

Зоны связаны через шифрованный туннель. CORE-ZONE видит только внутренний IP RU-зоны (10.10.10.1).
Прямой доступ извне к БД в RU-зоне закрыт.

## 🛠️ РАЗВЕРТЫВАНИЕ

### Шаг 1: Подготовка РФ-зоны (RU-CORE)

```bash
# На сервере в РФ
docker-compose -f docker-compose.ru.yml up -d
```

### Шаг 2: Подготовка CORE-зоны (APP-CORE)

```bash
# На зарубежном сервере
docker-compose -f docker-compose.core.yml up -d
```

## ⚙️ CONFIGURATION (.env)

### RU-ZONE .env

```env
APP_ZONE=ru
DB_CONNECTION=pgsql_ru
DB_RU_HOST=localhost
DB_RU_DATABASE=pdn_storage
```

### CORE-ZONE .env

```env
APP_ZONE=core
DB_CONNECTION=pgsql_core
DB_RU_HOST=10.10.10.1  # IP через туннель
DB_CORE_HOST=localhost
TYPESENSE_API_KEY=your_key
```

## 🔄 МИГРАЦИЯ ДАННЫХ

Для переноса существующих данных используйте ETL-скрипт (DataLocalizationService):

1. Выгрузить таблицу `users` в `pgsql_ru`.
2. Выгрузить все остальные таблицы в `pgsql_core`.
3. Заменить в `pgsql_core` прямые ссылки на UserID анонимизированными UUID/Hash.

---
**Compliance Status**: 100% FZ-152 Ready. All AI intellectual property is safely outside RU, all citizen PII is strictly inside RU.
