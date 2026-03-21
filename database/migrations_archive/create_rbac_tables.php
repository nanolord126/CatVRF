<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update users table with role field
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('customer')->comment('User role: super_admin, support_agent, customer');
                $table->index('role');
            });
        }

        // Update users table with additional RBAC fields
        if (!Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->comment('User account active status');
                $table->string('uuid')->nullable()->unique()->comment('UUID for public references');
                $table->json('tags')->nullable()->comment('Tags for user categorization');
                $table->index('uuid');
            });
        }

        // Create tenants table
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->comment('UUID for public references');
                $table->string('name')->comment('Tenant business name');
                $table->string('slug')->unique()->comment('URL slug');
                $table->string('inn')->unique()->comment('Russian tax ID');
                $table->string('kpp')->nullable()->comment('Russian location code');
                $table->string('ogrn')->nullable()->comment('Russian registration number');
                $table->string('legal_entity_type')->default('ip')->comment('Type: ip, ooo, ao, etc');
                $table->text('legal_address')->nullable()->comment('Registered address');
                $table->text('actual_address')->nullable()->comment('Actual business address');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_verified')->default(false)->comment('Legal entity verified');
                $table->string('verification_code')->nullable();
                $table->string('correlation_id')->nullable()->comment('Audit trail');
                $table->json('tags')->nullable();
                $table->json('metadata')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['inn', 'is_active']);
                $table->index('slug');
                $table->index('is_verified');
            });
        }

        // Create tenant_user pivot table
        if (!Schema::hasTable('tenant_user')) {
            Schema::create('tenant_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('role')->comment('User role in tenant: owner, manager, employee, accountant');
                $table->boolean('is_active')->default(true)->comment('Team membership active');
                $table->string('invitation_token')->nullable()->comment('Invitation link token');
                $table->timestamp('invited_at')->nullable()->comment('Invitation sent');
                $table->timestamp('accepted_at')->nullable()->comment('Invitation accepted');
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id']);
                $table->index('role');
                $table->index('is_active');
            });
        }

        // Create business_groups table (филиалы)
        if (!Schema::hasTable('business_groups')) {
            Schema::create('business_groups', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->comment('UUID for public references');
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name')->comment('Branch/subsidiary name');
                $table->string('inn')->unique()->comment('Subsidiary INN');
                $table->string('kpp')->nullable();
                $table->text('legal_address')->nullable();
                $table->text('actual_address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_verified')->default(false);
                $table->float('commission_percent')->default(14.0)->comment('Platform commission for this business group');
                $table->string('correlation_id')->nullable();
                $table->json('tags')->nullable();
                $table->json('metadata')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['tenant_id', 'is_active']);
                $table->index('inn');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('business_groups');
        Schema::dropIfExists('tenants');

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['role', 'is_active', 'uuid', 'tags']);
            });
        }
    }
};
