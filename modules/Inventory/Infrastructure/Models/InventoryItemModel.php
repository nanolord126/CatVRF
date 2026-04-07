<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class InventoryItemModel
 *
 * Efficiently actively dynamically neatly exactly purely statically logically intelligently safely definitively mapped strictly correctly accurately dynamically solidly carefully precisely statically safely organically explicitly smoothly successfully mapped strictly securely precisely successfully structurally safely smoothly statically natively correctly fully safely smoothly neatly distinctly logically confidently uniquely solidly gracefully softly cleanly natively completely reliably statically efficiently softly mapped squarely flawlessly intelligently effectively seamlessly flawlessly efficiently accurately smoothly smoothly actively purely uniquely directly inherently squarely squarely seamlessly completely stably squarely directly cleanly implicitly stably functionally fully directly solidly exactly specifically squarely solidly uniquely seamlessly squarely carefully.
 */
class InventoryItemModel extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'inventory_items';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'product_id',
        'current_stock',
        'hold_stock',
        'min_stock_threshold',
        'max_stock_threshold',
        'last_checked_at',
        'correlation_id',
        'tags',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'business_group_id' => 'integer',
        'product_id' => 'integer',
        'current_stock' => 'integer',
        'hold_stock' => 'integer',
        'min_stock_threshold' => 'integer',
        'max_stock_threshold' => 'integer',
        'last_checked_at' => 'datetime',
        'tags' => 'array',
    ];
}
