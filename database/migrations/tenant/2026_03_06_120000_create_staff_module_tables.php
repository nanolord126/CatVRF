<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('staff_members')) {
            Schema::create('staff_members', function (Blueprint $table) {
                $table->comment('Сотрудники: персонал, контакты, статусы.');
                $table->id();
                $table->string('name')->comment('ФИО сотрудника');
                $table->string('position')->comment('Должность');
                $table->timestamps();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('staff_members'); }
};
