# PHP Environment Blocker - Critical Issue

**Date:** 2026-04-30  
**Status:** BLOCKING Phase 1 (Critical Fixes)  
**Priority:** CRITICAL

---

## Problem

PHP 8.4.20 on Windows is missing the **openssl extension**, which blocks:
1. `composer install` - required for all development
2. Test execution - required for Phase 1.1
3. Package management - required for any dependency changes

---

## Current Environment

```
PHP Version: 8.4.20 (cli) (built: Apr  8 2026 08:27:36) (NTS Visual C++ 2022 x64)
PHP Configuration: C:\php84\php.ini
Extension Directory: C:\php84\ext
OpenSSL Extension: NOT INSTALLED
```

### Attempted Solutions

1. **Composer install with --disable-tls** - Option does not exist in modern Composer
2. **WSL PHP** - PHP not installed in WSL
3. **Docker** - Docker Desktop not installed

---

## Impact

### Blocked Tasks

**Phase 1.1: Fix test infrastructure**
- Cannot run `composer install` to install dependencies
- Cannot run tests to verify Mockery Console fix
- Cannot install/update Pest or PHPUnit

**Phase 1.2-1.4: Architecture migration**
- Cannot install new packages if needed
- Cannot run tests to verify migration
- Cannot update dependencies

**All development tasks**
- Cannot add new dependencies
- Cannot update existing packages
- Cannot run any composer commands

---

## Required Solutions

### Option 1: Fix Windows PHP (Recommended for local dev)

1. **Download PHP 8.3 with OpenSSL**
   - Download from https://windows.php.net/download/
   - Ensure openssl extension is included
   - Replace current C:\php84\ installation

2. **Enable openssl in php.ini**
   ```ini
   extension=openssl
   ```

3. **Verify installation**
   ```bash
   php -m | grep openssl
   composer install
   ```

### Option 2: Use Docker (Recommended for consistency)

1. **Install Docker Desktop**
   - Download from https://www.docker.com/products/docker-desktop/
   - Install and start Docker Desktop

2. **Use Laravel Sail**
   ```bash
   ./vendor/bin/sail up
   ./vendor/bin/sail composer install
   ./vendor/bin/sail test
   ```

### Option 3: Use WSL (Alternative)

1. **Install PHP in WSL**
   ```bash
   wsl sudo apt update
   wsl sudo apt install php8.3 php8.3-openssl php8.3-pgsql php8.3-redis composer
   ```

2. **Run commands through WSL**
   ```bash
   wsl bash -c "cd /mnt/c/opt/kotvrf/CatVRF && composer install"
   ```

### Option 4: Use XAMPP/WAMP (Quick fix)

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Includes PHP with all extensions

2. **Configure to use XAMPP PHP**
   - Add XAMPP PHP to PATH
   - Update system PATH to prioritize XAMPP

---

## Recommendation

**Use Docker (Option 2)** - This is the most consistent approach because:
- Project already has docker-compose.yml
- Laravel Sail is configured
- Consistent environment across team
- No need to modify system PHP
- Easy to switch between projects

**Steps:**
1. Install Docker Desktop
2. Run `./vendor/bin/sail up` (after composer install works)
3. Use `./vendor/bin/sail` prefix for all commands

---

## Temporary Workaround

If Docker cannot be installed immediately:

1. **Download PHP 8.3 zip with openssl** from windows.php.net
2. Extract to C:\php83\
3. Add C:\php83\ to system PATH (before C:\php84\)
4. Enable openssl in C:\php83\php.ini
5. Run `composer install`

---

## Verification Commands

After fixing the environment, verify:

```bash
# Check PHP version
php -v

# Check openssl extension
php -m | grep openssl

# Test composer
composer --version

# Install dependencies
composer install

# Run tests
php artisan test
```

---

## Related Issues

- **Phase 1.1:** Test infrastructure - Mockery Console issue
- **Phase 1.2-1.4:** Architecture migration
- **All dependency management**

---

## Next Steps

1. **Choose solution** (Docker recommended)
2. **Implement fix**
3. **Verify composer install works**
4. **Unblock Phase 1 tasks**
5. **Continue with critical fixes**

---

**Estimated Time to Fix:** 15-30 minutes (depending on solution chosen)
**Blocker Severity:** CRITICAL - blocks all development work
