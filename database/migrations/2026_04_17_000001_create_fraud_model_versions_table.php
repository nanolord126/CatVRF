<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('fraud_model_versions')) {
            return;
        }

        Schema::create('fraud_model_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('version')->unique()->comment('Version identifier (e.g., 2026-04-17-v1)');
            $table->string('model_type')->default('lightgbm')->comment('Model type: lightgbm, xgboost');
            $table->timestamp('trained_at')->nullable()->comment('When model was trained');
            $table->timestamp('shadow_started_at')->nullable()->comment('When shadow mode started');
            $table->timestamp('promoted_at')->nullable()->comment('When promoted to active');
            $table->boolean('is_shadow')->default(false)->comment('Is model in shadow mode');
            $table->boolean('is_active')->default(false)->comment('Is model currently active for inference');
            $table->boolean('is_rollback_candidate')->default(false)->comment('Can be used for rollback');
            
            // Model metrics
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->decimal('precision', 5, 4)->nullable();
            $table->decimal('recall', 5, 4)->nullable();
            $table->decimal('f1_score', 5, 4)->nullable();
            $table->decimal('auc_roc', 5, 4)->nullable();
            
            // Shadow mode metrics (collected during shadow period)
            $table->decimal('shadow_auc_roc', 5, 4)->nullable()->comment('AUC during shadow mode');
            $table->integer('shadow_predictions_count')->default(0)->comment('Number of predictions in shadow');
            $table->integer('shadow_drift_score')->nullable()->comment('Drift detection score');
            
            // Model storage
            $table->string('file_path')->nullable()->comment('Path to .joblib model file');
            $table->string('file_hash')->nullable()->comment('SHA256 hash for integrity verification');
            $table->boolean('is_encrypted')->default(false)->comment('Is model file encrypted');
            
            // Metadata
            $table->json('feature_importance')->nullable()->comment('Feature importance scores');
            $table->json('training_metadata')->nullable()->comment('Training parameters, dataset size, etc.');
            $table->text('comment')->nullable()->comment('Human-readable notes');
            $table->string('trained_by')->default('system')->comment('Who trained the model');
            $table->string('correlation_id')->nullable()->index()->comment('Training job correlation ID');
            
            // Rollback tracking
            $table->foreignId('rolled_back_from_id')->nullable()->constrained('fraud_model_versions')->nullOnDelete();
            $table->string('rollback_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_shadow']);
            $table->index('trained_at');
            $table->index('shadow_started_at');
            $table->index('promoted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_model_versions');
    }
};
