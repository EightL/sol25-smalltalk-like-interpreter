<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file MessageDispatcher.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

use DOMElement;

/**
 * Method definition
 */
class MethodDef
{
    /**
     * @param ClassDef   $owner class that defines this method
     * @param string     $sel   selector name of the method
     * @param DOMElement $blk   AST block node for method body
     */
    public function __construct(private ClassDef $owner, private string $sel, private DOMElement $blk)
    {
    }

    /**
     * @return ClassDef owner class definition
     */
    public function getOwner(): ClassDef
    {
        // we return the class that defined this method
        return $this->owner;
    }

    /**
     * @return string selector name
     */
    public function getSelector(): string
    {
        // we return stored selector for lookup
        return $this->sel;
    }

    /**
     * @return DOMElement AST block node for execution
     */
    public function getBlockNode(): DOMElement
    {
        // we return the block element representing method body
        return $this->blk;
    }
}
