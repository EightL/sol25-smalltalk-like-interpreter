<?php

namespace IPP\Student;

/**
 * Environment stack for variable lookup
 */
class Environment
{
    /** @var Frame[] */
    private array $stack = [];

    public function push(Frame $f): void
    {
        $this->stack[] = $f;
    }

    public function pop(): void
    {
        array_pop($this->stack);
    }

    public function &lookup(string $n): mixed
    {
        foreach (array_reverse($this->stack) as $f) {
            if ($f->has($n)) {
                return $f->ref($n);
            }
        }
        throw InterpreterException::name("Undefined variable '$n'");
    }

    public function set(string $n, mixed $v): void
    {
        $top = &$this->stack[count($this->stack) - 1];
        if (isset($top->paramFlags[$n])) {
            // error 34
            throw InterpreterException::scope("Cannot assign to parameter '$n'");
        }
        $top->vars[$n] = $v;
    }

    public function clone(): self
    {
        $c = new self();
        foreach ($this->stack as $f) {
            $c->push(new Frame($f->vars, $f->paramFlags));
        }
        return $c;
    }
}
