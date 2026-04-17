<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('b2b_api_keys')) {
            return;
        }

        Schema::create('b2b_api_keys', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_group_id')->constrained('business_groups')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');                         // "Интеграция с 1С", "Мобильное приложение"
            $table->string('key', 80)->unique();            // b2b_<64 случайных символа>
            $table->string('hashed_key', 64);               // SHA256(key)
            $table->json('permissions')->nullable();        // ['orders.read','orders.write','reports','stock']
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_ip')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['business_group_id', 'is_active']);
            $table->index('hashed_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_api_keys');
    }
};
