<?php

namespace App\Services\Ai;

/**
 * LLM provider interface (BYOK). The copilot NEVER grants write access:
 * providers only receive a read-only data context + the question.
 */
interface AiProviderInterface
{
    public function name(): string;

    /** Return ['answer' => string, 'model' => string]. Must not perform writes. */
    public function ask(string $question, string $context): array;
}
