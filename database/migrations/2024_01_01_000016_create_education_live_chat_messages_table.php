<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_live_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('id', 64)->unique();
            $table->foreignId('session_id')->constrained('education_live_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('sender_type', ['teacher', 'student', 'ai'])->default('student');
            $table->text('message');
            $table->timestamps();

            $table->index(['session_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_live_chat_messages');
    }
};
