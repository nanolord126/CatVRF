declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_taste_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->uuid('uuid')->unique()->nullable();
            
            // Explicit preferences (user provided)
            $table->json('explicit_preferences')->nullable()->comment('Явные предпочтения: категории, цвета, размеры, бренды, бюджет');
            
            // Implicit preferences (from behavior)
            $table->json('implicit_preferences')->nullable()->comment('Неявные предпочтения: из истории просмотров, покупок, оценок');
            
            // Confidence scores per category
            $table->json('confidence_scores')->nullable()->comment('Уровень уверенности в предпочтениях по категориям');
            
            // Analysis metadata
            $table->json('analysis_history')->nullable()->comment('История анализов: {date, method, features_count, result}');
            $table->timestamp('last_analyzed_at')->nullable();
            $table->integer('total_behaviors_analyzed')->default(0)->comment('Количество поведенческих точек проанализировано');
            
            // Constructor-specific preferences
            $table->json('interior_style')->nullable()->comment('Стили интерьера: modern, classical, minimalist и т.д.');
            $table->json('beauty_preferences')->nullable()->comment('Предпочтения красоты: {makeup_style, hair_color, skincare_type}');
            $table->json('fashion_preferences')->nullable()->comment('Предпочтения моды: {colors, styles, brands, price_range}');
            $table->json('food_preferences')->nullable()->comment('Предпочтения еды: {cuisines, dietary, price_range}');
            
            // Audit
            $table->string('correlation_id')->nullable()->index();
            $table->string('tenant_id')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'last_analyzed_at']);
            $table->comment('Профиль вкусов пользователя v2.0 с явными и неявными предпочтениями');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_taste_profiles');
    }
};
