# SRE Runbook: Big Data Monitoring — CatVRF

**Версия:** 1.0 (2026-04-28)
**Ответственные:** SRE Team + BigData Team
**Escalation:** L1 → L2 (BigData) → L3 (Architect)

---

## Быстрый справочник

| Алерт | Severity | Dashboard | Первое действие |
|-------|----------|-----------|-----------------|
| BigDataKafkaLagCritical | critical | [Overview](http://grafana:3000/d/bigdata-overview) | Проверить consumers |
| BigDataClickHouseDown | critical | [CH Deep](http://grafana:3000/d/bigdata-clickhouse-deep) | Restart ClickHouse |
| BigDataCLVModelDriftCritical | critical | [CLV](http://grafana:3000/d/bigdata-seller-clv-abtest) | Запустить ретренинг |
| BigDataRawEventsStale | critical | [Overview](http://grafana:3000/d/bigdata-overview) | Проверить Kafka producer |
| BigDataClickHouseDiskCritical | critical | [CH Deep](http://grafana:3000/d/bigdata-clickhouse-deep) | OPTIMIZE + DROP партиций |
| BigDataClickHouseMergeQueueHigh | warning | [CH Deep](http://grafana:3000/d/bigdata-clickhouse-deep) | Подождать или OPTIMIZE |
| BigDataDLQGrowing | warning | [Overview](http://grafana:3000/d/bigdata-overview) | Проанализировать DLQ |
| BigDataSparkJobFailed | critical | [Overview](http://grafana:3000/d/bigdata-overview) | Проверить Spark UI |
| BigDataEventVolumeDrop | warning | [Overview](http://grafana:3000/d/bigdata-overview) | Проверить ingestion |

---

## 1. Kafka Consumer Lag Critical

### Алерт
`BigDataKafkaLagCritical` — lag > 10K сообщений в `bigdata_events`

### Симптомы
- Seller dashboards показывают устаревшие данные
- CLV predictions не обновляются
- A/B test results stale

### Диагностика
```bash
# 1. Проверить Redis stream length
redis-cli XLEN bigdata:events:bigdata_events

# 2. Проверить consumer group pending
redis-cli XPENDING bigdata:events:bigdata_events bigdata_consumer

# 3. Проверить Horizon workers
php artisan horizon:status

# 4. Проверить throughput
php artisan tinker --execute="
  echo Cache::get('bigdata:throughput:bigdata_events');
"
```

### Восстановление
```bash
# 1. Перезапустить consumers (self-healing)
curl -X POST http://app:9100/api/bigdata/monitoring/self-heal \
  -H "Authorization: Bearer $TOKEN"

# 2. Или вручную через Horizon
php artisan horizon:terminate

# 3. Если Redis stream переполнен — обрезать
redis-cli XTRIM bigdata:events:bigdata_events MAXLEN ~10000

# 4. Проверить DLQ
redis-cli XLEN bigdata:dlq:events
```

### Post-Mortem
- Зафиксировать root cause (медленный ClickHouse? упавший worker?)
- Проверить `bigdata.monitoring.alert_threshold_lag` в конфиге

---

## 2. ClickHouse Down / Slow

### Алерт
`BigDataClickHouseDown` или `BigDataClickHouseSlowQueries`

### Диагностика
```bash
# 1. Ping ClickHouse
curl http://clickhouse:8123/ping

# 2. Проверить system.query_log
clickhouse-client --query "
  SELECT query_duration_ms, query, read_rows
  FROM system.query_log
  WHERE type = 'QueryFinish' AND event_date = today()
  ORDER BY query_duration_ms DESC
  LIMIT 10
"

# 3. Проверить merge queue
clickhouse-client --query "
  SELECT table, sum(parts) AS parts, sum(rows) AS rows
  FROM system.parts
  WHERE active = 1 AND database = currentDatabase()
  GROUP BY table
"

# 4. Проверить мутации
clickhouse-client --query "
  SELECT * FROM system.mutations WHERE is_done = 0
"
```

### Восстановление
```bash
# 1. Если медленные запросы — KILL их
clickhouse-client --query "KILL QUERY WHERE query_duration_ms > 60000"

# 2. OPTIMIZE таблиц с большим числом parts
clickhouse-client --query "OPTIMIZE TABLE ch_raw_events FINAL"
clickhouse-client --query "OPTIMIZE TABLE ch_seller_daily_metrics FINAL"

# 3. Если disk full — DROP старые партиции
clickhouse-client --query "
  ALTER TABLE ch_raw_events DROP PARTITION '2026-01'
"

# 4. Maintenance через API
curl -X POST http://app:9100/api/bigdata/monitoring/maintenance \
  -H "Authorization: Bearer $TOKEN"
```

### Prevention
- Настроить `TTL` на всех таблицах (уже в конфиге `bigdata.retention`)
- Запускать OPTIMIZE еженедельно (авто через scheduler)
- Мониторить `catvrf_bigdata_clickhouse_table_parts`

---

## 3. CLV Model Drift

### Алерт
`BigDataCLVModelDriftCritical` — model confidence < 0.92

### Симптомы
- Неверные CLV-предсказания
- Seller recommendations некорректные
- RFM-сегментация сдвинулась

### Диагностика
```bash
# 1. Проверить текущий confidence
clickhouse-client --query "
  SELECT
    avg(model_confidence) AS avg_conf,
    quantile(0.5)(model_confidence) AS median,
    quantile(0.1)(model_confidence) AS p10
  FROM ch_clv_predictions
  WHERE prediction_date >= today() - 7
"

# 2. Сравнить с baseline
clickhouse-client --query "
  SELECT
    avgIf(model_confidence, prediction_date >= today() - 7) AS current,
    avgIf(model_confidence, prediction_date BETWEEN today() - 30 AND today() - 14) AS baseline
  FROM ch_clv_predictions
"

# 3. Проверить segment distribution
clickhouse-client --query "
  SELECT clv_segment, COUNT() FROM ch_clv_predictions
  WHERE prediction_date = today()
  GROUP BY clv_segment
"
```

### Восстановление
```bash
# 1. Запустить ретренинг модели
spark-submit /opt/catvrf/modules/BigData/python/jobs/clv_training_job.py \
  --start_date $(date -d '-60 days' +%Y-%m-%d) \
  --end_date $(date +%Y-%m-%d) \
  --tenant_id 1

# 2. Обновить feature store
spark-submit /opt/catvrf/modules/BigData/python/jobs/feature_store_job.py \
  --date $(date +%Y-%m-%d) \
  --tenant_id 1

# 3. Проверить результат через 30 минут
curl http://app:9100/api/bigdata/monitoring/snapshot \
  -H "Authorization: Bearer $TOKEN" | jq '.clv_drift'
```

### Prevention
- Ретренинг еженедельно (cron)
- PSI/KS-test мониторинг через `BigDataMonitoringFacade::getCLVModelDrift()`
- Alert при accuracy drop > 5% (warning) / > 8% (critical)

---

## 4. Data Freshness Expired

### Алерт
`BigDataDailyMetricsStale` / `BigDataSellerMetricsStale` / `BigDataRawEventsStale`

### Диагностика
```bash
# 1. Проверить freshness через facade
php artisan tinker --execute="
  \$m = app(\Modules\BigData\Monitoring\BigDataMonitoringFacade::class);
  dd(\$m->getDataFreshness('seller_metrics'));
"

# 2. Проверить последнюю запись
clickhouse-client --query "
  SELECT max(created_at) FROM ch_seller_daily_metrics
"

# 3. Проверить Spark job статус
curl http://spark-master:4040/api/v1/applications | jq '.[].name'
```

### Восстановление
```bash
# 1. Перезапустить feature_store_job
spark-submit /opt/catvrf/modules/BigData/python/jobs/feature_store_job.py \
  --date $(date +%Y-%m-%d) \
  --tenant_id 1

# 2. Если Kafka pipeline сломан — см. Runbook #1

# 3. Проверить что данные появились
clickhouse-client --query "
  SELECT count() FROM ch_seller_daily_metrics
  WHERE metric_date = today()
"
```

---

## 5. Spark Job Failed

### Алерт
`BigDataSparkJobFailed`

### Диагностика
```bash
# 1. Проверить Spark UI
curl http://spark-master:4040/api/v1/applications | jq

# 2. Проверить логи
docker logs catvrf-spark-master --tail 100

# 3. Проверить ресурсы
curl http://spark-master:4040/api/v1/applications/0/executors | jq
```

### Восстановление
```bash
# 1. Перезапустить упавший job
spark-submit /opt/catvrf/modules/BigData/python/jobs/<job_name>.py \
  --date $(date +%Y-%m-%d) \
  --tenant_id 1

# 2. Если OOM — увеличить память
spark-submit --executor-memory 8g --driver-memory 4g ...

# 3. Если ClickHouse JDBC timeout — увеличить
spark-submit --conf spark.sql.session.timeZone=UTC \
  --conf spark.network.timeout=600s ...
```

---

## 6. DLQ Growing

### Алерт
`BigDataDLQGrowing` — > 100 сообщений в DLQ

### Диагностика
```bash
# 1. Посмотреть последние DLQ сообщения
redis-cli XRANGE bigdata:dlq:events - + COUNT 10

# 2. Проанализировать ошибки
redis-cli XRANGE bigdata:dlq:events - + COUNT 100 | \
  grep -o '"error":"[^"]*"' | sort | uniq -c | sort -rn
```

### Восстановление
```bash
# 1. Если ошибки временные — перепроцессить
php artisan tinker --execute="
  \$dlqLen = Redis::xlen('bigdata:dlq:events');
  echo \"DLQ size: \$dlqLen\";
  // TODO: implement DLQ reprocessor job
"

# 2. Если ошибки постоянные — исправить schema и перепроцессить
# 3. Очистить DLQ после обработки
redis-cli DEL bigdata:dlq:events
```

---

## 7. ClickHouse Disk Full

### Алерт
`BigDataClickHouseDiskCritical` — > 95% disk usage

### Экстренные действия
```bash
# 1. DROP старые партиции raw_events (самая большая таблица)
clickhouse-client --query "
  SELECT partition, sum(bytes_on_disk) AS size
  FROM system.parts
  WHERE table = 'ch_raw_events' AND active = 1
  GROUP BY partition
  ORDER BY partition
"

# DROP партиции старше retention
clickhouse-client --query "
  ALTER TABLE ch_raw_events DROP PARTITION WHERE
    toDate(partition) < today() - INTERVAL 30 DAY
"

# 2. OPTIMIZE для освобождения места от неактивных parts
clickhouse-client --query "OPTIMIZE TABLE ch_raw_events FINAL"

# 3. Очистить system.query_log
clickhouse-client --query "TRUNCATE TABLE IF EXISTS system.query_log"

# 4. Проверить TTL настроены
clickhouse-client --query "
  SELECT name, ttl_expression FROM system.tables
  WHERE database = currentDatabase()
"
```

### Prevention
- TTL на всех таблицах (raw_events: 90d, daily_metrics: 730d)
- Автоматический OPTIMIZE weekly
- Alert при 85% disk (warning) → даёт время на действия

---

## Correlation ID через весь пайплайн

```
Laravel Event → Kafka Message (header: traceparent)
  → Kafka Consumer (extract traceparent)
    → ClickHouse INSERT (correlation_id column)
      → Spark Job (spark.bigdata.correlation_id)
        → Feature Store (correlation_id в логах)
```

Поиск по correlation ID:
```bash
# В ClickHouse
clickhouse-client --query "
  SELECT * FROM ch_raw_events
  WHERE correlation_id = 'bd_6623a1b4c'
  LIMIT 10
"

# В Loki (через Grafana Explore)
{service="catvrf-bigdata"} |= "bd_6623a1b4c"

# В Tempo (через Grafana Trace view)
Trace ID: bd_6623a1b4c...
```

---

## Self-Healing Автоматика

| Ситуация | Автоматическое действие | Расписание |
|----------|------------------------|------------|
| Consumer lag > threshold | Restart consumers via `BigDataMonitoringFacade::restartConsumers()` | Каждые 5 мин |
| Alert evaluation | `BigDataAlertEvaluator::evaluateAll()` | Каждую 1 мин |
| ClickHouse OPTIMIZE | `BigDataMonitoringFacade::runMaintenance()` | Еженедельно |
| TTL enforcement | ClickHouse built-in TTL | Автоматически |
| DLQ overflow | Alert → SRE ручной анализ | По алерту |

---

## Контакты и Escalation

- **L1 (0-15 мин):** SRE oncall → Telegram @catvrf_sre
- **L2 (15-60 мин):** BigData Team → @catvrf_bigdata
- **L3 (>60 мин):** Architect (Сенсей) → прямой контакт
- **PagerDuty:** https://catvrf.pagerduty.com
