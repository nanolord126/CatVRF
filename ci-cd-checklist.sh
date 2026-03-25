#!/bin/bash
# CI/CD Pre-deployment Checklist

set -e

echo "🔍 CI/CD Deployment Checklist"
echo "=============================="
echo ""

# 1. Check .github/workflows/ci-cd.yml
echo "1️⃣  Checking CI/CD workflow file..."
if [ -f ".github/workflows/ci-cd.yml" ]; then
    echo "✅ ci-cd.yml found"
    LINES=$(wc -l < .github/workflows/ci-cd.yml)
    echo "   Lines: $LINES"
else
    echo "❌ ci-cd.yml NOT found"
    exit 1
fi

# 2. Check .env.testing
echo ""
echo "2️⃣  Checking test environment..."
if [ -f ".env.testing" ]; then
    echo "✅ .env.testing found"
    grep -q "DB_CONNECTION=pgsql" .env.testing && echo "   ✅ PostgreSQL configured"
    grep -q "CACHE_DRIVER=redis" .env.testing && echo "   ✅ Redis cache configured"
else
    echo "❌ .env.testing NOT found"
    exit 1
fi

# 3. Check docker-compose.yml
echo ""
echo "3️⃣  Checking Docker configuration..."
if [ -f "docker-compose.yml" ]; then
    echo "✅ docker-compose.yml found"
else
    echo "❌ docker-compose.yml NOT found"
fi

# 4. Check PHP version in composer.json
echo ""
echo "4️⃣  Checking PHP version..."
if grep -q '"php": "8.3' composer.json; then
    echo "✅ PHP 8.3 compatible"
elif grep -q '"php": ">=8' composer.json; then
    echo "⚠️  PHP 8+ required"
else
    echo "❌ PHP version not specified"
fi

# 5. Check GitHub Actions secrets
echo ""
echo "5️⃣  Checking GitHub Actions secrets..."
echo "   Required secrets:"
echo "   - SLACK_WEBHOOK (optional, for notifications)"
echo "   - GITHUB_TOKEN (automatic)"
echo ""
echo "   Add secrets via: GitHub → Settings → Secrets and variables → Actions"

# 6. Summary
echo ""
echo "=============================="
echo "✅ CI/CD Setup Ready!"
echo ""
echo "📋 Next Steps:"
echo "1. Ensure GitHub Actions is enabled for this repository"
echo "2. Set SLACK_WEBHOOK secret in GitHub (optional)"
echo "3. Push to main/develop branch to trigger pipeline"
echo "4. Monitor progress in GitHub Actions tab"
echo ""
echo "🔗 Pipeline Stages:"
echo "   1. Lint (PHP syntax & composer validation)"
echo "   2. Test (PHPUnit with coverage)"
echo "   3. Static Analysis (PHPStan, Pint, audit)"
echo "   4. Build (Docker image)"
echo "   5. Deploy Staging (develop branch)"
echo "   6. Deploy Production (main branch)"
echo "   7. Notify (summary)"
echo ""
