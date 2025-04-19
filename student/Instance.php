<?php

namespace IPP\Student;

/**
 * Base instance for all objects
 */
class Instance
{
    /** @var array<string,mixed> */
    public array $attr = [];

    public function __construct(public readonly string $class)
    {
    }

    /**
     * @param mixed[] $args
     */
    public function builtin(string $sel, array $args): mixed
    {
        if ($sel === 'equalTo:') {
            $o = $args[0];
            if ($o instanceof Instance && $o->class === $this->class) {
                // if no public attrs on either, fallback to identicalTo:
                if (empty($this->attr) && empty($o->attr)) {
                    return ($o === $this) ? TrueB::get() : FalseB::get();
                }
                // else deep compare public attrs
                return ($o->attr === $this->attr) ? TrueB::get() : FalseB::get();
            }
            return FalseB::get();
        }

        // Base Object methods available to all classes
        return match ($sel) {
            'identicalTo:' => $args[0] === $this ? TrueB::get() : FalseB::get(),
            'asString'     => new StringB(''),
            'isNumber', 'isString', 'isBlock', 'isNil' => FalseB::get(),
            default => throw InterpreterException::methodNotFound(),
        };
    }
}
