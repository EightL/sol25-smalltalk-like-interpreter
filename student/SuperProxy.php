<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file SuperProxy.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * Helper value object for super‑dispatch
 */
class SuperProxy
{
    /**
     * @param Instance|null $target      actual receiver object for message send
     * @param ClassDef      $startClass  first class we search when handling super
     */
    public function __construct(public ?Instance $target, public ClassDef $startClass)
    {
    }
}
