<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для расширения полей свадебных мероприятий в соответствии с КАНОНОМ 2026.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            // Флаг свадебного мероприятия (если ещё нет или для уточнения)
            if (!Schema::hasColumn('appointments', 'is_wedding_event')) {
                $table->boolean('is_wedding_event')->default(false)->index()->after('is_group');
            }
            
            // Дата свадьбы (может отличаться от даты записи на услугу)
            if (!Schema::hasColumn('appointments', 'wedding_date')) {
                $table->date('wedding_date')->nullable()->after('is_wedding_event');
            }

            // Имя невесты
            if (!Schema::hasColumn('appointments', 'bride_name')) {
                $table->string('bride_name')->nullable()->after('wedding_date');
            }

            // Тип свадебного пакета (Silver, Gold, Platinum, Custom)
            if (!Schema::hasColumn('appointments', 'wedding_package_type')) {
                $table->string('wedding_package_type')->nullable()->after('bride_name');
            }

            // Количество вовлеченных гостей (подружки невесты, мамы и т.д.)
            if (!Schema::hasColumn('appointments', 'number_of_guests_involved')) {
                $table->integer('number_of_guests_involved')->default(0)->after('wedding_package_type');
            }

            // Correlation ID для трассировки изменений согласно КАНОНУ
            if (!Schema::hasColumn('appointments', 'correlation_id')) {
                $table->string('correlation_id')->nullable()->index();
            }

            $table->comment('Специализированные поля для свадебных мероприятий Beauty 2026');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn([
                    'is_wedding_event', 
                    'wedding_date', 
                    'bride_name', 
                    'wedding_package_type', 
                    'number_of_guests_involved'
                ]);
            });
        }
    }
};
