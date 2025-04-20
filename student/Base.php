<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file Base.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * Base class for built-in types
 */
abstract class Base extends Instance
{
    /**
     *
     * Implements common methods shared by all base types
     *
     * @param mixed[] $args
     * @param string $sel
     */
    public function builtin(string $sel, array $args): mixed
    {
        // dispatch base builtin for generic instance methods
        return match ($sel) {
            // are they identical?
            'identicalTo:' => $args[0] === $this ? TrueB::get() : FalseB::get(),
            // are they equal?
            'equalTo:' => $args[0] === $this ? TrueB::get() : FalseB::get(),
            // we return default empty string for base Object
            'asString' => new StringB(''),
            // all return false for base Object
            'isNumber', 'isString', 'isBlock', 'isNil' => FalseB::get(),
            default => throw InterpreterException::methodNotFound(), // no match -> bubble up
        };
    }
}
