# Mutation Testing Guide for CatVRF

## Overview

Mutation testing with Infection ensures your tests actually catch bugs by introducing small changes (mutations) to your code and verifying that tests fail when they should.

## Installation

```bash
# Add Infection to composer dev dependencies
composer require --dev infection/infection

# Verify installation
vendor/bin/infection --version
```

## Configuration

Configuration is in `infection.json`:
- **Minimum MSI (Mutation Score Indicator)**: 80% overall
- **Minimum Covered MSI**: 90% for covered code
- **Target Directories**: Medical, Payment, FraudML domains
- **Test Framework**: Pest
- **Threads**: 4 parallel processes

## Running Mutation Tests

### Full Mutation Test (Medical, Payment, FraudML)
```bash
vendor/bin/infection
```

### Specific Domain Only
```bash
# Medical only
vendor/bin/infection --filter="App\Domains\Medical"

# Payment only
vendor/bin/infection --filter="App\Domains\Payment"

# FraudML only
vendor/bin/infection --filter="App\Domains\FraudML"
```

### With Coverage Report
```bash
# Generate coverage first
vendor/bin/pest --coverage

# Run infection with coverage
vendor/bin/infection --coverage=coverage.xml
```

### Quick Test (Sample Mutations)
```bash
vendor/bin/infection --test-framework-options="--filter=MedicalEmergencyCriticalPathTest"
```

## Understanding Results

### Mutation Score Indicator (MSI)
- **80%+**: Good - Most mutations caught
- **90%+**: Excellent - Very robust tests
- **<70%**: Poor - Tests missing edge cases

### Escaped Mutations
Mutations that survived (tests didn't catch them) indicate:
1. Missing test cases
2. Dead code
3. Over-specific assertions
4. Test gaps in edge cases

### HTML Report
Open `infection-log.html` for detailed visualization:
- Green: Mutations killed (tests passed ✅)
- Red: Mutations escaped (tests failed ❌)

## Critical Domains for Mutation Testing

### Medical Domain (Priority: CRITICAL)
```bash
vendor/bin/infection --filter="App\Domains\Medical"
```

**Focus Areas:**
- PiiAnonymizerService - NO mutations should escape
- EmergencyRequest handling
- Quota enforcement
- AI diagnostic services

**Threshold:** 90% MSI minimum

### Payment Domain (Priority: CRITICAL)
```bash
vendor/bin/infection --filter="App\Domains\Payment"
```

**Focus Areas:**
- Payment processing
- Transaction atomicity
- Idempotency
- Webhook handling

**Threshold:** 90% MSI minimum

### FraudML Domain (Priority: HIGH)
```bash
vendor/bin/infection --filter="App\Domains\FraudML"
```

**Focus Areas:**
- Fraud detection logic
- ML model integration
- Risk scoring
- Block/allow decisions

**Threshold:** 85% MSI minimum

## CI/CD Integration

### GitHub Actions
```yaml
- name: Run Mutation Tests
  run: |
    vendor/bin/pest --coverage
    vendor/bin/infection --min-msi=80 --min-covered-msi=90
```

### Quality Gates
- Build fails if MSI < 80%
- Build fails if Medical/Payment MSI < 90%
- Build fails if escaped mutations > 5% in critical paths

## Common Issues

### Slow Execution
- Increase threads: `--threads=8`
- Use `--only-covered` to skip uncovered code
- Run specific domains instead of full project

### Timeouts
- Increase timeout: `--timeout=60` (default is 30s)
- Check for infinite loops in code

### Escaped Mutations in Critical Code
1. Add missing test cases
2. Check if mutation is equivalent (no behavior change)
3. Add to ignore list ONLY if justified

## Best Practices

1. **Run Before Every Release**: Especially for Medical/Payment changes
2. **Fix Escaped Mutations**: Don't ignore them without justification
3. **Focus on Critical Paths**: Prioritize Medical emergency, payment processing
4. **Combine with Coverage**: Mutation testing + coverage = confidence
5. **Review HTML Report**: Understand why mutations escaped

## Example Workflow

```bash
# 1. Write/modify code
# 2. Write/update tests
vendor/bin/pest

# 3. Check coverage
vendor/bin/pest --coverage

# 4. Run mutation tests
vendor/bin/infection --filter="App\Domains\Medical"

# 5. Review escaped mutations
# Open infection-log.html

# 6. Add tests for escaped mutations
# Repeat from step 2
```

## Excluding Mutations

Only exclude mutations if:
1. Code is dead/unreachable
2. Mutation is equivalent (no behavior change)
3. Framework limitation (rare)

Add to `infection.json`:
```json
"TrueValue": {
    "ignore": [
        "App\\Domains\\Medical\\Services\\PiiAnonymizerService"
    ]
}
```

## Performance Tips

- Use `--only-covered` to skip uncovered code
- Increase threads based on CPU cores
- Run mutation tests on CI, not locally for full suite
- Use `--git-diff-filter` to test only changed files

## Troubleshooting

### Infection can't find Pest
Ensure `phpunit.customPath` points to `vendor/bin/pest`

### Coverage file not found
Generate coverage first: `vendor/bin/pest --coverage`

### Too many escaped mutations
- Check test quality
- Add integration tests
- Review test assertions for specificity

## Resources

- [Infection Documentation](https://infection.github.io/)
- [Mutation Testing Best Practices](https://infection.github.dev/guide/usage.html)
- [Pest + Infection Integration](https://pestphp.com/docs/plugins/infection)
