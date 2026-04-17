<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_live_participants', function (Blueprint $table) {
            $table->id();
            $table->string('id', 64)->unique();
            $table->foreignId('session_id')->constrained('education_live_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['teacher', 'student'])->default('student');
            $table->string('token');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamps();

            $table->index(['session_id', 'user_id']);
            $table->index(['session_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_live_participants');
    }
};
