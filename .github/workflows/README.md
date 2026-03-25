# CI/CD Configuration
This directory contains GitHub Actions workflows for automated testing, building, and deployment.

## Files

### ci-cd.yml (Production Ready)
Main CI/CD pipeline with 6 stages:

1. **Lint** - Code quality and syntax validation
   - Validate composer.json
   - Check PHP syntax across app/

2. **Test** - Unit and feature tests
   - PostgreSQL 16 + Redis 7 services
   - PHP 8.3 environment
   - Migrations with seeding
   - PHPUnit with coverage reporting
   - Upload to Codecov

3. **Static Analysis** - Code quality checks
   - PHPStan level 8 analysis
   - Pint code formatting
   - Security audit via composer

4. **Build** - Docker image building
   - Multi-platform build support
   - Layer caching via GitHub Actions
   - Push to GHCR on success

5. **Deploy Staging** - Deploy to develop branch
   - Validates docker-compose.yml
   - Runs migrations with --force
   - Health checks
   - Slack notifications

6. **Deploy Production** - Deploy to main branch
   - Validates production setup
   - Runs migrations with --force
   - Cache clearing and queue restart
   - Slack success/failure notifications

7. **Notify** - Pipeline completion summary
   - Reports overall job status

## Secrets Required

Add these to GitHub repository settings:
- `SLACK_WEBHOOK` - Webhook URL for Slack notifications

## Environment Files

- `.env.testing` - Test environment configuration
- `.env.staging` - Staging environment (set in deployment)
- `.env.production` - Production environment (set in deployment)

## Triggers

- Push to `main`, `develop`, `staging` branches
- Pull requests to `main`, `develop` branches

## GitHub Actions Used

- `actions/checkout@v4`
- `shivammathur/setup-php@v2`
- `actions/cache@v3`
- `docker/setup-buildx-action@v2`
- `docker/login-action@v2`
- `docker/metadata-action@v4`
- `docker/build-push-action@v4`
- `codecov/codecov-action@v3`
- `8398a7/action-slack@v3`

## Running Locally

Test the CI/CD pipeline locally:

```bash
# Install dependencies
composer install

# Run tests
php artisan test --env=testing

# Run static analysis
vendor/bin/phpstan analyse app --level=8
vendor/bin/pint --test
composer audit

# Build Docker image
docker buildx build . --load -t catvrf:local
```

## Troubleshooting

### Tests failing
- Check `.env.testing` is properly configured
- Ensure PostgreSQL and Redis are running
- Run migrations: `php artisan migrate --env=testing`

### Build failing
- Verify `docker-compose.yml` exists
- Check Docker is running and buildx is available
- Review Docker build logs

### Deploy failing
- Verify deployment secrets are set
- Check Slack webhook URL is valid
- Review deployment logs in GitHub Actions

## Configuration

### Test Database
- Host: localhost:5432
- Database: catvrf_test
- User: postgres
- Password: postgres (test environment only)

### Test Cache
- Redis on localhost:6379
- Database: 0 (default)

### Docker Registry
- Registry: ghcr.io
- Image: ghcr.io/bloggers/catvrf
- Tags: branch name, version, SHA, latest
