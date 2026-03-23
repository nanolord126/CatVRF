<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    // Удаляем старого пользователя если существует
    User::where('email', 'test@example.com')->delete();
    
    // Создаем нового тестового пользователя
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'email_verified_at' => now(),
    ]);
    
    echo "✅ Пользователь создан успешно!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Email:    test@example.com\n";
    echo "Password: password123\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n🚀 Используйте эти учетные данные для входа!\n";
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
