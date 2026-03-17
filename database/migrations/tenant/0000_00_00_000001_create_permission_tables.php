<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Создает таблицы для RBAC системы (Spatie/Laravel-Permission).
     * Production 2026: idempotent, correlation_id, tags, документация.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');

        if (!Schema::hasTable($tableNames['permissions'])) {
            Schema::create($tableNames['permissions'], function (Blueprint $table) use ($columnNames) {
                $table->comment('Права доступа (permissions) для RBAC.');
                
                $table->bigIncrements('id')->comment('ID прав доступа');
                $table->string('name')->comment('Имя права (напр., "create_posts")');
                $table->string('guard_name')->comment('Guard (api, web, admin)');
                $table->timestamps()->comment('created_at, updated_at');
                
                // Traceability
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID');
                $table->jsonb('tags')->nullable()->comment('Теги прав доступа');
                
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable($tableNames['roles'])) {
            Schema::create($tableNames['roles'], function (Blueprint $table) use ($columnNames, $teams) {
                $table->comment('Роли пользователей (roles) для RBAC.');
                
                $table->bigIncrements('id')->comment('ID роли');
                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable()
                        ->comment('ID команды (если multi-team)');
                    $table->index($columnNames['team_foreign_key']);
                }
                $table->string('name')->comment('Имя роли (напр., "admin", "editor")');
                $table->string('guard_name')->comment('Guard (api, web, admin)');
                $table->timestamps()->comment('created_at, updated_at');
                
                // Traceability
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID');
                $table->jsonb('tags')->nullable()->comment('Теги ролей');
                
                if ($teams) {
                    $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
                } else {
                    $table->unique(['name', 'guard_name']);
                }
            });
        }

        if (!Schema::hasTable($tableNames['model_has_permissions'])) {
            Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
                $table->comment('Связь: модели (пользователи, роли) -> права (permissions).');
                
                $table->unsignedBigInteger($columnNames['permission_pivot_key'])
                    ->comment('ID права доступа');
                $table->string('model_type')->comment('Тип модели (App\Models\User)');
                $table->unsignedBigInteger($columnNames['model_morph_key'])
                    ->comment('ID модели (user_id)');
                
                $table->index([$columnNames['model_morph_key'], 'model_type']);
                
                $table->foreign($columnNames['permission_pivot_key'])
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->onDelete('cascade');
                
                if (config('permission.teams')) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key'])
                        ->comment('ID команды');
                    $table->index($columnNames['team_foreign_key']);
                    
                    $table->primary(
                        [$columnNames['team_foreign_key'], $columnNames['permission_pivot_key'],
                            $columnNames['model_morph_key'], 'model_type'],
                        'model_has_permissions_permission_model_type_primary'
                    );
                } else {
                    $table->primary(
                        [$columnNames['permission_pivot_key'], $columnNames['model_morph_key'], 'model_type'],
                        'model_has_permissions_permission_model_type_primary'
                    );
                }
            });
        }

        if (!Schema::hasTable($tableNames['model_has_roles'])) {
            Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
                $table->comment('Связь: модели (пользователи) -> роли (roles).');
                
                $table->unsignedBigInteger($columnNames['role_pivot_key'])
                    ->comment('ID роли');
                $table->string('model_type')->comment('Тип модели');
                $table->unsignedBigInteger($columnNames['model_morph_key'])
                    ->comment('ID модели');
                
                $table->index([$columnNames['model_morph_key'], 'model_type']);
                
                $table->foreign($columnNames['role_pivot_key'])
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->onDelete('cascade');
                
                if (config('permission.teams')) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key']);
                    
                    $table->primary(
                        [$columnNames['team_foreign_key'], $columnNames['role_pivot_key'],
                            $columnNames['model_morph_key'], 'model_type'],
                        'model_has_roles_role_model_type_primary'
                    );
                } else {
                    $table->primary(
                        [$columnNames['role_pivot_key'], $columnNames['model_morph_key'], 'model_type'],
                        'model_has_roles_role_model_type_primary'
                    );
                }
            });
        }

        if (!Schema::hasTable($tableNames['role_has_permissions'])) {
            Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
                $table->comment('Связь: роли -> права доступа (role_has_permissions).');
                
                $table->unsignedBigInteger($columnNames['permission_pivot_key'])
                    ->comment('ID права доступа');
                $table->unsignedBigInteger($columnNames['role_pivot_key'])
                    ->comment('ID роли');
                
                $table->foreign($columnNames['permission_pivot_key'])
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->onDelete('cascade');
                
                $table->foreign($columnNames['role_pivot_key'])
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->onDelete('cascade');
                
                $table->primary([$columnNames['permission_pivot_key'], $columnNames['role_pivot_key']],
                    'role_has_permissions_permission_id_role_id_primary');
            });
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        
        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
