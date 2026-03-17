<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Добавляет дополнительные поля к таблице transfers для расширенной функциональности.
     * Production 2026: idempotent, backward compatible.
     */
    public function up(): void
    {
        if (Schema::hasTable('transfers')) {
            Schema::table('transfers', function (Blueprint $table) {
                if (!Schema::hasColumn('transfers', 'reference')) {
                    $table->string('reference')->nullable()->after('status')
                        ->comment('Внешний идентификатор транзакции');
                }
                
                if (!Schema::hasColumn('transfers', 'correlation_id')) {
                    $table->string('correlation_id')->nullable()->index()
                        ->comment('Correlation ID для трассировки');
                }
                
                if (!Schema::hasColumn('transfers', 'tags')) {
                    $table->jsonb('tags')->nullable()
                        ->comment('Теги для категоризации');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transfers')) {
            Schema::table('transfers', function (Blueprint $table) {
                $table->dropColumn(array_filter([
                    Schema::hasColumn('transfers', 'reference') ? 'reference' : null,
                    Schema::hasColumn('transfers', 'correlation_id') ? 'correlation_id' : null,
                    Schema::hasColumn('transfers', 'tags') ? 'tags' : null,
                ]));
            });
        }
    }
};
