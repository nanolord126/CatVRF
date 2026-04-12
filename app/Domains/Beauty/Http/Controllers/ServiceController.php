<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Http\Controllers\Api\V1\Beauty\ServiceController as BaseServiceController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ServiceController — Domain-level controller proxy (Beauty vertical).
 * Delegates all standard CRUD operations to base API controller.
 * Override individual methods here for domain-specific behaviour.
 *
 * @see \App\Http\Controllers\Api\V1\Beauty\BaseServiceController
 */
final class ServiceController extends BaseServiceController
{
}
