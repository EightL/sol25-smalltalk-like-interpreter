<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file StringB.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * String implementation
 */
final class StringB extends Base
{
    public function __construct(public string $value, ?string $className = null)
    {
        // passing through custom subclass name or defaulting to 'String'
        parent::__construct($className ?? 'String');
    }

    /**
     * @return self new StringB from input line
     */
    public static function read(): self
    {
        // reading a line from stdin, trimming trailing newline
        $line = Interpreter::getInstance()?->readString();
        return new self(rtrim($line ?? '', "\n"));
    }

    /**
     * @param string    $sel selector name
     * @param mixed[]   $args message arguments
     */
    public function builtin(string $sel, array $args): mixed
    {
        // we dispatch string‑specific methods first
        return match ($sel) {
            'asString' => $this,
            'print' => $this->printSelf(), // print then return self
            'equalTo:' => $args[0] instanceof self && $args[0]->value === $this->value ? TrueB::get() : FalseB::get(),
            'concatenateWith:' => $args[0] instanceof self ? new self($this->value . $args[0]->value) : NilB::get(),
            'startsWith:endsBefore:' => $this->substrOp($args),               // substring extraction
            'asInteger' => preg_match('/^[+-]?\d+$/', $this->value) ? new IntegerB((int)$this->value) : NilB::get(),
            'isString' => TrueB::get(), // confirm string type
            default => parent::builtin($sel, $args), // fallback to Base
        };
    }

    /**
     * @return self after printing to stdout
     */
    private function printSelf(): self
    {
        // we write out the string value
        Interpreter::getInstance()?->writeString($this->value);
        return $this;
    }

    /**
     * perform startsWith:endsBefore: selector
     *
     * @param mixed[] $args
     */
    private function substrOp(array $args): mixed
    {
        // ensure both args are IntegerB
        if (!isset($args[0], $args[1]) || !$args[0] instanceof IntegerB || !$args[1] instanceof IntegerB) {
            return NilB::get();
        }

        $s = $args[0]->value;
        $e = $args[1]->value;

        // indexes must be 1‑based and positive
        if ($s < 1 || $e < 1) {
            return NilB::get();
        }

        $len = $e - $s;
        // if end before start, return empty string
        return $len <= 0 ? new self('') : new self(substr($this->value, $s - 1, $len));
    }
}
