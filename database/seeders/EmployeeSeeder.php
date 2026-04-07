<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Сотрудники (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        Employee::factory()
            ->count(5)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}             [                 'first_name' => 'Иван',                 'last_name' => 'Петров',                 'email' => 'ivan.petrov@company.local',                 'phone' => '+7 (999) 123-45-67',                 'position' => 'Инженер',                 'department' => 'Техническое',                 'hire_date' => now()->subYears(3),                 'birth_date' => now()->subYears(35),                 'status' => 'active',                 'notes' => 'Опытный специалист, ведет проекты',             ],             [                 'first_name' => 'Мария',                 'last_name' => 'Сидорова',                 'email' => 'maria.sidorova@company.local',                 'phone' => '+7 (999) 234-56-78',                 'position' => 'Бухгалтер',                 'department' => 'Финансы',                 'hire_date' => now()->subYears(2),                 'birth_date' => now()->subYears(32),                 'status' => 'active',                 'notes' => 'Отвечает за налоговое планирование',             ],             [                 'first_name' => 'Александр',                 'last_name' => 'Иванов',                 'email' => 'alexander.ivanov@company.local',                 'phone' => '+7 (999) 345-67-89',                 'position' => 'Руководитель',                 'department' => 'Управление',                 'hire_date' => now()->subYears(5),                 'birth_date' => now()->subYears(42),                 'status' => 'active',                 'notes' => 'Руководитель отдела',             ],             [                 'first_name' => 'Елена',                 'last_name' => 'Федорова',                 'email' => 'elena.fedorova@company.local',                 'phone' => '+7 (999) 456-78-90',                 'position' => 'Специалист по продажам',                 'department' => 'Продажи',                 'hire_date' => now()->subMonths(8),                 'birth_date' => now()->subYears(28),                 'status' => 'active',                 'notes' => 'На испытательном сроке закончен',             ],             [                 'first_name' => 'Павел',                 'last_name' => 'Смирнов',                 'email' => 'pavel.smirnov@company.local',                 'phone' => '+7 (999) 567-89-01',                 'position' => 'Инженер',                 'department' => 'Техническое',                 'hire_date' => now()->subDays(30),                 'birth_date' => now()->subYears(31),                 'status' => 'on_leave',                 'notes' => 'На отпуске до конца месяца',             ],         ];          foreach ($employees as $employee) {             Employee::create(array_merge($employee, [                 'correlation_id' => Str::uuid()->toString(),             ]));         }     } }
