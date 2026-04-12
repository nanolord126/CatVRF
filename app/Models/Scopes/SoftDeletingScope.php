<?php declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\SoftDeletingScope as EloquentSoftDeletingScope;

/**
 * Application-owned soft-deleting scope.
 *
 * Delegates to Illuminate's SoftDeletingScope to maintain in-project
 * ownership while allowing future customisation.
 */
final class SoftDeletingScope implements Scope
{
    private EloquentSoftDeletingScope $inner;

    public function __construct()
    {
        $this->inner = new EloquentSoftDeletingScope();
    }

    public function apply(Builder $builder, Model $model): void
    {
        $this->inner->apply($builder, $model);
    }

    public function extend(Builder $builder): void
    {
        $this->inner->extend($builder);
    }
}
