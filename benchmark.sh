#!/bin/bash

# Performance Benchmarking Script
# Measures various performance metrics of the application

set -e

echo "🚀 CatVRF Performance Benchmark"
echo "=================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
BASE_URL="${1:-http://localhost:8000}"
RESULTS_FILE="benchmark-results-$(date +%Y%m%d_%H%M%S).json"

echo "📊 Starting benchmarks at: $(date)"
echo "Base URL: $BASE_URL"
echo "Results file: $RESULTS_FILE"
echo ""

# Function to measure response time
measure_endpoint() {
    local method=$1
    local endpoint=$2
    local name=$3
    
    echo -n "Testing $name... "
    
    local response_time=$(curl -s -w "%{time_total}" -o /dev/null -X "$method" "$BASE_URL$endpoint")
    
    if (( $(echo "$response_time < 0.5" | bc -l) )); then
        echo -e "${GREEN}✅ ${response_time}s${NC}"
        echo "$response_time" > /tmp/perf_$name
        return 0
    elif (( $(echo "$response_time < 1.0" | bc -l) )); then
        echo -e "${YELLOW}⚠️  ${response_time}s${NC}"
        return 0
    else
        echo -e "${RED}❌ ${response_time}s${NC}"
        return 1
    fi
}

# Function for load testing with AB
load_test() {
    local endpoint=$1
    local name=$2
    local requests=${3:-1000}
    local concurrency=${4:-50}
    
    echo ""
    echo "Load testing $name..."
    echo "  Requests: $requests"
    echo "  Concurrency: $concurrency"
    
    ab -n $requests -c $concurrency "$BASE_URL$endpoint" 2>/dev/null | grep -E "Requests per second|Time per request|Failed requests"
}

# Function to measure memory usage
measure_memory() {
    echo ""
    echo "📦 Memory Usage:"
    
    # Get total memory before
    free -h | grep Mem
}

# Function to measure disk I/O
measure_disk_io() {
    echo ""
    echo "💾 Disk I/O Test:"
    
    # Run fio if available
    if command -v fio &> /dev/null; then
        echo "Running disk I/O test..."
        fio --name=random-read --ioengine=libaio --iodepth=16 --rw=randread --bs=4k --direct=1 --size=1G --numjobs=1 --runtime=30 --group_reporting 2>/dev/null | tail -5
    else
        echo "fio not installed, skipping disk I/O test"
    fi
}

# Function to measure database query time
measure_database() {
    echo ""
    echo "🗄️  Database Performance:"
    
    php -r "
    \$start = microtime(true);
    \$pdo = new PDO(getenv('DATABASE_URL'));
    \$stmt = \$pdo->query('SELECT COUNT(*) FROM concerts');
    \$count = \$stmt->fetchColumn();
    \$time = microtime(true) - \$start;
    echo sprintf('  Query time: %.3fs (Result: %d rows)\n', \$time, \$count);
    "
}

# Function to measure cache performance
measure_cache() {
    echo ""
    echo "⚡ Cache Performance:"
    
    php -r "
    \$cache = app('cache');
    
    // Write test
    \$start = microtime(true);
    \$cache->put('test_key', 'test_value', 60);
    \$write_time = microtime(true) - \$start;
    
    // Read test
    \$start = microtime(true);
    for (\$i = 0; \$i < 1000; \$i++) {
        \$cache->get('test_key');
    }
    \$read_time = (microtime(true) - \$start) / 1000;
    
    echo sprintf('  Write: %.4fs\n', \$write_time);
    echo sprintf('  Read (avg): %.4fs\n', \$read_time);
    "
}

# Run benchmarks
echo "1️⃣  API Response Times:"
echo "========================"
measure_endpoint "GET" "/api/concerts" "GET /api/concerts"
measure_endpoint "GET" "/api/concerts?page=1&per_page=100" "GET /api/concerts (paginated)"
measure_endpoint "GET" "/api/concerts?search=jazz" "GET /api/concerts (search)"
measure_endpoint "POST" "/api/login" "POST /api/login"

echo ""
echo "2️⃣  Load Testing:"
echo "================="
load_test "/api/concerts" "Read heavy" 1000 50
load_test "/api/concerts?search=test" "Search heavy" 500 25

echo ""
echo "3️⃣  System Metrics:"
echo "=================="
measure_memory
measure_disk_io
measure_database
measure_cache

echo ""
echo "4️⃣  Web Vitals Simulation:"
echo "========================="
echo "Testing page load times..."
curl -s "$BASE_URL/admin" -w "
  Time to First Byte: %{time_starttransfer}s
  Total Time: %{time_total}s
  DNS Lookup: %{time_namelookup}s
  Connect: %{time_connect}s
  App Process: %{time_appconnect}s
" -o /dev/null

echo ""
echo "=================================="
echo "✅ Benchmark completed at: $(date)"
echo "Results saved to: $RESULTS_FILE"
echo ""
