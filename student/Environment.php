<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file Environment.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * Environment stack for variable lookup and assignment
 */
class Environment
{
    /** @var Frame[] stack of call frames */
    private array $stack = [];

    /**
     * Push a new frame onto the stack
     *
     * @param Frame $f frame containing vars and flags
     */
    public function push(Frame $f): void
    {
        // pushing a new frame for new scope
        $this->stack[] = $f;
    }

    /**
     * Pop the top frame from the stack
     */
    public function pop(): void
    {
        // removing the current scope frame
        array_pop($this->stack);
    }

    /**
     * Lookup a variable by name, returning a reference to its storage
     *
     * @param string $n variable name to find
     * @return mixed
     * @throws InterpreterException if var not defined
     */
    public function &lookup(string $n): mixed
    {
        // scanning frames from innermost to outermost
        foreach (array_reverse($this->stack) as $f) {
            if ($f->has($n)) {
                // found var -> return its reference
                return $f->ref($n);
            }
        }
        // var not found -> error 32
        throw InterpreterException::name("Undefined variable '$n'");
    }

    /**
     * Set or create a variable in the current (top) frame
     *
     * @param string $n name of variable
     * @param mixed  $v value to assign
     */
    public function set(string $n, mixed $v): void
    {
        // grabbing the top frame by reference
        $top = &$this->stack[count($this->stack) - 1];

        // check if var is a parameter (immutable) -> error 34
        if (isset($top->paramFlags[$n])) {
            throw InterpreterException::scope("Cannot assign to parameter '$n'");
        }

        // assign or create local variable in current scope
        $top->vars[$n] = $v;
    }

    /**
     * Clone the entire environment stack (for block capture)
     *
     * @return self new Environment with copied frames
     */
    public function clone(): self
    {
        // creating fresh Environment so original stays intact
        $c = new self();
        // copying each frame
        foreach ($this->stack as $f) {
            $c->push(new Frame($f->vars, $f->paramFlags));
        }
        return $c;
    }
}
