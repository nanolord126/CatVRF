<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('is_wedding_group')->default(false)->index()->after('is_group');
            $table->date('wedding_date')->nullable()->after('is_wedding_group');
            $table->string('bride_name')->nullable()->after('wedding_date');
            $table->string('group_type')->nullable()->after('bride_name');
            $table->comment('Дополнительные поля для свадебных групп в вертикали Beauty 2026');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['is_wedding_group', 'wedding_date', 'bride_name', 'group_type']);
        });
    }
};
