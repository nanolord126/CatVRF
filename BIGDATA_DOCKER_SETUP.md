# BigData Local Development - Docker Compose

**Purpose:** Simplified BigData setup for local development without requiring WSL, ClickHouse installation, or Confluent Cloud.

## Quick Start

```bash
# Start all services
docker-compose -f docker-compose-bigdata-dev.yaml up -d

# Check service status
docker-compose -f docker-compose-bigdata-dev.yaml ps

# Stop all services
docker-compose -f docker-compose-bigdata-dev.yaml down

# Stop and remove volumes (clean slate)
docker-compose -f docker-compose-bigdata-dev.yaml down -v
```

## Services

| Service | Port | Description | Access |
|---------|------|-------------|--------|
| **ClickHouse** | 8123 (HTTP), 9000 (Native) | OLAP Database | http://localhost:8123 |
| **Redis** | 6379 | Cache & Message Broker | redis-cli -h localhost |
| **Redis Streams** | 6380 | Real-time Ingestion | redis-cli -p 6380 |
| **MinIO** | 9001 (API), 9002 (Console) | S3-compatible Storage | http://localhost:9002 (minioadmin/minioadmin) |
| **Grafana** | 3000 | Visualization Dashboard | http://localhost:3000 (admin/admin) |
| **Vector** | 8686 | Log Aggregation | http://localhost:8686 |

## Environment Configuration

Update `.env` to use local services:

```env
# ClickHouse
CLICKHOUSE_HOST=localhost
CLICKHOUSE_PORT=8123
CLICKHOUSE_DATABASE=catvrf_analytics
CLICKHOUSE_USER=catvrf
CLICKHOUSE_PASSWORD=catvrf_password

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_STREAMS_HOST=localhost
REDIS_STREAMS_PORT=6380

# S3 (MinIO)
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_ENDPOINT=http://localhost:9001
AWS_BUCKET=catvrf-bigdata
AWS_REGION=us-east-1
```

## Accessing Services

### ClickHouse

```bash
# Using clickhouse-client (in container)
docker exec -it catvrf-clickhouse clickhouse-client --user catvrf --password catvrf_password

# Using HTTP
curl http://localhost:8123 --data "SELECT 1"
```

### Redis

```bash
# Main Redis
docker exec -it catvrf-redis redis-cli

# Redis Streams
docker exec -it catvrf-redis-streams redis-cli
```

### Grafana

1. Open http://localhost:3000
2. Login: admin / admin
3. ClickHouse datasource is auto-configured
4. Import dashboards from `monitoring/grafana/dashboards/`

### MinIO

1. Open http://localhost:9002
2. Login: minioadmin / minioadmin
3. Create bucket: `catvrf-bigdata`

## Development Workflow

### Running BigData Jobs

```bash
# Start services
docker-compose -f docker-compose-bigdata-dev.yaml up -d

# Run Laravel command to ingest data
php artisan bigdata:ingest

# Run analytics job
php artisan bigdata:analyze

# View results in Grafana
open http://localhost:3000
```

### Testing

```bash
# Test ClickHouse connection
php artisan bigdata:test-clickhouse

# Test Redis connection
php artisan bigdata:test-redis

# Test MinIO connection
php artisan bigdata:test-s3
```

## Troubleshooting

### Port Conflicts

If ports are already in use, edit `docker-compose-bigdata-dev.yaml`:

```yaml
clickhouse:
  ports:
    - "18123:8123"  # Change to different port
```

### Permission Issues (Linux/Mac)

```bash
sudo chown -R $USER:$USER .
```

### Volume Cleanup

```bash
# Remove all volumes and start fresh
docker-compose -f docker-compose-bigdata-dev.yaml down -v
docker volume prune
```

### Service Not Starting

```bash
# Check logs
docker-compose -f docker-compose-bigdata-dev.yaml logs clickhouse
docker-compose -f docker-compose-bigdata-dev.yaml logs redis

# Restart specific service
docker-compose -f docker-compose-bigdata-dev.yaml restart clickhouse
```

## Production Deployment

For production, use the existing setup:
- AWS S3 (not MinIO)
- Confluent Cloud or self-hosted Kafka (not Redis Streams)
- Managed ClickHouse or ClickHouse Cloud
- Grafana Cloud

This Docker Compose setup is **for local development only**.

## Migration from WSL Setup

If you were using the WSL-based setup:

1. Export data from WSL ClickHouse
2. Import to Docker ClickHouse
3. Update `.env` to use localhost instead of WSL IP
4. Remove WSL services

```bash
# Export from WSL ClickHouse
clickhouse-client --host 127.0.0.1 --query "SELECT * FROM table" > data.csv

# Import to Docker ClickHouse
cat data.csv | docker exec -i catvrf-clickhouse clickhouse-client --query "INSERT INTO table FORMAT CSV"
```

## Performance Tuning

### ClickHouse

Edit clickhouse config in volume:

```xml
<max_memory_usage>4000000000</max_memory_usage>
<max_threads>4</max_threads>
```

### Redis

Increase memory limit in docker-compose:

```yaml
redis:
  command: redis-server --appendonly yes --maxmemory 4gb
```

## Monitoring

- **Grafana**: http://localhost:3000 - Dashboards and alerts
- **Vector**: http://localhost:8686 - Log aggregation metrics
- **ClickHouse UI**: Use Grafana or Datagrip for queries

## Related Documentation

- [docs/BIGDATA_SETUP.md](docs/BIGDATA_SETUP.md) - Original WSL setup
- [docs/BIGDATA_SRE_RUNBOOK.md](docs/BIGDATA_SRE_RUNBOOK.md) - SRE operations
- [modules/BigData/](modules/BigData/) - BigData module code

## Support

For issues:
1. Check service logs: `docker-compose logs <service>`
2. Verify ports are not in use
3. Check Docker Desktop is running
4. Review this README troubleshooting section
