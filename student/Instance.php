<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file Instance.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * Base instance for all SOL25 objects
 */
class Instance
{
    /** @var array<string,mixed> public instance attributes */
    public array $attr = [];

    /**
     * @param string $class name of the SOL25 class
     */
    public function __construct(public readonly string $class)
    {
    }

    /**
     * handle built-in methods common to all instances
     *
     * @param string   $sel  selector name
     * @param mixed[]  $args arguments for the message
     * @return mixed result of builtin handling
     */
    public function builtin(string $sel, array $args): mixed
    {
        // special equalTo: logic for user-defined classes
        if ($sel === 'equalTo:') {
            $o = $args[0];
            // we check same class instances
            if ($o instanceof Instance && $o->class === $this->class) {
                // if no public attrs on either, we fallback to identicalTo:
                if (empty($this->attr) && empty($o->attr)) {
                    return ($o === $this) ? TrueB::get() : FalseB::get();
                }
                // comparing of public attrs
                return ($o->attr === $this->attr) ? TrueB::get() : FalseB::get();
            }
            // mismatched class -> false
            return FalseB::get();
        }

        // doing fallback to base Object methods
        return match ($sel) {
            // identicalTo: compares object identity
            'identicalTo:' => $args[0] === $this ? TrueB::get() : FalseB::get(),
            // asString for base Object returns empty string
            'asString'     => new StringB(''),
            // isNumber/isString/isBlock/isNil all false on generic Instance
            'isNumber', 'isString', 'isBlock', 'isNil' => FalseB::get(),
            default  => throw InterpreterException::methodNotFound(), // no match -> error
        };
    }
}
