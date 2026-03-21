# Production Deployment Guide — CANON 2026

**Last Updated:** 17 March 2026

---

## Quick Start

```bash
# 1. Clone repository
git clone https://github.com/yourorg/catvrf.git
cd catvrf

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 3. Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with production values

# 4. Database setup
php artisan migrate --force

# 5. Start Octane server
bash scripts/octane-start.sh production
```

---

## System Requirements

- **PHP:** 8.2+ (8.3 recommended for performance)
- **Node.js:** 18.x or 20.x
- **Database:** MariaDB 10.6+ or MySQL 8.0+
- **Memory:** 2GB minimum, 4GB+ recommended
- **CPU:** 2+ cores
- **Disk:** 10GB+ SSD

---

## Installation Steps

### 1. Clone Repository

```bash
git clone https://github.com/yourorg/catvrf.git /var/www/catvrf
cd /var/www/catvrf
```

### 2. Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /var/www/catvrf
```

### 3. Install Node Dependencies

```bash
npm ci
npm run build
```

### 4. Environment Configuration

```bash
cp .env.example .env

# Edit .env with production values:
APP_ENV=production
APP_DEBUG=false
APP_URL=https://catvrf.com

# Database
DB_CONNECTION=mysql
DB_HOST=db.internal
DB_DATABASE=catvrf_prod
DB_USERNAME=catvrf_user
DB_PASSWORD=secure_password_here

# Mail
MAIL_DRIVER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=sendgrid_api_key

# Payment Gateways
TINKOFF_TERMINAL_KEY=your_terminal_key
TINKOFF_SECRET_KEY=your_secret_key
SBERBANK_MERCHANT_ID=your_merchant_id

# Doppler Secrets Management (optional)
DOPPLER_TOKEN=dp.sv.xxx
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Database Setup

```bash
php artisan migrate --force
php artisan db:seed --force  # Seed initial data if needed
```

### 7. Clear Cache

```bash
php artisan optimize:clear
php artisan optimize
php artisan view:cache
php artisan route:cache
php artisan config:cache
```

---

## Octane Server Configuration

### Using Systemd (Recommended)

```bash
# Copy systemd unit file
sudo cp etc/systemd/system/octane.service /etc/systemd/system/

# Edit for your environment
sudo nano /etc/systemd/system/octane.service

# Enable and start
sudo systemctl daemon-reload
sudo systemctl enable octane.service
sudo systemctl start octane.service

# Check status
sudo systemctl status octane.service

# View logs
sudo journalctl -u octane.service -f
```

### Manual Start (Development)

```bash
bash scripts/octane-start.sh development
```

---

## Reverse Proxy Configuration

### Nginx

```nginx
upstream octane {
    server 127.0.0.1:8000;
    server 127.0.0.1:8001;
    keepalive 64;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name catvrf.com www.catvrf.com;

    ssl_certificate /etc/letsencrypt/live/catvrf.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/catvrf.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    client_max_body_size 100M;
    client_body_buffer_size 128k;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        proxy_pass http://octane;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Correlation-ID $request_id;
        proxy_buffering off;
    }

    location ~* /\.(?!well-known\/) {
        deny all;
    }
}

# HTTP redirect to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name catvrf.com www.catvrf.com;
    return 301 https://$server_name$request_uri;
}
```

### Apache

```apache
<VirtualHost *:443>
    ServerName catvrf.com
    ServerAlias www.catvrf.com
    DocumentRoot /var/www/catvrf/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/catvrf.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/catvrf.com/privkey.pem

    <Proxy "http://127.0.0.1:8000">
        ProxySet max=20 keepalive=On ttl=300 timeout=600 acquired=3000
    </Proxy>

    ProxyPreserveHost On
    ProxyPass / http://127.0.0.1:8000/
    ProxyPassReverse / http://127.0.0.1:8000/

    RequestHeader set X-Forwarded-Proto "https"
    RequestHeader set X-Forwarded-For "%{REMOTE_ADDR}s"

    <Directory /var/www/catvrf/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/catvrf-error.log
    CustomLog ${APACHE_LOG_DIR}/catvrf-access.log combined
</VirtualHost>

<VirtualHost *:80>
    ServerName catvrf.com
    ServerAlias www.catvrf.com
    Redirect permanent / https://catvrf.com/
</VirtualHost>
```

---

## Database Optimization

### MariaDB Configuration

```cnf
[mysqld]
# Performance
max_connections = 1000
max_allowed_packet = 256M
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2

# Replication (optional)
server_id = 1
log_bin = /var/log/mysql/mysql-bin.log
binlog_format = ROW
binlog_row_image = MINIMAL

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### Redis Configuration (Caching)

```conf
# /etc/redis/redis.conf

# Memory management
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Replication (optional)
slaveof 192.168.1.100 6379
masterauth your_password
requirepass your_password
```

---

## Monitoring & Logging

### Logging

All logs stored in `/var/log/catvrf/`:

```bash
# Laravel application logs
tail -f storage/logs/laravel.log

# Octane server logs (via systemd)
journalctl -u octane.service -f

# Nginx access logs
tail -f /var/log/nginx/catvrf-access.log

# Nginx error logs
tail -f /var/log/nginx/catvrf-error.log
```

### Health Check

```bash
# Check application health
curl https://catvrf.com/up

# Check database connection
php artisan db:show

# Check Redis connection
redis-cli ping
```

### Monitoring Tools

- **Sentry:** Error tracking and performance monitoring
- **New Relic:** APM and infrastructure monitoring
- **Datadog:** Real-time monitoring and alerting
- **Prometheus + Grafana:** Metrics collection and visualization

---

## Backup & Recovery

### Daily Backup Strategy

```bash
#!/bin/bash
# /usr/local/bin/backup-catvrf.sh

BACKUP_DIR="/backups/catvrf"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup database
mysqldump -u catvrf_user -p$DB_PASSWORD catvrf_prod | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup file uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/catvrf/storage/app/uploads/

# Upload to S3
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://catvrf-backups/
aws s3 cp $BACKUP_DIR/uploads_$DATE.tar.gz s3://catvrf-backups/

# Cleanup old local backups (keep 7 days)
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete
```

### Restore from Backup

```bash
# Restore database
gunzip < db_20260317_120000.sql.gz | mysql -u catvrf_user -p catvrf_prod

# Restore uploads
tar -xzf uploads_20260317_120000.tar.gz -C /var/www/catvrf/
chown -R www-data:www-data /var/www/catvrf/storage
```

---

## Security Hardening

### File Permissions

```bash
find /var/www/catvrf -type f -exec chmod 644 {} \;
find /var/www/catvrf -type d -exec chmod 755 {} \;
chmod 700 storage bootstrap/cache
chown -R www-data:www-data /var/www/catvrf
```

### Firewall Configuration

```bash
# UFW (Ubuntu)
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP
sudo ufw allow 443/tcp     # HTTPS
sudo ufw enable
```

### SSH Key Authentication

```bash
# Disable password auth, use only keys
sudo sed -i 's/PasswordAuthentication yes/PasswordAuthentication no/g' /etc/ssh/sshd_config
sudo systemctl restart ssh
```

### HTTPS/SSL

```bash
# Let's Encrypt certificate
sudo apt install certbot python3-certbot-nginx
sudo certbot certonly --nginx -d catvrf.com -d www.catvrf.com

# Auto-renewal
sudo systemctl enable certbot.timer
```

---

## Performance Tuning

### PHP-FPM Tuning

```ini
[www]
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
pm.max_requests_grace_period = 30s
request_terminate_timeout = 60s
```

### Octane Optimization

```env
# config/octane.php
OCTANE_SWOOLE_WORKERS=8        # 2x CPU cores
OCTANE_SWOOLE_TASK_WORKERS=8   # 2x CPU cores
OCTANE_SWOOLE_MAX_REQUEST=500  # Force restart periodically
```

---

## Scaling

### Horizontal Scaling (Multiple Octane Instances)

```bash
# Start 4 Octane instances on different ports
for port in {8000..8003}; do
    php artisan octane:start --port=$port &
done

# Load balance with Nginx
upstream octane {
    server 127.0.0.1:8000;
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;
}
```

### Database Replication (Master-Slave)

```sql
-- On master
CHANGE MASTER TO
    MASTER_HOST='192.168.1.100',
    MASTER_USER='replication',
    MASTER_PASSWORD='password',
    MASTER_LOG_FILE='mysql-bin.000001',
    MASTER_LOG_POS=156;

START SLAVE;
```

---

## Troubleshooting

### Octane Not Starting

```bash
# Check for port conflicts
sudo lsof -i :8000

# Check permissions
ls -la /var/www/catvrf

# Check PHP extensions
php -m | grep -i swoole

# Check logs
journalctl -u octane.service -n 50 -e
```

### High Memory Usage

```bash
# Check PHP processes
ps aux | grep php

# Adjust in octane.php
'max_request' => 500  // Force restart after 500 requests
```

### Slow Queries

```bash
# Enable slow query log in MariaDB
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

# Analyze slow queries
mysqldumpslow -s at /var/log/mysql/slow.log | head -20
```

---

## Support & Resources

- **Documentation:** https://laravel.com/docs/octane
- **Octane GitHub:** https://github.com/laravel/octane
- **Community Forum:** https://laracasts.com/discuss

---

**Deployment Status:** ✅ Production Ready  
**Last Verified:** 17 March 2026
