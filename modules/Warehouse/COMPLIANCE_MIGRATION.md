# Compliance Database Migrations for Warehouse Vertical

## Required Migrations

### 1. Controlled Substances Log

```php
// database/migrations/XXXX_XX_XX_create_warehouse_controlled_substances_log_table.php

Schema::create('warehouse_controlled_substances_log', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('second_user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->uuid('warehouse_id')->nullable();
    $table->enum('operation', ['receipt', 'issue', 'transfer', 'write_off', 'damage', 'loss']);
    $table->uuid('product_id')->nullable();
    $table->string('product_sku');
    $table->integer('quantity');
    $table->enum('controlled_list', ['list_i', 'list_ii', 'list_iii']);
    $table->text('reason');
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    $table->json('metadata')->nullable();
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();

    $table->index(['user_id', 'created_at']);
    $table->index(['warehouse_id', 'created_at']);
    $table->index(['product_sku', 'created_at']);
    $table->index(['controlled_list', 'created_at']);
});
```

### 2. Audit Trail Table

```php
// database/migrations/XXXX_XX_XX_create_warehouse_audit_trail_table.php

Schema::create('warehouse_audit_trail', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->string('entity_type');
    $table->uuid('entity_id');
    $table->enum('action', ['created', 'updated', 'deleted', 'started', 'completed', 'approved', 'cancelled', 'reserved', 'released']);
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->json('changes')->nullable();
    $table->text('reason')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    $table->json('metadata')->nullable();
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();

    $table->index(['entity_type', 'entity_id']);
    $table->index(['user_id', 'created_at']);
    $table->index(['entity_type', 'created_at']);
    $table->index(['action', 'created_at']);
});
```

### 3. Licenses Table

```php
// database/migrations/XXXX_XX_XX_create_warehouse_licenses_table.php

Schema::create('warehouse_licenses', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('warehouse_id');
    $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
    $table->enum('license_type', [
        'pharmacy',                // Фармацевтическая деятельность
        'controlled_substances',    // Наркотические и психотропные
        'medical_devices',          // Медицинские изделия
        'storage_pharma',          // Хранение лекарств
        'storage_narcotics'         // Хранение наркотических
    ]);
    $table->string('license_number')->unique();
    $table->date('issue_date');
    $table->date('expiry_date');
    $table->string('issuing_authority');
    $table->enum('status', ['active', 'expired', 'suspended', 'revoked'])->default('active');
    $table->text('notes')->nullable();
    $table->json('metadata')->nullable();
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['warehouse_id', 'license_type']);
    $table->index(['status', 'expiry_date']);
});
```

### 4. PII Consent Table

```php
// database/migrations/XXXX_XX_XX_create_warehouse_pii_consent_table.php

Schema::create('warehouse_pii_consent', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('consent_type', [
        'data_processing',         // Обработка персональных данных
        'email_marketing',         // Email-маркетинг
        'sms_marketing',           // SMS-маркетинг
        'analytics',               // Аналитика
    ]);
    $table->boolean('granted')->default(true);
    $table->timestamp('granted_at');
    $table->timestamp('revoked_at')->nullable();
    $table->string('revocation_reason')->nullable();
    $table->text('consent_text')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'consent_type']);
    $table->index(['user_id', 'granted']);
});
```

### 5. Chestny ZNAK Documents Table

```php
// database/migrations/XXXX_XX_XX_create_warehouse_chestny_znak_documents_table.php

Schema::create('warehouse_chestny_znak_documents', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('external_document_id')->unique(); // ID в системе Честный ЗНАК
    $table->string('document_number');
    $table->date('document_date');
    $table->enum('document_type', [
        'lp_introduce_goods',      // Ввод в оборот
        'lp_ship_goods',           // Отгрузка
        'lp_write_off_goods',      // Списание
        'lp_return_goods',         // Возврат
    ]);
    $table->enum('product_group', ['pharma', 'tobacco', 'water']);
    $table->enum('status', ['pending', 'submitted', 'accepted', 'rejected', 'error'])->default('pending');
    $table->integer('total_products')->default(0);
    $table->json('products_data')->nullable(); // CIS codes и quantities
    $table->text('error_message')->nullable();
    $table->timestamp('submitted_at')->nullable();
    $table->timestamp('processed_at')->nullable();
    $table->json('response_data')->nullable();
    $table->json('metadata')->nullable();
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['document_type', 'status']);
    $table->index(['status', 'submitted_at']);
    $table->index(['document_number', 'document_date']);
});
```

### 6. Encryption Keys Table (для PII encryption)

```php
// database/migrations/XXXX_XX_XX_create_warehouse_encryption_keys_table.php

Schema::create('warehouse_encryption_keys', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('key_name')->unique();
    $table->text('encrypted_key'); // Зашифрованный ключ
    $table->enum('algorithm', ['aes-256-gcm', 'aes-256-cbc'])->default('aes-256-gcm');
    $table->enum('status', ['active', 'rotated', 'revoked'])->default('active');
    $table->timestamp('rotated_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->foreignId('rotated_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamps();

    $table->index(['status', 'expires_at']);
});
```

## Migration Execution Order

1. `warehouse_licenses`
2. `warehouse_encryption_keys`
3. `warehouse_pii_consent`
4. `warehouse_controlled_substances_log`
5. `warehouse_audit_trail`
6. `warehouse_chestny_znak_documents`

## Notes

- Все таблицы используют UUID primary keys для совместимости с распределёнными системами
- Все таблицы имеют `correlation_id` для трассировки операций
- Все таблицы используют soft deletes для сохранения истории
- Все чувствительные данные должны быть зашифрованы с использованием ключей из `warehouse_encryption_keys`
