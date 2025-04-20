<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file Frame.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * Frame holds variables and param flags for one scope
 */
class Frame
{
    /**
     * constructor sets up vars and flags
     * storing current variables; we keep initial state
     * marking params immutable so they can't be reassigned
     *
     * @param array<string,mixed> $vars initial variable map
     * @param array<string,bool>  $paramFlags map of param names to true (immutable)
     */
    public function __construct(public array $vars = [], public array $paramFlags = [])
    {
    }

    /**
     * check if variable exists in this frame
     *
     * @param string $n name to look up
     * @return bool true if var defined in this frame
     */
    public function has(string $n): bool
    {
        // doing key existence check; we search in this frame only
        return array_key_exists($n, $this->vars);
    }

    /**
     * get reference to variable so Environment can modify it
     *
     * @param string $n variable name
     * @return mixed reference to stored value
     */
    public function &ref(string $n): mixed
    {
        // return by reference; we allow direct assignment to frame slot
        return $this->vars[$n];
    }
}
