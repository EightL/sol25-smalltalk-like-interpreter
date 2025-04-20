<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file IntegerB.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * Integer implementation for SOL25
 *
 * handles numeric operations and repeats
 */
final class IntegerB extends Base
{
    /**
     * @param int $value numeric value stored
     * @param string|null $cls optional subclass name
     */
    public function __construct(public int $value, ?string $cls = null)
    {
        parent::__construct($cls ?? 'Integer');
    }

    /**
     * @param mixed[] $args
     */
    public function builtin(string $sel, array $args): mixed
    {
        return match ($sel) {
            // doing value compare then boolean
            'equalTo:'     => ($args[0] instanceof self && $args[0]->value === $this->value )
                                ? TrueB::get() : FalseB::get(),
            // to-string conversion
            'asString'     => new StringB((string) $this->value),
            // return self as integer
            'asInteger'    => $this,
            'plus:'        => new self($this->value + self::i($args[0])),
            'minus:'       => new self($this->value - self::i($args[0])),
            'multiplyBy:'  => new self($this->value * self::i($args[0])),
            // division by zero check, else safe divide
            'divBy:'       => self::i($args[0]) === 0 ? throw InterpreterException::runtime("Division by zero")
                                : new self(intdiv($this->value, self::i($args[0]))),

            'greaterThan:' => $this->value > self::i($args[0]) ? TrueB::get() : FalseB::get(),
            // we have helper for timesRepeat
            'timesRepeat:' => $this->timesRepeat($args[0]),
            'isNumber'     => TrueB::get(), // true..
            default        => parent::builtin($sel, $args),
        };
    }

    /**
     * Convert mixed to int value or error
     *
     * @param mixed $v value to convert
     * @return int numeric value
     */
    private static function i(mixed $v): int
    {
        if ($v instanceof self) {
            return $v->value; // we unwrap integer value
        }
        throw InterpreterException::runtime("Expected Integer"); // type check and error
    }

    /**
     * Repeat block value: for each iteration from 1 to value
     *
     * @param mixed $blk block or block-like instance
     * @return NilB always return nil
     */
    private function timesRepeat(mixed $blk): NilB
    {
        if ($this->value <= 0) {
            return NilB::get(); // nothing for non-positive
        }
        // we iterate n times, passing iteration count to block
        for ($i = 1; $i <= $this->value; ++$i) {
            MessageDispatcher::send($blk, 'value:', [new self($i)]); // doing send value: to blk
        }
        return NilB::get(); // doing return nil after loop
    }
}
