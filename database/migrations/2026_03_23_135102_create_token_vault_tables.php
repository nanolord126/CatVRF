<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('encrypted_secrets')) {
            Schema::create('encrypted_secrets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->unique()->index();
                $table->string('key_name')->index()->comment('Название секрета (напр. TINKOFF_SECRET)');
                $table->text('encrypted_payload')->comment('Зашифрованные данные AES-256-GCM');
                $table->string('encryption_version')->default('1.0');
                $table->timestamp('expires_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Хранилище зашифрованных токенов и секретов');
            });
        }

        if (!Schema::hasTable('token_vault_access_logs')) {
            Schema::create('token_vault_access_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('secret_id')->constrained('encrypted_secrets')->onDelete('cascade');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action')->comment('read, update, delete');
                $table->string('ip_address')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Аудит доступа к секретам');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_vault_access_logs');
        Schema::dropIfExists('encrypted_secrets');
    }
};
