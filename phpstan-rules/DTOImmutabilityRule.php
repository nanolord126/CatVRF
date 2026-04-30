<?php

declare(strict_types=1);

namespace CatVRF\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

/**
 * Rule: DTOs must be readonly and have fromJson/toArray methods
 * 
 * This rule checks that DTO classes follow CatVRF standards:
 * - Must be readonly
 * - Must have fromJson() static method
 * - Must have toArray() method
 */
final class DTOImmutabilityRule implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Class_) {
            throw new ShouldNotHappenException();
        }

        // Only check DTO classes
        $className = $node->name->toString();
        if (!str_ends_with($className, 'DTO')) {
            return [];
        }

        $errors = [];

        // Check if class is readonly
        if (!$node->isReadonly()) {
            $errors[] = 'DTO classes must be readonly';
        }

        // Check for fromJson method
        $hasFromJson = false;
        $hasToArray = false;

        foreach ($node->getMethods() as $method) {
            $methodName = $method->name->toString();
            if ($methodName === 'fromJson') {
                $hasFromJson = true;
            }
            if ($methodName === 'toArray') {
                $hasToArray = true;
            }
        }

        if (!$hasFromJson) {
            $errors[] = 'DTO classes must have a fromJson() static method';
        }

        if (!$hasToArray) {
            $errors[] = 'DTO classes must have an toArray() method';
        }

        return $errors;
    }
}
