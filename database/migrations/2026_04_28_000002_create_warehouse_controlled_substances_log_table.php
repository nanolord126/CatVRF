<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Warehouse Controlled Substances Log Table
 * 
 * Таблица для логирования операций с наркотическими и психотропными веществами
 * в соответствии с требованиями ФСБ и Постановлением Правительства РФ № 371
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_controlled_substances_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('second_user_id')->nullable()->constrained('users')->onDelete('restrict')->comment('Второй сотрудник для двойного контроля');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->string('product_id')->index()->comment('SKU или ID товара');
            $table->string('product_name')->comment('Наименование товара');
            $table->string('controlled_list', 20)->index()->comment('Список: list_i, list_ii, list_iii');
            $table->enum('operation', [
                'receipt',
                'issue',
                'transfer',
                'write_off',
                'damage',
                'loss',
                'quarantine',
                'return',
                'adjustment'
            ])->index();
            $table->integer('quantity')->unsigned();
            $table->string('unit', 20)->default('pieces');
            $table->string('batch_number')->nullable()->index()->comment('Номер партии');
            $table->string('reason')->comment('Основание для операции');
            $table->string('document_number')->nullable()->comment('Номер документа');
            $table->date('document_date')->nullable()->comment('Дата документа');
            $table->string('recipient_name')->nullable()->comment('Получатель (для отгрузки)');
            $table->string('recipient_inn')->nullable()->comment('ИНН получателя');
            $table->string('ip_address', 45)->comment('IP адрес пользователя');
            $table->string('user_agent')->nullable();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('branch_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            // Indexes
            $table->index(['warehouse_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['controlled_list', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index(['operation', 'created_at']);
            $table->index('created_at');

            // Foreign keys
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });

        // Add comment
        DB::statement("ALTER TABLE warehouse_controlled_substances_log COMMENT = 'Log of operations with controlled substances (narcotics, psychotropics) per FSB requirements'");
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_controlled_substances_log');
    }
};
