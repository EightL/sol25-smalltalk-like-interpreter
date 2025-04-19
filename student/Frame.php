<?php

namespace IPP\Student;

/**
 * Stack frame for environment
 */
class Frame
{
    /**
     * @param array<string,mixed> $vars
     * @param array<string,bool>  $paramFlags
     */
    public function __construct(
        public array $vars = [],  // current variables
        public array $paramFlags = []   // [ 'paramName' => true, … ]
    ) {
    }

    public function has(string $n): bool
    {
        return array_key_exists($n, $this->vars);
    }

    public function &ref(string $n): mixed
    {
        return $this->vars[$n];
    }
}
