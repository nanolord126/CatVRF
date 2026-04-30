# CatVRF Monitoring Stack

Полный мониторинг для Laravel Horizon + Supermarket + CRM Sync с интеграцией всех каналов нотификации.

## Структура

```
monitoring/
├── grafana/
│   └── dashboards/
│       └── catvrf-queues-v2.json    # Дашборд для очередей
├── prometheus/
│   └── prometheus.yml                 # Конфигурация Prometheus
├── alertmanager/
│   ├── alertmanager.yml               # Конфигурация Alertmanager
│   ├── rules/
│   │   └── alerts.yml                 # Alert rules
│   └── templates/
│       ├── slack.tmpl                 # Slack шаблоны
│       ├── telegram.tmpl              # Telegram шаблоны
│       └── email.tmpl                 # Email шаблоны
└── terraform/
    ├── main.tf                        # Основной конфиг Terraform
    ├── prometheus.tf                  # Prometheus deployment
    ├── grafana.tf                     # Grafana deployment
    └── alertmanager.tf                # Alertmanager deployment
```

## Компоненты

### 1. Grafana Dashboard

**Файл:** `grafana/dashboards/catvrf-queues-v2.json`

**Панели:**
- Pending Jobs (с thresholds)
- Failed Jobs (критично)
- Cold Chain Orders
- B2B Orders Pending
- Очереди — Pending Jobs (timeseries)
- Failed Jobs по очередям (barchart)
- Supermarket High Queue — Нагрузка
- Среднее время обработки (сек)
- CRM Sync Status
- CRM Sync Fail Rate
- Failed CRM Syncs

**Переменные:**
- `$queue` - фильтрация по очередям

**Импорт:**
1. Grafana → Dashboards → Import
2. Вставить JSON из файла
3. Название: `CatVRF • Мониторинг Очередей & Horizon`
4. UID: `catvrf-queues-v2`

### 2. Prometheus Configuration

**Файл:** `prometheus/prometheus.yml`

**Scrape targets:**
- Prometheus (self)
- Alertmanager
- Horizon Exporter
- Laravel Application
- Redis
- MySQL
- Node Exporter
- CRM Health Check

### 3. Alertmanager Configuration

**Файл:** `alertmanager/alertmanager.yml`

**Receivers:**
- `default` - Slack only
- `critical-alerts` - Telegram + Slack + Email + Webhook
- `warning-alerts` - Telegram + Slack
- `info-alerts` - Slack only
- `supermarket-alerts` - Slack + Telegram (Supermarket specific)
- `crm-alerts` - Slack + Telegram + Email (CRM specific)

**Routing:**
- Critical → critical-alerts
- Warning → warning-alerts
- Info → info-alerts
- Supermarket → supermarket-alerts
- CRM → crm-alerts

**Templates:**
- Slack с цветовой индикацией и кнопками
- Telegram с HTML форматированием
- Email с styled HTML

### 4. Alert Rules

**Файл:** `alertmanager/rules/alerts.yml`

**Группы алертов:**

**horizon_queue_alerts:**
- HighFailedJobs (>8 за 3мин) - critical
- SupermarketQueueOverload (>250 за 5мин) - warning
- CRMSyncHighFailureRate (>0.1/sec за 5мин) - critical
- LongRunningJob (>30сек за 2мин) - warning
- QueueBacklogGrowing (>100 за 10мин) - warning

**supermarket_alerts:**
- ColdChainOrdersDelayed (>50 за 5мин) - critical
- B2BOrdersPending (>30 за 10мин) - warning
- OrderCreationRateDrop (<0.1/sec за 10мин) - warning

**crm_alerts:**
- CRMHealthCheckFailed (2мин) - critical
- FailedCRMSyncs (>10 за 5мин) - warning
- CRMSyncLatencyHigh (p95 > 5сек за 5мин) - warning

**infrastructure_alerts:**
- RedisDown (1мин) - critical
- MySQLDown (1мин) - critical
- HighCPUUsage (>80% за 5мин) - warning
- HighMemoryUsage (>85% за 5мин) - warning
- DiskSpaceLow (<20% за 5мин) - warning

### 5. Terraform Deployment

**Файлы:**
- `terraform/main.tf` - основной конфиг
- `terraform/prometheus.tf` - Prometheus deployment
- `terraform/grafana.tf` - Grafana deployment
- `terraform/alertmanager.tf` - Alertmanager deployment

**Переменные окружения:**
```bash
export SLACK_WEBHOOK_URL="https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK"
export SMTP_SMARTHOST="smtp.gmail.com:587"
export SMTP_FROM="alerts@catvrf.ru"
export SMTP_AUTH_USERNAME="alerts@catvrf.ru"
export SMTP_AUTH_PASSWORD="YOUR_APP_PASSWORD"
export GRAFANA_ADMIN_PASSWORD="YOUR_SECURE_PASSWORD"
```

**Деплой:**
```bash
cd monitoring/terraform
terraform init
terraform plan
terraform apply
```

## Настройка интеграций

### Telegram

1. Создать бота через @BotFather
2. Получить `bot_token`
3. Получить `chat_id` (для персонального или группового чата)
4. Заменить в `alertmanager.yml`:
   - `bot_token: 'YOUR_TELEGRAM_BOT_TOKEN'`
   - `chat_id: 'YOUR_ADMIN_CHAT_ID'`

### Slack

1. Создать Incoming Webhook в Slack Workspace
2. Получить webhook URL
3. Заменить в `alertmanager.yml`:
   - `slack_api_url: 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK'`
4. Создать каналы:
   - `#catvrf-alerts`
   - `#catvrf-critical`
   - `#catvrf-warnings`
   - `#catvrf-info`
   - `#supermarket-alerts`
   - `#crm-alerts`

### Email

1. Настроить Gmail App Password (или другой SMTP)
2. Заменить в `alertmanager.yml`:
   - `smtp_smarthost: 'smtp.gmail.com:587'`
   - `smtp_from: 'alerts@catvrf.ru'`
   - `smtp_auth_username: 'alerts@catvrf.ru'`
   - `smtp_auth_password: 'YOUR_APP_PASSWORD'`

### WhatsApp (через Twilio API)

1. Создать аккаунт Twilio
2. Настроить WhatsApp Sandbox или Production API
3. Получить Account SID и Auth Token
4. Заменить в `alertmanager.yml`:
   - `url: 'https://api.twilio.com/2010-04-01/Accounts/YOUR_TWILIO_ACCOUNT_SID/Messages.json'`
   - `username: 'YOUR_TWILIO_ACCOUNT_SID'`
   - `password: 'YOUR_TWILIO_AUTH_TOKEN'`

### Signal

1. Использовать Signal REST API (через self-hosted или сторонний сервис)
2. Получить API token
3. Заменить в `alertmanager.yml`:
   - `url: 'https://api.signal.org/v1/messages'`
   - `bearer_token: 'YOUR_SIGNAL_API_TOKEN'`

### Viber

1. Создать Viber Public Account
2. Получить API token
3. Заменить в `alertmanager.yml`:
   - `url: 'https://chatapi.viber.com/pa/send_message'`
   - `bearer_token: 'YOUR_VIBER_API_TOKEN'`

### WeChat (для китайского рынка)

1. Создать WeChat Work (企业微信) аккаунт
2. Настроить webhook в WeChat Work
3. Получить webhook key
4. Заменить в `alertmanager.yml`:
   - `url: 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=YOUR_WECHAT_KEY'`

### KakaoTalk (для корейского рынка)

1. Создать KakaoTalk Plus Friend
2. Получить Access Token
3. Заменить в `alertmanager.yml`:
   - `url: 'https://api.kakaotalk.com/v2/api/talk/memo/default/send'`
   - `bearer_token: 'YOUR_KAKAO_ACCESS_TOKEN'`

### iMessage (только для macOS/iOS)

1. Требуется Apple Developer Account
2. Использовать APNS (Apple Push Notification Service)
3. Получить device token и APNS token
4. Заменить в `alertmanager.yml`:
   - `url: 'https://api.push.apple.com/3/device/YOUR_DEVICE_TOKEN'`
   - `bearer_token: 'YOUR_APNS_TOKEN'`

**Примечание:** iMessage интеграция требует macOS/iOS устройства и Apple Developer Program membership. Для production рекомендуется использовать альтернативные каналы.

## Проверка

### Prometheus
```bash
kubectl port-forward -n monitoring svc/prometheus 9090:9090
# Открыть http://localhost:9090
# Проверить Targets → Все должны быть UP
# Проверить Status → Rules → Все правила должны быть активны
```

### Alertmanager
```bash
kubectl port-forward -n monitoring svc/alertmanager 9093:9093
# Открыть http://localhost:9093
# Проверить Status → Silence
# Проверить Alerts → Активные алерты
```

### Grafana
```bash
kubectl port-forward -n monitoring svc/grafana 3000:3000
# Открыть http://localhost:3000
# Логин: admin / password из terraform variable
# Импортировать дашборд из grafana/dashboards/catvrf-queues-v2.json
```

## Тестирование алертов

### Тест Critical Alert
```bash
# Включить test alert в rules.yml временно
kubectl exec -n monitoring prometheus-0 -- wget -qO- 'http://localhost:9090/-/reload'
```

### Тест Notification
```bash
# Проверить отправку в Telegram
curl -X POST https://api.telegram.org/bot<token>/sendMessage -d "chat_id=<chat_id>&text=Test alert"
```

## Maintenance

### Silence Rules
```bash
# Через UI Alertmanager
# Или через API:
curl -X POST http://alertmanager:9093/api/v2/silences \
  -H 'Content-Type: application/json' \
  -d '{
    "matchers": [{"name": "alertname", "value": "HighFailedJobs", "isRegex": false}],
    "startsAt": "2024-04-27T10:00:00Z",
    "endsAt": "2024-04-27T12:00:00Z",
    "comment": "Maintenance window"
  }'
```

### Обновление правил
```bash
# Обновить alerts.yml
kubectl create configmap alertmanager-rules --from-file=alerts.yml --dry-run=client -o yaml -n monitoring | kubectl apply -f -
kubectl exec -n monitoring alertmanager-0 -- wget -qO- 'http://localhost:9093/-/reload'
```

## Мониторинг

### Ключевые метрики
- `sum(horizon_jobs_failed)` - общее количество упавших джобов
- `horizon_jobs_pending{queue="supermarket-high"}` - нагрузка на основную очередь
- `rate(horizon_jobs_failed{queue="crm-sync"}[5m])` - процент ошибок CRM sync
- `supermarket_cold_chain_jobs` - заказы с холодной цепью
- `failed_crm_syncs_count` - проваленные синхронизации

### Дашборды
1. **CatVRF Queues Monitoring** - основной дашборд для очередей
2. **Prometheus Stats** - мониторинг самого Prometheus
3. **Alertmanager Stats** - мониторинг Alertmanager
4. **Node Exporter** - мониторинг сервера

## Troubleshooting

### Алерты не отправляются
1. Проверить Alertmanager logs: `kubectl logs -n monitoring alertmanager-0`
2. Проверить конфигурацию: `kubectl get cm alertmanager-config -n monitoring -o yaml`
3. Проверить тестовый webhook: `curl -X POST <webhook_url> -d '{"text":"test"}'`

### Grafana не показывает данные
1. Проверить datasource: Configuration → Data Sources → Prometheus
2. Проверить Prometheus scrape targets
3. Проверить timezone dashboard

### Prometheus не scrapes targets
1. Проверить `prometheus.yml` конфигурацию
2. Проверить service names и ports
3. Проверить network policies
