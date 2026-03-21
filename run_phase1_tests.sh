#!/bin/bash
cd c:\opt\kotvrf\CatVRF

echo "============================================"
echo "PHASE 1 TEST EXECUTION - SEQUENTIAL"
echo "============================================"
echo ""

# Unit Tests
echo "[1/4] Running Unit Tests (WalletService)..."
php artisan test tests/Unit/Services/Wallet/WalletServiceTestPHPUnit.php --no-coverage
UNIT_EXIT=$?

# Feature Tests (skip broken ones)
echo ""
echo "[2/4] Running Security Integration Tests..."
php artisan test tests/Feature/Security/ --no-coverage
FEATURE_EXIT=$?

# Other tests
echo ""
echo "[3/4] Running Marketplace Tests..."
php artisan test tests/Feature/Marketplace/ --no-coverage 2>&1 | grep -E "(PASS|FAIL|Tests:|Error)"
MARKETPLACE_EXIT=$?

# Summary
echo ""
echo "============================================"
echo "PHASE 1 EXECUTION COMPLETE"
echo "============================================"
echo "Unit Tests Exit Code: $UNIT_EXIT"
echo "Feature Tests Exit Code: $FEATURE_EXIT"
echo "Marketplace Tests Exit Code: $MARKETPLACE_EXIT"

if [ $UNIT_EXIT -eq 0 ] && [ $FEATURE_EXIT -eq 0 ]; then
  echo ""
  echo "✅ PHASE 1 PARTIALLY SUCCESSFUL"
  echo "Some tests executed successfully"
else
  echo ""
  echo "⚠️  SOME TESTS FAILED OR WERE SKIPPED"
fi

