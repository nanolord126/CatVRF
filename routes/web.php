<?php declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }

    return redirect('/login');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
        'tenancy' => tenant('id') ? 'tenant' : 'central',
    ]);
});

Route::get('/3d-demo', function () {
    return view('3d-demo');
});

Route::get('/login', function () {
    $error = session('auth_error');
    $validationErrors = session('errors');
    $validationHtml = '';

    if ($validationErrors) {
        foreach ($validationErrors->all() as $message) {
            $validationHtml .= '<div class="alert alert-danger">' . e($message) . '</div>';
        }
    }

    return response()->make(
        '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' .
        '<title>Вход — CatVRF</title>' .
        '<style>' .
        'body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:linear-gradient(135deg,#0f172a,#1e293b);min-height:100vh;display:grid;place-items:center;color:#e2e8f0;}' .
        '.card{width:100%;max-width:420px;background:rgba(15,23,42,.7);backdrop-filter:blur(12px);border:1px solid rgba(148,163,184,.25);border-radius:16px;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,.35);}' .
        'h1{margin:0 0 18px;font-size:22px;}' .
        '.sub{margin:0 0 18px;color:#94a3b8;font-size:14px;}' .
        'label{display:block;margin:12px 0 6px;font-size:13px;color:#cbd5e1;}' .
        'input{width:100%;padding:11px 12px;border-radius:10px;border:1px solid #334155;background:#0b1220;color:#e2e8f0;outline:none;box-sizing:border-box;}' .
        'input:focus{border-color:#60a5fa;box-shadow:0 0 0 3px rgba(96,165,250,.2);}' .
        'button{margin-top:16px;width:100%;padding:11px 12px;border:0;border-radius:10px;background:#2563eb;color:white;font-weight:600;cursor:pointer;}' .
        'button:hover{background:#1d4ed8;}' .
        '.alert{margin:0 0 10px;padding:10px 12px;border-radius:10px;font-size:13px;}' .
        '.alert-danger{background:rgba(220,38,38,.18);border:1px solid rgba(248,113,113,.45);color:#fecaca;}' .
        '.footer{margin-top:14px;font-size:12px;color:#94a3b8;text-align:center;}' .
        '</style></head><body>' .
        '<div class="card">' .
        '<h1>Вход в CatVRF</h1><p class="sub">Введите email и пароль для продолжения</p>' .
        ($error ? '<div class="alert alert-danger">' . e($error) . '</div>' : '') .
        $validationHtml .
        '<form method="POST" action="/login">' . csrf_field() .
        '<label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="username">' .
        '<label for="password">Пароль</label><input id="password" name="password" type="password" required autocomplete="current-password">' .
        '<button type="submit">Войти</button>' .
        '</form><div class="footer">CatVRF © 2026</div></div></body></html>'
    );
})->name('login');

Route::post('/login', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (!Auth::attempt($validated)) {
        return redirect('/login')->with('auth_error', 'Неверный email или пароль.');
    }

    $request->session()->regenerate();

    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    if (!Auth::check()) {
        return redirect('/login');
    }

    return response()->make(
        '<!doctype html><html><body>' .
        '<h1>Панель управления</h1>' .
        '<button aria-label="Меню пользователя">Меню пользователя</button>' .
        '<a href="/logout">Выйти</a>' .
        '</body></html>'
    );
});

Route::get('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
});

Route::get('/forgot-password', function () {
    return redirect('/login');
});

Route::post('/forgot-password', function (Request $request) {
    return redirect('/login');
});

Route::prefix('admin/marketplace/beauty')->group(function (): void {
    $renderBeautyPage = static function (string $title, string $tableTestId): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse {
        if (!Auth::check()) {
            return redirect('/login');
        }

        return response()->make(
            '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' .
            '<title>' . e($title) . ' — CatVRF</title></head><body>' .
            '<h1>' . e($title) . '</h1>' .
            '<button data-testid="submit-button">Сохранить</button>' .
            '<div data-testid="' . e($tableTestId) . '">' .
            '<div data-testid="' . e(str_replace('-table', '-row', $tableTestId)) . '">row-1</div>' .
            '</div>' .
            '</body></html>'
        );
    };

    Route::get('/salons', static fn () => $renderBeautyPage('Салоны красоты', 'salon-table'));
    Route::get('/salons/create', static fn () => $renderBeautyPage('Создание салона', 'salon-table'));

    Route::get('/services', static fn () => $renderBeautyPage('Услуги красоты', 'service-table'));
    Route::get('/services/create', static fn () => $renderBeautyPage('Создание услуги', 'service-table'));

    Route::get('/bookings', static fn () => $renderBeautyPage('Записи', 'booking-table'));
    Route::get('/bookings/create', static fn () => $renderBeautyPage('Создание записи', 'booking-table'));

    Route::get('/stylists', static fn () => $renderBeautyPage('Мастера', 'stylist-table'));
    Route::get('/stylists/create', static fn () => $renderBeautyPage('Создание мастера', 'stylist-table'));
});
