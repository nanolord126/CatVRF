<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('hr_employees')) {
            Schema::create('hr_employees', function (Blueprint $table) {
                $table->comment('HR: сотрудники.');
                $table->id();
                $table->string('name');
                $table->string('department');
                $table->string('position');
                $table->timestamps();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('hr_employees'); }
};
