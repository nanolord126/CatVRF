<?php

declare(strict_types=1);

namespace CatVRF\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

/**
 * Rule: Service classes should not use static:: calls
 * 
 * This rule checks that Service classes do not use static:: calls,
 * which violates dependency injection principles.
 */
final class NoStaticCallsInServicesRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof StaticCall) {
            throw new ShouldNotHappenException();
        }

        // Only check Service classes
        $className = $scope->getClassReflection()?->getName();
        if (!$className || !str_ends_with($className, 'Service')) {
            return [];
        }

        // Allow static:: for certain patterns (e.g., static::create() for factories)
        if ($node->name instanceof Node\Identifier) {
            $methodName = $node->name->toString();
            if (in_array($methodName, ['create', 'make', 'factory'], true)) {
                return [];
            }
        }

        return [
            sprintf(
                'Service classes should not use static:: calls. Use dependency injection instead. Found: %s',
                $node->class->toString()
            )
        ];
    }
}
