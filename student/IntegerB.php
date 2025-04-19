<?php

namespace IPP\Student;

/**
 * Integer implementation
 */
final class IntegerB extends Base
{
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
            'equalTo:'     => ($args[0] instanceof self && $args[0]->value === $this->value)
                                ? TrueB::get() : FalseB::get(),
            'asString'     => new StringB((string) $this->value),
            'asInteger'    => $this,
            'plus:'        => new self($this->value + self::i($args[0])),
            'minus:'       => new self($this->value - self::i($args[0])),
            'multiplyBy:'  => new self($this->value * self::i($args[0])),
            'divBy:'       => self::i($args[0]) === 0 ?
                             throw InterpreterException::runtime("Division by zero") :
                             new self(intdiv($this->value, self::i($args[0]))),
            'greaterThan:' => $this->value > self::i($args[0]) ? TrueB::get() : FalseB::get(),
            'timesRepeat:' => $this->timesRepeat($args[0]),
            'isNumber'     => TrueB::get(),
            default        => parent::builtin($sel, $args),
        };
    }

    private static function i(mixed $v): int
    {
        return $v instanceof self ? $v->value :
               throw InterpreterException::runtime("Expected Integer");
    }

    private function timesRepeat(mixed $blk): NilB
    {
        if ($this->value <= 0) {
            return NilB::get();
        }
        for ($i = 1; $i <= $this->value; ++$i) {
            MessageDispatcher::send($blk, 'value:', [new self($i)]);
        }
        return NilB::get();
    }
}
