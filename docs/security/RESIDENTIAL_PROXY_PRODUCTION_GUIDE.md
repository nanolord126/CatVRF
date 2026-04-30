# Residential Proxy Detection - Production Guide

## Cloudflare Bot Management Integration

### Setup

1. **Enable Cloudflare Bot Management**
   - Go to Cloudflare Dashboard → Security → Bot Management
   - Enable Bot Management for your domain
   - Configure Bot Classifications

2. **Configure API Access**
   ```bash
   # Add to .env
   CLOUDFLARE_BOT_MANAGEMENT_ENABLED=true
   CLOUDFLARE_API_TOKEN=your_api_token
   CLOUDFLARE_ACCOUNT_ID=your_account_id
   CLOUDFLARE_ZONE_ID=your_zone_id
   ```

3. **Integration Configuration**
   ```php
   // config/proxy-detection.php
   'cloudflare' => [
       'enabled' => env('CLOUDFLARE_BOT_MANAGEMENT_ENABLED', false),
       'api_token' => env('CLOUDFLARE_API_TOKEN'),
       'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
       'zone_id' => env('CLOUDFLARE_ZONE_ID'),
       'use_bot_score' => true,
       'bot_score_threshold' => 30, // Below 30 = likely bot
   ],
   ```

### Using Cloudflare Bot Scores

```php
// In ResidentialProxyDetectionService
$cfBotScore = $this->getCloudflareBotScore($request);
if ($cfBotScore !== null && $cfBotScore < 30) {
    $data['is_residential_proxy'] = true;
    $data['detection_sources'][] = 'cloudflare_bot_management';
    $data['metadata']['cloudflare_bot_score'] = $cfBotScore;
}
```

### Custom WAF Rules

Create Cloudflare WAF rules to block known residential proxy providers:

```
(cf.threat_score > 10) and (http.user_agent contains "bot" or http.user_agent contains "crawler")
```

## IP Intelligence Database Updates

### IPinfo

```bash
# Download IPinfo database
wget https://ipinfo.io/data/free/geoip-country.mmdb.gz
gunzip geoip-country.mmdb.gz
mv geoip-country.mmdb storage/app/maxmind/

# Update via scheduled job
php artisan schedule:run
```

### MaxMind

```bash
# Download MaxMind GeoLite2 database
wget https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Proxy&license_key=YOUR_LICENSE_KEY&suffix=tar.gz
tar -xzf GeoLite2-Proxy.tar.gz
mv GeoLite2-Proxy_*/GeoLite2-Proxy.mmdb storage/app/maxmind/
```

### Custom Database Updates

```php
// Create scheduled job
class UpdateResidentialProxyRangesJob implements ShouldQueue
{
    public function handle(): void
    {
        // Fetch from threat intelligence feeds
        $ranges = $this->fetchFromFstecBdu();
        
        // Update config
        config(['proxy-detection.known_ranges' => $ranges]);
        
        // Clear cache
        Cache::tags(['security', 'proxy_detection'])->flush();
    }
}
```

## Monitoring and Alerting

### Prometheus Metrics

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'catvrf'
    static_configs:
      - targets: ['localhost:9090']
```

Key metrics to monitor:
- `residential_proxy_detections_total` - Total detections
- `residential_proxy_detections_by_risk_level` - Detections by risk
- `residential_proxy_detection_duration_seconds` - Detection latency
- `residential_proxy_cache_hits_total` - Cache hit rate

### Grafana Dashboards

Create a Grafana dashboard with panels:
1. Residential Proxy Detections Over Time
2. Risk Level Distribution
3. Top Proxy Providers
4. Russian Territory Violations
5. Detection Latency
6. Cache Hit Rate

### Alerting Rules

```yaml
# alerting_rules.yml
groups:
  - name: residential_proxy_alerts
    rules:
      - alert: HighResidentialProxyDetectionRate
        expr: rate(residential_proxy_detections_total[5m]) > 10
        for: 5m
        annotations:
          summary: "High residential proxy detection rate"
      
      - alert: CriticalRussianTerritoryViolation
        expr: increase(residential_proxy_russian_violations_total[1h]) > 5
        annotations:
          summary: "Russian territory violations detected"
      
      - alert: DetectionLatencyHigh
        expr: histogram_quantile(0.95, rate(residential_proxy_detection_duration_seconds_bucket[5m])) > 1
        annotations:
          summary: "Residential proxy detection latency high"
```

## Performance Optimization

### Caching Strategy

```php
// Use Redis with tags for cache invalidation
Cache::tags(['security', 'proxy_detection'])->remember(
    "residential_proxy:{$ip}",
    3600, // 1 hour TTL
    fn() => $this->performDetection($ip)
);

// Invalidate on update
Cache::tags(['security', 'proxy_detection'])->flush();
```

### Database Indexing

Ensure proper indexes on `residential_proxy_detections` table:

```sql
CREATE INDEX idx_ip_risk ON residential_proxy_detections(ip_address, risk_level);
CREATE INDEX idx_user_risk ON residential_proxy_detections(user_id, risk_level);
CREATE INDEX idx_detected_at ON residential_proxy_detections(detected_at);
CREATE INDEX idx_provider_detected ON residential_proxy_detections(proxy_provider, detected_at);
```

### ClickHouse Partitioning

```sql
-- Partition by month for efficient querying
ALTER TABLE residential_proxy_detections 
MODIFY PARTITION BY toYYYYMM(detected_at);
```

## Security Considerations

### API Key Management

- Store API keys in environment variables, never in code
- Rotate keys regularly (every 90 days)
- Use separate keys for different environments
- Monitor API key usage for anomalies

### Data Privacy

- Anonymize IP addresses in logs (mask last octet)
- Never store raw medical data with external LLM calls
- Comply with 152-ФZ for personal data protection
- Implement data retention policies (90 days for detections)

### Rate Limiting

```php
// Rate limit IP intelligence API calls
RateLimiter::for('ip-intelligence', function (Request $request) {
    return Limit::perMinute(100)->by($request->ip());
});
```

## Troubleshooting

### High False Positives

1. Review threshold configuration in `config/proxy-detection.php`
2. Check if legitimate corporate VPNs are whitelisted
3. Verify IP intelligence provider accuracy
4. Review behavioral biometrics thresholds

### Detection Latency

1. Check Redis cache hit rate
2. Verify database query performance
3. Profile external API calls
4. Consider using local databases instead of API calls

### Russian Territory Violations

1. Verify user location data accuracy
2. Check GeoIP database is up to date
3. Review territory configuration
4. Ensure VPN detection is working correctly

## Rollback Procedure

If issues occur in production:

1. **Disable detection**
   ```bash
   # .env
   RESIDENTIAL_PROXY_DETECTION_ENABLED=false
   ```

2. **Clear cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. **Monitor logs**
   ```bash
   tail -f storage/logs/security.log
   ```

4. **Rollback migration** (if needed)
   ```bash
   php artisan migrate:rollback --step=1
   ```

## Maintenance Tasks

### Daily
- Review detection logs for anomalies
- Check alert thresholds
- Monitor API quota usage

### Weekly
- Update IP intelligence databases
- Review blacklist/whitelist entries
- Analyze detection patterns

### Monthly
- Rotate API keys
- Review and update thresholds
- Audit Russian territory configuration
- Performance review

## Contact

For production support:
- Security Team: security@catvrf.ru
- DevOps Team: devops@catvrf.ru
- On-call: +7 (XXX) XXX-XX-XX
