<?php

namespace IPP\Student;

/**
 * Helper value object for super-dispatch
 */
class SuperProxy
{
    public function __construct(
        public ?Instance $target,
        public ClassDef $startClass,  // first class to search (direct parent)
    ) {
    }
}
