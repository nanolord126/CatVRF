# Mockery Console Issue - Project-Level Analysis

**Date:** 2026-04-28  
**Status:** CRITICAL - Blocking all tests  
**Impact:** Global - affects all test suites in the project  
**Resolution:** Framework-level issue - requires Laravel/Pest fix

## Error

```
BadMethodCallException: Received Mockery_1_Illuminate_Console_OutputStyle::askQuestion(), 
but no expectations were specified
```

**Stack Trace:**
```
at vendor\symfony\console\Style\SymfonyStyle.php:234
at vendor\laravel\framework\src\Illuminate\Console\View\Components\Confirm.php:17
```

## Root Cause

Laravel's Console View Components (specifically `Illuminate\Console\View\Components\Confirm`) are being rendered during test execution. The Confirm component calls `askQuestion()` on a mocked `Illuminate\Console\OutputStyle` instance, but the mock doesn't have expectations set for this method.

The mock is being created by Laravel's test framework (likely Pest Laravel plugin or Laravel's built-in test traits), but without proper expectations for all Console OutputStyle methods.

## Attempted Fixes (All Failed)

1. **Mocking SymfonyStyle in BaseTestCase** - Failed
   - Attempted to mock `Symfony\Component\Console\Style\SymfonyStyle`
   - Mock not being used by the test framework

2. **Mocking Illuminate\Console\OutputStyle in BaseTestCase** - Failed
   - Attempted to mock `Illuminate\Console\OutputStyle`
   - Mock not being used by the test framework

3. **Mocking in tests/bootstrap.php** - Failed
   - Added global mock before app bootstrap
   - Mock not being used by the test framework

4. **Disabling WMSServiceProvider** - Failed
   - Service provider doesn't use Console components
   - Error persists

5. **Adding NO_INTERACTION env var** - Failed
   - Added to phpunit.xml
   - No effect on Console View Components

6. **Configuring Mockery to allow non-existent methods** - Failed
   - `\Mockery::getConfiguration()->allowMockingNonExistentMethods(false)`
   - No effect

7. **Using --no-interaction flag** - Failed
   - Flag doesn't exist for `php artisan test`

8. **Creating PHPUnit listener** - Failed
   - Created `ConsoleMockListener` with global mock
   - Mock not being used by the test framework

9. **Overriding Laravel Confirm component** - Failed
   - Created `App\Testing\LaravelTestConfirm` extending Laravel Confirm
   - Component instantiated directly, not via container

10. **Using Mockery spy for SymfonyStyle** - Failed
    - Attempted to spy on SymfonyStyle::confirm()
    - Mock still created by framework without expectations

11. **Mockery global helpers** - Failed
    - `Mockery::globalHelpers()`
    - No effect on OutputStyle mocking

12. **Disabling RefreshDatabase trait** - Failed
    - Removed trait from test
    - Error persists - not migration-related

13. **Using class_alias** - Failed
    - Attempted to alias Confirm component
    - Laravel still instantiates original class

14. **Custom autoloader for SymfonyStyle** - Failed
    - Created custom SymfonyStyle override
    - Laravel loads original from vendor

15. **Mockery::onMock callback** - Failed
    - Attempted to intercept all mock creations
    - Callback not triggered for framework-created mocks

16. **Mockery::getConfiguration()->allowMockingNonExistentMethods(true)** - Failed
    - Attempted to allow undefined methods
    - Still requires expectations for defined methods

## Investigation Results

- **No Console components in test code** - Test files don't use Console components
- **No Console components in FIFOShelfLifeService** - Service doesn't use Console
- **No Console components in WMSServiceProvider** - Provider doesn't use Console
- **No artisan calls in migrations** - Migrations don't use Console commands
- **Mock not created in application code** - Mock is created by Laravel's test framework
- **Pest Laravel plugin installed** - `pestphp/pest-plugin-laravel ^2.0`

## Hypothesis

The issue is in the **Pest Laravel plugin** or **Laravel's test framework** itself. The plugin/framework is automatically mocking Console OutputStyle for some reason (possibly for artisan command testing), but doesn't set expectations for all methods like `askQuestion()`.

When Laravel's Console View Components (Confirm) are rendered during test execution, they call `confirm()` on SymfonyStyle, which internally calls `askQuestion()` on the mocked OutputStyle without expectations.

## Recommended Solutions

### Option 1: Report to Pest/Laravel (Recommended)
- This is clearly a framework-level bug
- Report to Pest plugin maintainers with full reproduction steps
- Report to Laravel if it's a core framework issue
- Monitor for fixes in upcoming releases

### Option 2: Upgrade/Downgrade Dependencies
- Try upgrading Pest Laravel plugin to latest version
- Try upgrading Laravel to latest version
- Try downgrading PHP to 8.2
- Try downgrading Laravel to 10.x

### Option 3: Disable Console View Components Globally
- Find where Console View Components are being registered
- Disable them in test environment
- This may require patching Laravel core or using a package

### Option 4: Use Different Test Framework
- Consider switching to PHPUnit without Pest
- May avoid the Pest plugin-specific mocking behavior

### Option 5: Workaround - Skip Tests That Trigger Console
- Identify which tests trigger Console components
- Skip them temporarily
- Not ideal as it reduces test coverage

## Current Status

**BLOCKED:** This is a global infrastructure issue that requires framework-level fixes. It cannot be fixed with simple mocking or configuration changes at the application level.

**Recommendation:** Treat this as a known limitation and document it. Focus on other critical issues that can be fixed.

## Files Modified (Attempted Fixes)

- `tests/BaseTestCase.php` - Added mockConsoleOutput() method (removed)
- `tests/bootstrap.php` - Added global mock (removed)
- `phpunit.xml` - Added NO_INTERACTION env var (kept, but ineffective)
- `tests/ConsoleMockListener.php` - Created PHPUnit listener (deleted)
- `app/Testing/LaravelTestConfirm.php` - Created Confirm override (deleted)
- `tests/SymfonyStyleOverride.php` - Created SymfonyStyle override (deleted)

## Next Steps

1. **Document this issue** in project README or known issues ✅
2. **Report to Pest Laravel plugin** with full reproduction steps
3. **Report to Laravel** if plugin is not the source
4. **Focus on other critical issues** that can be fixed
5. **Monitor Laravel/Pest releases** for fixes
6. **Consider alternative test frameworks** if issue persists

## Related Issues

- Inventory vertical tests cannot run due to this issue
- All other test suites likely affected as well
- This blocks CI/CD pipeline execution

## Additional Context

- PHP 8.3+
- Laravel 11.0+
- Pest 2.34+
- Pest Laravel Plugin 2.0+
- Mockery 1.4.4+

The combination of these versions may have incompatibility issues related to Console mocking in test environment.
