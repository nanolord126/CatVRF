# CatVRF PHPStan Custom Rules

Custom PHPStan rules to enforce CatVRF coding standards.

## Available Rules

### NoFraudCheckFirstRule
Checks that public service methods call `FraudControlService::check()` as the first action.

**Error message:** "Public service method must call FraudControlService::check() as first action"

### NoStaticCallsInServicesRule
Prevents Service classes from using `static::` calls, enforcing dependency injection.

**Error message:** "Service classes should not use static:: calls. Use dependency injection instead."

### NoLLMInTransactionRule
Ensures LLM calls (OpenAI, Grok, etc.) are not made inside DB::transaction() blocks.

**Error message:** "LLM calls must not be inside DB::transaction(). Move to async queue job instead."

### DTOImmutabilityRule
Enforces DTO standards:
- Must be `readonly` class
- Must have `fromJson()` static method
- Must have `toArray()` method

**Error messages:**
- "DTO classes must be readonly"
- "DTO classes must have a fromJson() static method"
- "DTO classes must have an toArray() method"

## Usage

Add to `phpstan.neon`:

```neon
includes:
    - phpstan-rules/NoFraudCheckFirstRule.php
    - phpstan-rules/NoStaticCallsInServicesRule.php
    - phpstan-rules/NoLLMInTransactionRule.php
    - phpstan-rules/DTOImmutabilityRule.php

services:
    -
        class: CatVRF\PHPStan\Rules\NoFraudCheckFirstRule
        tags:
            - phpstan.rules.rule
    -
        class: CatVRF\PHPStan\Rules\NoStaticCallsInServicesRule
        tags:
            - phpstan.rules.rule
    -
        class: CatVRF\PHPStan\Rules\NoLLMInTransactionRule
        tags:
            - phpstan.rules.rule
    -
        class: CatVRF\PHPStan\Rules\DTOImmutabilityRule
        tags:
            - phpstan.rules.rule
```

## CatVRF Standards Enforced

Based on `MEMORY[user_global]`:

1. **Fraud check first** - FraudControlService::check() must be first action in public service methods
2. **No static in services** - All dependencies must be injected, no static::
3. **LLM async only** - LLM calls must not be in DB::transaction()
4. **DTO immutability** - DTOs must be readonly with fromJson/toArray
5. **Strict typing** - All files must use declare(strict_types=1)

## Future Enhancements

- Medical data anonymization check
- Cache tags validation
- Redis + Lua script validation for slots
- Embedding generation validation (no fake embeddings)
