declare(strict_types=1);

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
        if (Schema::hasTable('api_keys')) {
            return;  // Already exists
        }
        
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->comment('Название API ключа (например "External Integration")');
            
            $table->string('key_hash')->unique();
            $table->comment('SHA-256 hash полного ключа');
            
            $table->char('key_preview', 8);
            $table->comment('Первые 8 символов ключа для отображения в UI');
            
            $table->json('abilities')->nullable();
            $table->comment('Массив разрешений: read, write, payments, refunds и т.д.');
            
            $table->timestamp('expires_at')->nullable();
            $table->comment('Дата истечения ключа');
            
            $table->timestamp('last_used_at')->nullable();
            $table->comment('Когда ключ был последний раз использован');
            
            $table->timestamp('revoked_at')->nullable();
            $table->comment('Дата ревокации ключа (если применимо)');
            
            $table->string('correlation_id')->nullable();
            $table->comment('Correlation ID для аудита');
            
            $table->string('created_by')->nullable();
            $table->comment('Кто создал ключ');
            
            $table->timestamps();
            
            // Индексы
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('expires_at');
            $table->index('revoked_at');
            $table->index('key_preview');
            
            // Table comment
            $table->comment('API Keys для B2B интеграций и внешних сервисов');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
