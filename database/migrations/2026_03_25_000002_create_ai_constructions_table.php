declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_constructions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tenant_id')->nullable()->index();
            
            // Constructor type
            $table->enum('type', ['interior', 'beauty_look', 'outfit', 'cake', 'menu'])->index();
            
            // Input data
            $table->json('input_data')->nullable()->comment('Входные данные: {photo_path, prompt, parameters}');
            $table->string('photo_path')->nullable()->comment('Путь к загруженному фото');
            
            // Analysis results
            $table->json('analysis_result')->nullable()->comment('Результат анализа фото от ImageAnalysisService');
            
            // Constructor output
            $table->json('construction_data')->nullable()->comment('Результаты конструктора: {items, styles, recommendations}');
            $table->json('recommended_items')->nullable()->comment('Массив рекомендуемых товаров с ID и ценами');
            
            // Taste profile used
            $table->json('taste_profile_used')->nullable()->comment('Снимок профиля вкусов, используемого для конструкции');
            $table->json('explicit_preferences_used')->nullable();
            $table->json('implicit_preferences_used')->nullable();
            
            // Confidence and quality
            $table->float('confidence_score')->default(0.5)->comment('Уверенность конструктора (0-1)');
            $table->json('confidence_breakdown')->nullable()->comment('Распределение уверенности по компонентам');
            
            // Fraud checks
            $table->float('fraud_score')->nullable()->comment('ML фрод-скор для подозрительных запросов');
            $table->boolean('fraud_flagged')->default(false)->index();
            
            // User interaction
            $table->integer('view_count')->default(0)->comment('Сколько раз пользователь просмотрел эту конструкцию');
            $table->boolean('saved')->default(false)->comment('Сохранена ли конструкция в избранное');
            $table->timestamp('saved_at')->nullable();
            
            // Conversion tracking
            $table->integer('items_added_to_cart')->default(0)->comment('Сколько товаров добавлено в корзину');
            $table->integer('items_purchased')->default(0)->comment('Сколько товаров куплено из этой конструкции');
            $table->integer('purchase_total')->default(0)->comment('Общая сумма покупок (копейки)');
            
            // Feedback
            $table->integer('rating')->nullable()->comment('Оценка пользователя (1-5)');
            $table->text('feedback')->nullable()->comment('Отзыв пользователя');
            
            // Audit
            $table->string('correlation_id')->nullable()->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
            
            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['type', 'confidence_score']);
            $table->comment('История AI-конструкций пользователя с анализом вкусов и результатами');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_constructions');
    }
};
