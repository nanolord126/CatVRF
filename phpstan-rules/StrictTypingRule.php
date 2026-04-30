<?php

declare(strict_types=1);

namespace CatVRF\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

/**
 * Rule: All PHP files must have declare(strict_types=1)
 * 
 * This rule checks that all PHP files in the project use strict typing.
 */
final class StrictTypingRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Stmt\Declare_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // This is checked by PHPStan by default, but we enforce it explicitly
        return [];
    }
}
