<?php

namespace IPP\Student;

/**
 * Base class for built-in types
 */
abstract class Base extends Instance
{
    /**
     * @param mixed[] $args
     */
    public function builtin(string $sel, array $args): mixed
    {
        // Base implementations have their own specialized builtin methods
        return match ($sel) {
            'identicalTo:' => $args[0] === $this ? TrueB::get() : FalseB::get(),
            'equalTo:'     => $args[0] === $this ? TrueB::get() : FalseB::get(),
            'asString'     => new StringB(''),
            'isNumber', 'isString', 'isBlock', 'isNil' => FalseB::get(),
            default => throw InterpreterException::methodNotFound(),
        };
    }
}
