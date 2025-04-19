<?php

namespace IPP\Student;

/**
 * String implementation
 */
final class StringB extends Base
{
    public function __construct(public string $value, ?string $className = null)
    {
        parent::__construct($className ?? 'String');
    }

    public static function read(): self
    {
        $line = Interpreter::getInstance()?->readString();
        return new self(rtrim($line ?? '', "\n"));
    }

    /**
     * @param mixed[] $args
     */
    public function builtin(string $sel, array $args): mixed
    {
        return match ($sel) {
            'asString'                => $this,
            'print'                   => $this->printSelf(),
            'equalTo:'                => $args[0] instanceof self && $args[0]->value === $this->value
                                            ? TrueB::get() : FalseB::get(),
            'concatenateWith:'        => $args[0] instanceof self
                                            ? new self($this->value . $args[0]->value) : NilB::get(),
            'startsWith:endsBefore:'  => $this->substrOp($args),
            'asInteger'               => preg_match('/^[+-]?\d+$/', $this->value)
                                            ? new IntegerB((int) $this->value) : NilB::get(),
            'isString'                => TrueB::get(),
            default                   => parent::builtin($sel, $args),
        };
    }

    private function printSelf(): self
    {
        Interpreter::getInstance()?->writeString($this->value);
        return $this;
    }

    /**
     * @param mixed[] $args
     */
    private function substrOp(array $args): mixed
    {
        if (!isset($args[0], $args[1]) || !$args[0] instanceof IntegerB || !$args[1] instanceof IntegerB) {
            return NilB::get();
        }
        $s = $args[0]->value;
        $e = $args[1]->value;
        if ($s < 1 || $e < 1) {
            return NilB::get();
        }
        $len = $e - $s;
        return $len <= 0 ? new self('') : new self(substr($this->value, $s - 1, $len));
    }
}
