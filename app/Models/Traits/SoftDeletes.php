<?php declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\SoftDeletes as EloquentSoftDeletes;

/**
 * Application-owned SoftDeletes trait.
 *
 * Wraps Illuminate's SoftDeletes to maintain in-project ownership
 * and allow future customisation without touching framework code.
 */
trait SoftDeletes
{
    use EloquentSoftDeletes;
}
