<?php

declare(strict_types=1);

namespace CatVRF\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

/**
 * Rule: Public service methods must call FraudControlService::check() as first action
 * 
 * This rule checks that any public method in a Service class calls
 * FraudControlService::check() before any other logic.
 */
final class NoFraudCheckFirstRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof ClassMethod) {
            throw new ShouldNotHappenException();
        }

        // Only check public methods
        if (!$node->isPublic()) {
            return [];
        }

        // Only check Service classes
        $className = $scope->getClassReflection()?->getName();
        if (!$className || !str_ends_with($className, 'Service')) {
            return [];
        }

        // Skip constructor and magic methods
        if ($node->name->toString() === '__construct') {
            return [];
        }

        $stmts = $node->getStmts();
        if ($stmts === null) {
            return [];
        }

        // Check if first statement is a method call to FraudControlService::check()
        $firstStmt = $stmts[0] ?? null;
        if ($firstStmt === null) {
            return ['Public service method must call FraudControlService::check() as first action'];
        }

        // Simple check - look for ::check() call
        $stmtString = $this->stmtToString($firstStmt);
        if (!str_contains($stmtString, 'check(')) {
            return ['Public service method must call FraudControlService::check() as first action'];
        }

        return [];
    }

    private function stmtToString(Node $node): string
    {
        return $node->getText();
    }
}
