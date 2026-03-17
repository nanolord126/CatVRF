<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('payouts')) {
            Schema::create('payouts', function (Blueprint $table) {
                $table->comment('Выплаты: зарплата, комиссии, бонусы.');
                $table->id();
                $table->decimal('amount', 15, 2);
                $table->string('status')->default('pending');
                $table->timestamps();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('payouts'); }
};
