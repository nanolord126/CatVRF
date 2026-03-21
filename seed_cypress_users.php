<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Use DB directly to avoid SoftDeletes issue on tenants
$tenantRow = \Illuminate\Support\Facades\DB::table('tenants')->where('slug', 'kotvrf')->first();
if (!$tenantRow) {
    $tenantId = (string) Str::uuid();
    \Illuminate\Support\Facades\DB::table('tenants')->insert([
        'id'             => $tenantId,
        'name'           => 'KotVRF',
        'type'           => 'business',
        'slug'           => 'kotvrf',
        'correlation_id' => 'test-tenant-001',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
    $tenantRow = \Illuminate\Support\Facades\DB::table('tenants')->where('id', $tenantId)->first();
}
$tenant = (object)['id' => $tenantRow->id];

$users = [
    ['admin@kotvrf.ru', 'Admin'],
    ['manager@kotvrf.ru', 'Manager'],
    ['viewer@kotvrf.ru', 'Viewer'],
];

foreach ($users as [$email, $name]) {
    $user = User::firstOrCreate(
        ['email' => $email],
        [
            'name'           => $name,
            'password'       => Hash::make('password123'),
            'tenant_id'      => $tenant->id,
            'correlation_id' => 'test-user-' . strtolower($name),
            'email_verified_at' => now(),
        ]
    );
    echo "User: {$user->email} (id={$user->id})\n";
}

echo 'Total users: ' . User::count() . "\n";
