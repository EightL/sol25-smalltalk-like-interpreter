<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file TrueB.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * True singleton implementation
 */
final class TrueB extends Base
{
    private static ?self $inst = null;

    private function __construct()
    {
        parent::__construct('True');
    }

    public static function get(): self
    {
        return self::$inst ??= new self();
    }

    /**
     * @param mixed[] $args
     */
    public function builtin(string $sel, array $args): mixed
    {
        // dispatch truthy methods for True
        return match ($sel) {
            // not flips true to false
            'not' => FalseB::get(),
            // and: only evaluates block if still true
            'and:' => MessageDispatcher::send($args[0], 'value', []),
            // or: always true, skip evaluating second block
            'or:' => TrueB::get(),
            // ifTrue:ifFalse: run true‑branch block
            'ifTrue:ifFalse:' => MessageDispatcher::send($args[0], 'value', []),
            // fall back to common base methods
            default => parent::builtin($sel, $args),
        };
    }
}
