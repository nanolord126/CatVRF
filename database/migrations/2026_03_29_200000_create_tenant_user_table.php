<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_user')) {
            return;
        }

        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index()->comment('Tenant ID (UUID or int)');
            $table->unsignedBigInteger('user_id')->index()->comment('User ID');
            $table->string('role')->default('employee')->comment('Role in tenant: admin, owner, manager, employee, accountant');
            $table->boolean('is_active')->default(true)->comment('Is this user-tenant association active');
            $table->string('invitation_token')->nullable()->index()->comment('Token for pending invitation');
            $table->timestamp('invited_at')->nullable()->comment('When the invitation was sent');
            $table->timestamp('accepted_at')->nullable()->comment('When the user accepted the invitation');
            $table->string('correlation_id')->nullable()->index()->comment('Correlation ID for audit');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'role']);

            $table->comment('Pivot table: users <-> tenants with roles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};
