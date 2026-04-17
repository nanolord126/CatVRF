# ClickHouse Deployment Guide

**Дата:** 17 апреля 2026  
**Проект:** CatVRF  
**Версия:** 1.0.0

## Обзор

Руководство по развертыванию ClickHouse кластера для аналитики квот tenant'ов в production среде.

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

### Вариант 1: Docker Compose (Development/Testing)

#### docker-compose.yml

```yaml
version: '3.8'

services:
  clickhouse:
    image: clickhouse/clickhouse-server:24.3
    container_name: catvrf-clickhouse
    ports:
      - "8123:8123"
      - "9000:9000"
    volumes:
      - clickhouse_data:/var/lib/clickhouse
      - clickhouse_logs:/var/log/clickhouse-server
      - ./config/clickhouse/config.xml:/etc/clickhouse-server/config.d/custom.xml:ro
      - ./config/clickhouse/users.xml:/etc/clickhouse-server/users.d/custom.xml:ro
    environment:
      CLICKHOUSE_DB: catvrf_analytics
      CLICKHOUSE_USER: catvrf_admin
      CLICKHOUSE_PASSWORD: ${CLICKHOUSE_PASSWORD}
      CLICKHOUSE_DEFAULT_ACCESS_MANAGEMENT: 1
    ulimits:
      nofile:
        soft: 262144
        hard: 262144
    healthcheck:
      test: ["CMD", "clickhouse-client", "--query", "SELECT 1"]
      interval: 30s
      timeout: 10s
      retries: 3
    networks:
      - catvrf_network

volumes:
  clickhouse_data:
    driver: local
  clickhouse_logs:
    driver: local

networks:
  catvrf_network:
    driver: bridge
```

#### config/clickhouse/config.xml

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

    <users_config>users.d/custom.xml</users_config>
    <default_profile>default</default_profile>
    <default_database>catvrf_analytics</default_database>

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

#### config/clickhouse/users.xml

```xml
<clickhouse>
    <users>
        <catvrf_admin>
            <password>${CLICKHOUSE_PASSWORD}</password>
            <access_management>1</access_management>
            <networks>
                <ip>::/0</ip>
            </networks>
            <profile>default</profile>
            <quota>default</quota>
            <databases>
                <database>catvrf_analytics</database>
            </databases>
        </catvrf_admin>

        <catvrf_app>
            <password>${CLICKHOUSE_APP_PASSWORD}</password>
            <networks>
                <ip>::/0</ip>
            </networks>
            <profile>default</profile>
            <quota>default</quota>
            <databases>
                <database>catvrf_analytics</database>
            </databases>
            <readonly>1</readonly>
        </catvrf_app>
    </users>

    <profiles>
        <default>
            <max_memory_usage>10000000000</max_memory_usage>
            <use_uncompressed_cache>0</use_uncompressed_cache>
            <load_balancing>random</load_balancing>
        </default>
    </profiles>

    <quotas>
        <default>
            <interval>
                <duration>3600</duration>
                <queries>0</queries>
                <errors>0</errors>
                <result_rows>0</result_rows>
                <read_rows>0</read_rows>
                <execution_time>0</execution_time>
            </interval>
        </default>
    </quotas>
</clickhouse>
```

#### Запуск

```bash
# Создать .env файл
cat > .env.clickhouse << EOF
CLICKHOUSE_PASSWORD=your_secure_password_here
CLICKHOUSE_APP_PASSWORD=your_app_password_here
EOF

# Запустить ClickHouse
docker-compose -f docker-compose.clickhouse.yml up -d

# Проверить статус
docker-compose -f docker-compose.clickhouse.yml ps

# Просмотреть логи
docker-compose -f docker-compose.clickhouse.yml logs -f clickhouse
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
        <users_config>users.d/custom.xml</users_config>
        <default_profile>default</default_profile>
        <default_database>catvrf_analytics</default_database>
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
  users.xml: |
    <clickhouse>
        <users>
            <catvrf_admin>
                <password_sha256_hex>YOUR_SHA256_HASH</password_sha256_hex>
                <access_management>1</access_management>
                <networks>
                    <ip>::/0</ip>
                </networks>
                <profile>default</profile>
                <quota>default</quota>
                <databases>
                    <database>catvrf_analytics</database>
                </databases>
            </catvrf_admin>
        </users>
        <profiles>
            <default>
                <max_memory_usage>10000000000</max_memory_usage>
                <use_uncompressed_cache>0</use_uncompressed_cache>
                <load_balancing>random</load_balancing>
            </default>
        </profiles>
        <quotas>
            <default>
                <interval>
                    <duration>3600</duration>
                    <queries>0</queries>
                    <errors>0</errors>
                    <result_rows>0</result_rows>
                    <read_rows>0</read_rows>
                    <execution_time>0</execution_time>
                </interval>
            </default>
        </quotas>
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
        image: clickhouse/clickhouse-server:24.3
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
        - name: users
          mountPath: /etc/clickhouse-server/users.d/custom.xml
          subPath: users.xml
        resources:
          requests:
            memory: "16Gi"
            cpu: "4"
          limits:
            memory: "32Gi"
            cpu: "8"
        livenessProbe:
          exec:
            command:
            - clickhouse-client
            - --query
            - SELECT 1
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          exec:
            command:
            - clickhouse-client
            - --query
            - SELECT 1
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
  - name: users
    configMap:
      name: clickhouse-config
      items:
      - key: users.xml
        path: users.xml
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
# Apply manifests
kubectl apply -f k8s/clickhouse/

# Check status
kubectl get pods -n clickhouse
kubectl get svc -n clickhouse

# Get connection string
kubectl get svc clickhouse -n clickhouse
```

### Вариант 3: Managed Cloud (Recommended for Production)

#### ClickHouse Cloud

```bash
# Sign up at https://clickhouse.com/cloud
# Create cluster via UI or CLI

# CLI installation
curl - https://cli.clickhouse.com | sh

# Create cluster
clickhouse cloud cluster create \
  --name=catvrf-analytics \
  --region=us-east-1 \
  --nodes=3 \
  --type=m-medium

# Get connection string
clickhouse cloud cluster list
```

#### Environment Variables

```env
CLICKHOUSE_HOST=your-cluster.clickhouse.cloud
CLICKHOUSE_PORT=8443
CLICKHOUSE_DATABASE=catvrf_analytics
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=your_password
CLICKHOUSE_TIMEOUT=30
CLICKHOUSE_CONNECT_TIMEOUT=10
```

## Инициализация базы данных

После развертывания ClickHouse, выполните миграции:

```bash
# Запустить миграции ClickHouse
php artisan migrate --path=database/migrations/clickhouse

# Проверить создание таблиц
clickhouse-client --host=CLICKHOUSE_HOST --user=catvrf_admin --password=PASSWORD \
  --query="SHOW TABLES FROM catvrf_analytics"
```

## Мониторинг

### Prometheus Integration

```xml
<!-- config/clickhouse/config.xml -->
<clickhouse>
    <prometheus>
        <endpoint>/metrics</endpoint>
        <port>9363</port>
        <events>true</events>
        <asynchronous_metrics>true</asynchronous_metrics>
        <status_info>true</status_info>
    </prometheus>
</clickhouse>
```

### Grafana Dashboard

Import dashboard from: https://grafana.com/grafana/dashboards/

Key metrics to monitor:
- ClickHouse insert latency
- Queue size for SyncQuotaUsageToClickHouseJob
- Redis-ClickHouse drift count
- Alert firing rate
- Disk usage
- Memory usage
- Query performance

## Backup and Recovery

### Backup

```bash
# Использовать clickhouse-backup
clickhouse-backup create catvrf_analytics_$(date +%Y%m%d)

# Backup to S3
clickhouse-backup upload catvrf_analytics_$(date +%Y%m%d)
```

### Recovery

```bash
# List backups
clickhouse-backup list

# Restore from backup
clickhouse-backup restore catvrf_analytics_20240417
```

## Security

### Network Security

- Ограничить доступ по IP
- Использовать VPN или private network
- Настроить firewall rules

### Authentication

- Использовать сильные пароли
- Включить SSL/TLS
- Настроить RBAC

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
   - Проверить, что ClickHouse запущен
   - Проверить firewall rules
   - Проверить порт (8123 for HTTP, 9000 for native)

2. **Out of memory**
   - Увеличить memory limit
   - Оптимизировать запросы
   - Увеличить кэш

3. **Slow queries**
   - Проверить explain plan
   - Добавить индексы
   - Оптимизировать partitioning

4. **Disk full**
   - Настроить TTL
   - Увеличить disk size
   - Очистить старые данные

### Logs

```bash
# Docker
docker logs catvrf-clickhouse

# Kubernetes
kubectl logs -n clickhouse -l app=clickhouse

# Direct access
tail -f /var/log/clickhouse-server/clickhouse-server.log
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
2. Развернуть ClickHouse кластер
3. Настроить мониторинг
4. Настроить backup
5. Выполнить миграции
6. Настроить environment variables
7. Запустить scheduler jobs
8. Мониторить производительность

## Контакты

Для вопросов по развертыванию:
- DevOps Team: devops@catvrf.ru
- Database Team: dba@catvrf.ru
