# ClickHouse Deployment Guide

**Дата:** 28 апреля 2026  
**Проект:** CatVRF  
**Версия:** 2.0.0

## Обзор

Руководство по развертыванию ClickHouse для аналитики CatVRF marketplace.

## Требования

### Минимальные требования
- CPU: 4 cores
- RAM: 16 GB
- Disk: 500 GB SSD (NVMe рекомендуется)
- Network: 1 Gbps

### Рекомендуемые требования (production)
- CPU: 8+ cores
- RAM: 32+ GB
- Disk: 1+ TB SSD (NVMe)
- Network: 10 Gbps
- Replication: 3+ nodes

## Варианты развертывания

### Вариант 1: Нативная установка (WSL/Linux)

#### Установка на WSL (Windows)

```bash
# Открыть WSL
wsl -d Ubuntu

# Установить ClickHouse
sudo apt-get install -y apt-transport-https ca-certificates curl gnupg
curl -fsSL 'https://packages.clickhouse.com/rpm/lts/repodata/repomd.xml.key' | sudo gpg --dearmor -o /usr/share/keyrings/clickhouse-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/clickhouse-keyring.gpg] https://packages.clickhouse.com/deb stable main" | sudo tee /etc/apt/sources.list.d/clickhouse.list
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y clickhouse-server clickhouse-client

# Запустить сервер
sudo -u clickhouse /usr/bin/clickhouse server --config-file /etc/clickhouse-server/config.xml &

# Проверить
clickhouse-client --query 'SELECT version()'
```

#### Установка на Linux (Ubuntu/Debian)

```bash
sudo apt-get install -y apt-transport-https ca-certificates curl gnupg
curl -fsSL 'https://packages.clickhouse.com/rpm/lts/repodata/repomd.xml.key' | sudo gpg --dearmor -o /usr/share/keyrings/clickhouse-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/clickhouse-keyring.gpg] https://packages.clickhouse.com/deb stable main" | sudo tee /etc/apt/sources.list.d/clickhouse.list
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y clickhouse-server clickhouse-client

# Запустить как сервис
sudo systemctl enable clickhouse-server
sudo systemctl start clickhouse-server
```

#### Создание базы данных и миграции

```bash
# Создать базу данных
clickhouse-client --query 'CREATE DATABASE IF NOT EXISTS catvrf_bigdata'

# Выполнить миграции Big Data
clickhouse-client --database catvrf_bigdata --queries-file database/clickhouse/bigdata_schema.sql

# Выполнить миграции feature store
clickhouse-client --database catvrf_bigdata --queries-file database/clickhouse/feature_store.sql

# Проверить таблицы
clickhouse-client --database catvrf_bigdata --query 'SHOW TABLES'
```

#### Настройка конфигурации

```bash
# Редактировать конфигурацию
sudo nano /etc/clickhouse-server/config.xml
```

Рекомендуемые настройки:

```xml
<clickhouse>
    <logger>
        <level>information</level>
        <log>/var/log/clickhouse-server/clickhouse-server.log</log>
        <errorlog>/var/log/clickhouse-server/clickhouse-server.err.log</errorlog>
        <size>100M</size>
        <count>10</count>
    </logger>

    <mark_cache_size>5368709120</mark_cache_size>
    <path>/var/lib/clickhouse/</path>
    <tmp_path>/var/lib/clickhouse/tmp/</tmp_path>
    <user_files_path>/var/lib/clickhouse/user_files/</user_files_path>
    <default_database>catvrf_bigdata</default_database>

    <http_port>8123</http_port>
    <tcp_port>9000</tcp_port>
    <listen_host>0.0.0.0</listen_host>

    <max_connections>4096</max_connections>
    <keep_alive_timeout>3</keep_alive_timeout>
    <max_concurrent_queries>100</max_concurrent_queries>
    <uncompressed_cache_size>8589934592</uncompressed_cache_size>

    <prometheus>
        <endpoint>/metrics</endpoint>
        <port>9363</port>
        <events>true</events>
        <asynchronous_metrics>true</asynchronous_metrics>
        <status_info>true</status_info>
    </prometheus>
</clickhouse>
```

#### Управление сервером (WSL)

```bash
# Запуск
wsl -d Ubuntu -- bash -c "sudo -u clickhouse /usr/bin/clickhouse server --config-file /etc/clickhouse-server/config.xml &"

# Проверка статуса (из Windows)
Invoke-WebRequest -Uri "http://localhost:8123/ping"

# Остановка
wsl -d Ubuntu -- bash -c "clickhouse-client --query 'SYSTEM SHUTDOWN'"

# Логи
wsl -d Ubuntu -- bash -c "sudo tail -f /var/log/clickhouse-server/clickhouse-server.log"
```

### Вариант 2: Kubernetes (Production)

#### Namespace

```yaml
apiVersion: v1
kind: Namespace
metadata:
  name: clickhouse
```

#### ConfigMap

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: clickhouse-config
  namespace: clickhouse
data:
  config.xml: |
    <clickhouse>
        <logger>
            <level>information</level>
            <log>/var/log/clickhouse-server/clickhouse-server.log</log>
            <errorlog>/var/log/clickhouse-server/clickhouse-server.err.log</errorlog>
            <size>100M</size>
            <count>10</count>
        </logger>
        <mark_cache_size>5368709120</mark_cache_size>
        <path>/var/lib/clickhouse/</path>
        <tmp_path>/var/lib/clickhouse/tmp/</tmp_path>
        <user_files_path>/var/lib/clickhouse/user_files/</user_files_path>
        <default_database>catvrf_bigdata</default_database>
        <http_port>8123</http_port>
        <tcp_port>9000</tcp_port>
        <listen_host>::</listen_host>
        <max_connections>4096</max_connections>
        <keep_alive_timeout>3</keep_alive_timeout>
        <max_concurrent_queries>100</max_concurrent_queries>
        <uncompressed_cache_size>8589934592</uncompressed_cache_size>
        <mark_cache_size>5368709120</mark_cache_size>
        <distributed_ddl>
            <enable>true</enable>
        </distributed_ddl>
    </clickhouse>
```

#### StatefulSet

```yaml
apiVersion: apps/v1
kind: StatefulSet
metadata:
  name: clickhouse
  namespace: clickhouse
spec:
  serviceName: clickhouse
  replicas: 3
  selector:
    matchLabels:
      app: clickhouse
  template:
    metadata:
      labels:
        app: clickhouse
    spec:
      containers:
      - name: clickhouse
        image: clickhouse/clickhouse-server:26.3
        ports:
        - containerPort: 8123
          name: http
        - containerPort: 9000
          name: native
        volumeMounts:
        - name: data
          mountPath: /var/lib/clickhouse
        - name: config
          mountPath: /etc/clickhouse-server/config.d/custom.xml
          subPath: config.xml
        resources:
          requests:
            memory: "16Gi"
            cpu: "4"
          limits:
            memory: "32Gi"
            cpu: "8"
        livenessProbe:
          httpGet:
            path: /ping
            port: 8123
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /ping
            port: 8123
          initialDelaySeconds: 10
          periodSeconds: 5
  volumeClaimTemplates:
  - metadata:
      name: data
    spec:
      accessModes: [ "ReadWriteOnce" ]
      storageClassName: fast-ssd
      resources:
        requests:
          storage: 500Gi
  volumes:
  - name: config
    configMap:
      name: clickhouse-config
      items:
      - key: config.xml
        path: config.xml
```

#### Service

```yaml
apiVersion: v1
kind: Service
metadata:
  name: clickhouse
  namespace: clickhouse
spec:
  ports:
  - port: 8123
    name: http
    targetPort: 8123
  - port: 9000
    name: native
    targetPort: 9000
  selector:
    app: clickhouse
  clusterIP: None
```

#### Deployment

```bash
kubectl apply -f k8s/clickhouse/
kubectl get pods -n clickhouse
kubectl get svc -n clickhouse
```

### Вариант 3: ClickHouse Cloud (Managed)

```bash
# Sign up at https://clickhouse.cloud/
# Create cluster via UI or CLI

# CLI
curl https://cli.clickhouse.com | sh
clickhouse cloud cluster create --name=catvrf-bigdata --region=us-east-1
```

#### Environment Variables

```env
CLICKHOUSE_HOST=your-cluster.clickhouse.cloud
CLICKHOUSE_PORT=8443
CLICKHOUSE_DATABASE=catvrf_bigdata
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=your_password
```

## Инициализация базы данных

```bash
# Нативная установка
clickhouse-client --query 'CREATE DATABASE IF NOT EXISTS catvrf_bigdata'
clickhouse-client --database catvrf_bigdata --queries-file database/clickhouse/bigdata_schema.sql

# Проверить
clickhouse-client --database catvrf_bigdata --query 'SHOW TABLES'
```

## Мониторинг

### Prometheus Integration

ClickHouse встроенный endpoint:

```bash
curl http://localhost:9363/metrics
```

### Grafana Cloud

Sign up at https://grafana.com/auth/sign-up/cloud/ (free tier).

Key metrics:
- Insert latency
- Query performance
- Disk usage
- Memory usage
- Event volume

## Backup and Recovery

### Backup

```bash
clickhouse-backup create catvrf_bigdata_$(date +%Y%m%d)
clickhouse-backup upload catvrf_bigdata_$(date +%Y%m%d)
```

### Recovery

```bash
clickhouse-backup list
clickhouse-backup restore catvrf_bigdata_20260428
```

## Security

### Network Security
- Ограничить доступ по IP
- Использовать VPN или private network
- Настроить firewall rules

### Authentication

```sql
CREATE USER bigdata_user IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT ON catvrf_bigdata.* TO bigdata_user;
```

### Data Encryption
- At rest: ClickHouse native encryption
- In transit: SSL/TLS

## Performance Tuning

### Memory Settings

```xml
<max_memory_usage>10000000000</max_memory_usage>
<max_memory_usage_for_user>8000000000</max_memory_usage_for_user>
```

### Cache Settings

```xml
<uncompressed_cache_size>8589934592</uncompressed_cache_size>
<mark_cache_size>5368709120</mark_cache_size>
```

### Concurrency

```xml
<max_concurrent_queries>100</max_concurrent_queries>
<max_concurrent_queries_for_user>10</max_concurrent_queries_for_user>
```

## Troubleshooting

### Common Issues

1. **Connection refused**
   - Проверить, что ClickHouse запущен: `curl http://localhost:8123/ping`
   - Проверить firewall rules
   - Проверить порт (8123 for HTTP, 9000 for native)

2. **User mismatch error**
   - Запускать под пользователем clickhouse: `sudo -u clickhouse clickhouse-server ...`
   - Проверить права: `sudo chown -R clickhouse:clickhouse /var/lib/clickhouse`

3. **Out of memory**
   - Увеличить memory limit
   - Оптимизировать запросы

4. **Slow queries**
   - Проверить explain plan
   - Добавить индексы
   - Оптимизировать partitioning

### Logs

```bash
# Нативная установка
tail -f /var/log/clickhouse-server/clickhouse-server.log
tail -f /var/log/clickhouse-server/clickhouse-server.err.log

# Kubernetes
kubectl logs -n clickhouse -l app=clickhouse

# WSL (из Windows)
wsl -d Ubuntu -- bash -c "sudo tail -f /var/log/clickhouse-server/clickhouse-server.log"
```

## Масштабирование

### Horizontal Scaling
- Добавить реплики в StatefulSet
- Настроить replication
- Использовать distributed tables

### Vertical Scaling
- Увеличить CPU/memory limits
- Увеличить disk size
- Оптимизировать кэш

## Следующие шаги

1. Выбрать вариант развертывания
2. Развернуть ClickHouse
3. Создать базу данных `catvrf_bigdata`
4. Выполнить миграции
5. Настроить мониторинг
6. Настроить backup
7. Настроить environment variables
8. Запустить scheduler jobs
