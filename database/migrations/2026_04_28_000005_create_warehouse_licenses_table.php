<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Warehouse Licenses Table
 * 
 * Таблица для хранения лицензий складов в соответствии с ФЗ-323
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->enum('license_type', [
                'pharmaceutical',
                'narcotic',
                'psychotropic',
                'controlled',
            ])->index()->comment('Тип лицензии');
            $table->string('license_number')->unique()->comment('Номер лицензии');
            $table->date('issue_date')->comment('Дата выдачи');
            $table->date('expiry_date')->index()->comment('Дата истечения');
            $table->enum('status', ['active', 'suspended', 'revoked', 'expired'])->default('active')->index();
            $table->text('issuing_authority')->comment('Орган выдавший лицензию');
            $table->text('license_scope')->nullable()->comment('Сcope лицензии');
            $table->json('restrictions')->nullable()->comment('Ограничения лицензии');
            $table->boolean('has_temporary_restrictions')->default(false);
            $table->text('suspension_reason')->nullable();
            $table->date('suspension_date')->nullable();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            // Indexes
            $table->index(['warehouse_id', 'license_type']);
            $table->index(['warehouse_id', 'status']);
            $table->index(['license_type', 'expiry_date']);
            $table->index('created_at');
        });

        // Add comment
        DB::statement("ALTER TABLE warehouse_licenses COMMENT = 'Warehouse licenses per FZ-323 (pharmaceutical activity)'");
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_licenses');
    }
};
