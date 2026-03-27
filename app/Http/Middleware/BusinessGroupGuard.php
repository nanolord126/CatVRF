<?php

declare(strict_types=1);
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final /**
 * BusinessGroupGuard
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BusinessGroupGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
