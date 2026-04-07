<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Запустить миграцию (создать таблицу referrals)
     * CANON 2026 - Production Ready
     */
    public function up(): void
    {
        if (Schema::hasTable('referrals')) {
            return;
        }

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referee_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Referral code & tracking
            $table->string('referral_code')->unique()->index()
                ->comment('Уникальный код реферальной ссылки');
            
            // Status tracking
            $table->enum('status', ['active', 'registered', 'qualified', 'rewarded', 'expired', 'inactive'])
                ->default('active')
                ->index()
                ->comment('Статус: active, registered, qualified, rewarded, expired, inactive');
            
            // Migration tracking
            $table->string('source_platform')->nullable()
                ->comment('Платформа, с которой произошла миграция (Yandex, Dikidi, Flowwow)');
            
            $table->timestamp('migrated_at')->nullable()
                ->comment('Дата подтверждения миграции');
            
            // Timestamps
            $table->timestamp('registered_at')->nullable()
                ->comment('Дата регистрации приглашённого');
            
            $table->timestamp('rewarded_at')->nullable()
                ->comment('Дата выплаты бонуса');
            
            // Bonus tracking
            $table->bigInteger('bonus_amount')->nullable()->default(0)
                ->comment('Размер выплаченного бонуса в копейках');
            
            // Tracing & audit
            $table->string('correlation_id')->nullable()->unique()->index()
                ->comment('ID корреляции для трейсирования');
            
            $table->json('tags')->nullable()
                ->comment('Теги для фильтрации: referral, migration, qualified, rewarded');
            
            $table->json('metadata')->nullable()
                ->comment('Дополнительные данные');
            
            $table->timestamps();
            
            // Indices
            $table->index(['tenant_id', 'referrer_id']);
            $table->index(['tenant_id', 'referee_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['referrer_id', 'status']);
            $table->index(['created_at']);
            
            // Table comment
            $table->comment('Реферальные ссылки и отношения пользователей');
        });
    }

    /**
     * Откатить миграцию (удалить таблицу)
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};


