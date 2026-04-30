<?php

declare(strict_types=1);

namespace CatVRF\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

/**
 * Rule: LLM calls must not be inside DB::transaction()
 * 
 * This rule checks that methods calling LLM services (OpenAI, Grok, etc.)
 * are not called within DB::transaction() blocks.
 */
final class NoLLMInTransactionRule implements Rule
{
    private const LLM_METHODS = [
        'chat',
        'complete',
        'generate',
        'ask',
        'stream',
    ];

    private const LLM_CLASSES = [
        'OpenAI',
        'OpenAI\Client',
        'GigaChat',
        'Grok',
        'LLM',
    ];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof MethodCall) {
            throw new ShouldNotHappenException();
        }

        // Check if this is an LLM method call
        if (!$this->isLLMCall($node)) {
            return [];
        }

        // Check if we're inside a DB::transaction() call
        $function = $scope->getFunction();
        if ($function === null) {
            return [];
        }

        // Simple heuristic: if method name contains "transaction", flag it
        $methodName = $function->getName();
        if (str_contains($methodName, 'transaction')) {
            return [
                'LLM calls must not be inside DB::transaction(). Move to async queue job instead.'
            ];
        }

        return [];
    }

    private function isLLMCall(MethodCall $node): bool
    {
        if (!$node->name instanceof Node\Identifier) {
            return false;
        }

        $methodName = $node->name->toString();
        if (!in_array($methodName, self::LLM_METHODS, true)) {
            return false;
        }

        $classString = $node->var->toString();
        foreach (self::LLM_CLASSES as $llmClass) {
            if (str_contains($classString, $llmClass)) {
                return true;
            }
        }

        return false;
    }
}
