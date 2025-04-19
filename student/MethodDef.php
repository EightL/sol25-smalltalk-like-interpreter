<?php

namespace IPP\Student;

use DOMElement;

/**
 * Method definition
 */
class MethodDef
{
    public function __construct(
        private ClassDef $owner,
        private string $sel,
        private DOMElement $blk
    ) {
    }

    public function getOwner(): ClassDef
    {
        return $this->owner;
    }

    public function getSelector(): string
    {
        return $this->sel;
    }

    public function getBlockNode(): DOMElement
    {
        return $this->blk;
    }
}
