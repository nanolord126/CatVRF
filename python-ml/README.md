# CatVRF ML Service

Python сервис для ML-моделей Unified Logistics Platform CatVRF.

## Возможности

- **Courier Assignment** — скоринг курьеров для оптимального назначения
- **PVZ Scoring** — скоринг пунктов выдачи заказов
- **ETA Prediction** — предсказание времени доставки
- **Route Optimization** — оптимизация маршрутов (OR-Tools VRP)
- **Agentic AI (Кот ИИ)** — обнаружение аномалий и поддержка принятия решений

## Установка

```bash
cd python-ml
pip install -r requirements.txt
cp .env.example .env
# Настройте .env
```

## Запуск

```bash
python main.py
```

Или через uvicorn:

```bash
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```

## API Эндпоинты

### Courier Assignment
```bash
POST /api/v1/predict/courier-assignment
```

### PVZ Scoring
```bash
POST /api/v1/predict/pvz-scoring
```

### ETA Prediction
```bash
POST /api/v1/predict/eta
```

### Route Optimization
```bash
POST /api/v1/optimize/route
```

### Agent AI Analysis
```bash
POST /api/v1/agent/analyze
```

## Мониторинг

- **Metrics**: `/metrics` (Prometheus)
- **Health**: `/health`
- **Root**: `/`

## Структура

```
python-ml/
├── main.py                 # FastAPI приложение
├── requirements.txt        # Зависимости
├── .env.example           # Пример конфигурации
├── app/
│   ├── routers/          # API роутеры
│   │   ├── courier.py    # Courier assignment
│   │   ├── pvz.py        # PVZ scoring
│   │   ├── eta.py        # ETA prediction
│   │   ├── route.py      # Route optimization
│   │   └── agent.py      # Agentic AI
│   ├── core/             # Core модули
│   │   ├── config.py     # Конфигурация
│   │   ├── logging.py    # Логирование
│   │   └── metrics.py    # Prometheus метрики
│   ├── models/           # ML модели (в будущем)
│   └── services/         # Сервисы (в будущем)
└── models/               # Обученные модели
```

## Интеграция с Laravel

Laravel вызывает ML сервис через HTTP (Guzzle) или Redis queue:

```php
// В UnifiedFleetService
$response = Http::post('http://ml-service:8000/api/v1/predict/courier-assignment', [
    'order_id' => $order->id,
    'candidates' => $candidates,
    // ...
]);
```

## Обучение моделей

TODO: Скрипты для обучения будут добавлены позже.

## Агент AI (Кот ИИ)

Интеллектуальный агент для:
- Обнаружения курьеров, застрявших в пробках
- Анализа аномалий трафика
- Балансировки загрузки ПВЗ
- Прогнозирования спроса
- Поддержки диспетчеров в реальном времени
