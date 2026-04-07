<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица вызовов экстренных служб: emergency_calls.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('caller_name')->nullable();
            $table->string('caller_phone', 30);
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->string('category', 30)->default('other'); // fire|medical|accident|crime|other
            $table->string('status', 30)->default('new');     // new|dispatched|on_scene|resolved|cancelled|false_call
            $table->string('assigned_unit', 50)->nullable();
            $table->text('dispatcher_notes')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->comment('Emergency service calls (dispatch system)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_calls');
    }
};
