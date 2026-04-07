<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Расширение таблицы мастеров для AI Beauty Look Constructor.
 * CANON 2026: idempotent, comments, correlation_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('masters')) {
            Schema::table('masters', function (Blueprint $table) {
                if (!Schema::hasColumn('masters', 'preferred_styles')) {
                    $table->json('preferred_styles')->nullable()->comment('Предпочтительные стили мастера (json)');
                }
                if (!Schema::hasColumn('masters', 'price_level')) {
                    $table->integer('price_level')->default(1)->comment('Уровень цен: 1-эконом, 2-стандарт, 3-премиум, 4-люкс');
                }
                if (!Schema::hasColumn('masters', 'specializations_detailed')) {
                    $table->json('specializations_detailed')->nullable()->comment('Детальные специализации (makeup, hair, nails и т.д.)');
                }
                if (!Schema::hasColumn('masters', 'available_slots_cache')) {
                    $table->json('available_slots_cache')->nullable()->comment('Кэш доступных слотов для быстрого поиска');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('masters')) {
            Schema::table('masters', function (Blueprint $table) {
                $table->dropColumn(['preferred_styles', 'price_level', 'specializations_detailed', 'available_slots_cache']);
            });
        }
    }
};


