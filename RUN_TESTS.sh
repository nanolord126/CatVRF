#!/bin/bash
# PHASE 1 TEST EXECUTION SCRIPT
# Just copy-paste these commands to run all tests

echo "======================================"
echo "PHASE 1 TEST SUITE EXECUTION"
echo "======================================"
echo ""

# COMMAND 1: Quick Unit Tests (20 seconds)
echo "📋 COMMAND 1: Unit Tests (20 sec)"
echo "Code: ./vendor/bin/pest tests/Unit --parallel"
echo "Expected: ✅ 18 tests passed"
echo ""

# COMMAND 2: All Feature Tests (2-3 minutes)
echo "📋 COMMAND 2: Feature Tests (2-3 min)"
echo "Code: ./vendor/bin/pest tests/Feature --parallel"
echo "Expected: ✅ 12 tests passed"
echo ""

# COMMAND 3: Security Tests (1-2 minutes)
echo "📋 COMMAND 3: Security Tests (1-2 min)"
echo "Code: ./vendor/bin/pest tests/Feature/Fraud"
echo "Expected: ✅ 22 tests passed"
echo ""

# COMMAND 4: Chaos Tests (1 minute)
echo "📋 COMMAND 4: Chaos Tests (1 min)"
echo "Code: ./vendor/bin/pest tests/Chaos"
echo "Expected: ✅ 16 tests passed"
echo ""

# COMMAND 5: All PHASE 1 at once (4-5 minutes)
echo "📋 COMMAND 5: All PHASE 1 Tests (4-5 min)"
echo "Code: ./vendor/bin/pest tests/Unit tests/Feature tests/Chaos --parallel"
echo "Expected: ✅ 68 tests passed"
echo ""

# COMMAND 6: Coverage Report (1-2 minutes)
echo "📋 COMMAND 6: Generate Coverage Report (1-2 min)"
echo "Code: ./vendor/bin/pest --coverage --coverage-html=storage/coverage"
echo "Then: open storage/coverage/index.html"
echo "Expected: ✅ 85%+ coverage"
echo ""

# COMMAND 7: Load Test (10 minutes)
echo "📋 COMMAND 7: Load Test with k6 (10 min)"
echo "Terminal 1: php artisan serve"
echo "Terminal 2: k6 run k6/payment-flow-loadtest.js"
echo "Expected: ✅ P95 < 500ms, Error rate < 0.1%"
echo ""

# COMMAND 8: Extended Load Test (30+ minutes)
echo "📋 COMMAND 8: Extended Load Test - Soak (30+ min)"
echo "Code: k6 run k6/payment-flow-loadtest.js --duration 30m --vus 1000"
echo "Expected: ✅ No memory leaks, stable performance"
echo ""

# COMMAND 9: Watch mode for development
echo "📋 COMMAND 9: Watch Mode (Development)"
echo "Code: ./vendor/bin/pest --watch tests/Unit/Services/Wallet"
echo "Expected: Tests re-run on file changes"
echo ""

# COMMAND 10: CI/CD dry run
echo "📋 COMMAND 10: Full CI/CD Pipeline Dry Run"
echo "Code: ./vendor/bin/pest && k6 run k6/payment-flow-loadtest.js"
echo "Expected: ✅ All tests pass + load test passes"
echo ""

echo "======================================"
echo "COPY-PASTE READY COMMANDS:"
echo "======================================"
echo ""

cat << 'EOF'
# Quick Start (30 seconds)
./vendor/bin/pest tests/Unit --parallel

# Full PHASE 1 (5 minutes)
./vendor/bin/pest tests/Unit tests/Feature tests/Chaos --parallel

# With Coverage (7 minutes)
./vendor/bin/pest --coverage --coverage-html=storage/coverage && open storage/coverage/index.html

# Load Test (in separate terminal after app starts)
php artisan serve &
sleep 2
k6 run k6/payment-flow-loadtest.js

# Extended Load Test (30+ min)
php artisan serve &
sleep 2
k6 run k6/payment-flow-loadtest.js --duration 30m --vus 1000
EOF

echo ""
echo "======================================"
echo "✅ READY TO RUN!"
echo "======================================"
