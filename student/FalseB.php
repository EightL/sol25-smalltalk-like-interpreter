<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file FalseB.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * FalseB singleton implementation
 *
 * handles false-specific logic for boolean operations
 */
final class FalseB extends Base
{
    private static ?self $inst = null;

    // private to enforce singleton
    private function __construct()
    {
        parent::__construct('False');
    }

    /**
     * get the singleton instance of FalseB
     *
     * @return self
     */
    public static function get(): self
    {
        // doing lazy init of false singleton
        return self::$inst ??= new self();
    }

    /**
     * implement false-specific builtins: not, and:, or:, ifTrue:ifFalse:
     *
     * @param string $sel  selector name
     * @param mixed[] $args arguments for selector
     * @return mixed result of false logic
     */
    public function builtin(string $sel, array $args): mixed
    {
        // doing dispatch for false-only selectors, else fallback
        return match ($sel) {
            'not'             => TrueB::get(),  // not false -> true
            'and:'            => FalseB::get(), // false and anything -> false, LOGIC! :)
            'or:'             => MessageDispatcher::send($args[0], 'value', []), // false or:block -> evaluate block
            'ifTrue:ifFalse:' => MessageDispatcher::send($args[1], 'value', []), // false ifTrue:ifFalse: -> else block
            default           => parent::builtin($sel, $args),  // doing fallback to base Object
        };
    }
}
