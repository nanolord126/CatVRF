<?php declare(strict_types=1);


namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;  

final class UserSeeder extends Seeder {     /**      * Seed the users table.      */     public function run(): void     {         // Create admin user         User::factory()             ->state([                 'email' => 'admin@catvrf.local',                 'name' => 'System Administrator',                 'is_admin' => true,             ])             ->create();          // Create regular users         User::factory()             ->count(10)             ->create();     } }