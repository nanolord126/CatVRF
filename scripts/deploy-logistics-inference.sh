#!/bin/bash

# Deploy Logistics Inference Service to Production
# This script deploys the FastAPI service natively (no Docker)

set -e

echo "=== Deploying CatVRF Logistics Inference Service ==="

# Configuration
APP_DIR="python-logistics"
PID_FILE="/tmp/catvrf-logistics-inference.pid"

# Install dependencies
echo "Installing Python dependencies..."
cd "$APP_DIR"
pip install -r requirements.txt

# Stop existing instance if running
if [ -f "$PID_FILE" ]; then
    OLD_PID=$(cat "$PID_FILE")
    if kill -0 "$OLD_PID" 2>/dev/null; then
        echo "Stopping existing instance (PID: $OLD_PID)..."
        kill "$OLD_PID" 2>/dev/null || true
        sleep 2
    fi
    rm -f "$PID_FILE"
fi

# Start service
echo "Starting Logistics Inference Service..."
nohup python main.py > /tmp/catvrf-logistics-inference.log 2>&1 &
echo $! > "$PID_FILE"

cd ..

# Wait for service to be healthy
echo "Waiting for service to be healthy..."
sleep 10

# Health check
echo "Running health check..."
HEALTH_CHECK=$(curl -s http://localhost:8000/health)
echo "Health check result: $HEALTH_CHECK"

if echo "$HEALTH_CHECK" | grep -q "healthy"; then
    echo "✅ Service deployed successfully (PID: $(cat $PID_FILE))"
    exit 0
else
    echo "❌ Service health check failed"
    echo "Check logs: tail -f /tmp/catvrf-logistics-inference.log"
    exit 1
fi
