#!/bin/bash

# Logistics AI Integration Test Runner
# Runs all integration tests for the Logistics AI system

set -e

echo "=== CatVRF Logistics AI Integration Test Runner ==="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test results
PASSED=0
FAILED=0

# Function to run a test suite
run_test_suite() {
    local suite_name=$1
    local test_command=$2
    
    echo -e "${YELLOW}Running $suite_name...${NC}"
    
    if eval "$test_command"; then
        echo -e "${GREEN}✓ $suite_name passed${NC}"
        ((PASSED++))
    else
        echo -e "${RED}✗ $suite_name failed${NC}"
        ((FAILED++))
    fi
    echo ""
}

# Check prerequisites
echo "Checking prerequisites..."

# Check if Redis is available
if redis-cli ping > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Redis is running${NC}"
else
    echo -e "${RED}✗ Redis is not running${NC}"
    echo "Please start Redis: sudo systemctl start redis-server"
    exit 1
fi

# Check if ClickHouse is available
if clickhouse-client --query "SELECT 1" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ ClickHouse is running${NC}"
else
    echo -e "${RED}✗ ClickHouse is not running${NC}"
    echo "Please start ClickHouse: sudo -u clickhouse /usr/bin/clickhouse server --config-file /etc/clickhouse-server/config.xml &"
    exit 1
fi

# Check if FastAPI service is running
if curl -s http://localhost:8000/health > /dev/null 2>&1; then
    echo -e "${GREEN}✓ FastAPI service is running${NC}"
else
    echo -e "${YELLOW}⚠ FastAPI service is not running (skipping HTTP tests)${NC}"
fi

echo ""

# Run unit tests
echo "=== Unit Tests ==="
run_test_suite "LogisticsInferenceService Unit Tests" "php artisan test --filter LogisticsInferenceServiceTest"
run_test_suite "Fraud Control Tests" "php artisan test --filter LogisticsInferenceFraudTest"
run_test_suite "A/B Testing Tests" "php artisan test --filter LogisticsABTestServiceTest"
run_test_suite "Filament Resource Tests" "php artisan test --filter FilamentLogisticsAgentResourceTest"

# Run feature tests
echo "=== Feature Tests ==="
run_test_suite "LogisticsInference Integration Tests" "php artisan test --filter LogisticsInferenceIntegrationTest"
run_test_suite "Load Tests" "php artisan test --filter LogisticsInferenceLoadTest"
run_test_suite "Stress Tests" "php artisan test --filter LogisticsInferenceStressTest"

# Run static analysis
echo "=== Static Analysis ==="
echo "Running Pint..."
if ./vendor/bin/pint --test; then
    echo -e "${GREEN}✓ Pint passed${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ Pint failed${NC}"
    ((FAILED++))
fi
echo ""

echo "Running PHPStan..."
if ./vendor/bin/phpstan analyse modules/GeoLogistics --level=8; then
    echo -e "${GREEN}✓ PHPStan passed${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ PHPStan failed${NC}"
    ((FAILED++))
fi
echo ""

# Print summary
echo "=== Test Summary ==="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed. Please review the output above.${NC}"
    exit 1
fi
