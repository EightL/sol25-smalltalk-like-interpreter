<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file ClassTable.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

use DOMDocument;
use DOMElement;

/**
 * ClassTable is singleton managing class definitions from AST
 */
class ClassTable
{
    private static ?self $instance = null;

    /** @var array<string,ClassDef> map of class name */
    private array $classes = [];

    private function __construct()
    {
    }

    /**
     * Get the singleton instance of ClassTable
     *
     * @return self
     */
    public static function getInstance(): self
    {
        // initialize on first call
        return self::$instance ??= new self();
    }

    /**
     * Relation helper: returns true if $a is ancestor, descendant, or same as $b
     *
     * @param string $a first class name
     * @param string $b second class name
     * @return bool
     */
    public function isRelated(string $a, string $b): bool
    {
        // Checking ancestors in both directions of the inheritance chain
        return $this->isAncestor($a, $b) || $this->isAncestor($b, $a);
    }

    /**
     * Check if $ancestor is in inheritance chain of $desc (or same)
     *
     * @param string $ancestor
     * @param string $desc
     * @return bool
     */
    public function isAncestor(string $ancestor, string $desc): bool
    {
        while (true) {
            if ($ancestor === $desc) {
                return true; // found match
            }
            $descCls = $this->classes[$desc] ?? null;
            if (
                $descCls === null
                || $descCls->getParentName() === $descCls->getName()
            ) {
                return false; // reached top or unknown class
            }
            // moving up one level
            $desc = $descCls->getParentName();
        }
    }

    /**
     * load class definitions and methods from AST DOM
     *
     * @param DOMDocument $dom parsed AST
     */
    public function loadFromDOM(DOMDocument $dom): void
    {
        // registering built‑in classes
        foreach (['Object', 'Integer', 'String', 'Nil', 'True', 'False', 'Block'] as $builtin) {
            $this->classes[$builtin] = new ClassDef($builtin, 'Object');
        }

        // loading user classes, forbid redefining built‑ins
        $prog = $dom->getElementsByTagName('program')->item(0)
             ?? throw InterpreterException::parse("Missing <program>");
        foreach ($prog->getElementsByTagName('class') as $c) {
            /** @var DOMElement $c */
            $name = $c->getAttribute('name');
            $par  = $c->getAttribute('parent');
            if ($name === '' || $par === '') {
                throw InterpreterException::parse("Malformed <class>");
            }
            if (in_array($name, ['Object','Integer','String','Nil','True','False','Block'], true)) {
                throw InterpreterException::other("Redefining built‑in '$name' is forbidden");
            }
            $this->classes[$name] = new ClassDef($name, $par);
        }

        // we resolve parents and link ClassDef objects
        foreach ($this->classes as $cls) {
            $p = $cls->getParentName();
            if (!isset($this->classes[$p])) {
                throw InterpreterException::classNotFound("Parent class '$p' not found");
            }
            $cls->setParent($this->classes[$p]);
        }

        // loading methods into each ClassDef (arity checked in MethodDef)
        foreach ($prog->getElementsByTagName('class') as $c) {
            $cd = $this->classes[$c->getAttribute('name')];
            foreach ($c->getElementsByTagName('method') as $m) {
                /** @var DOMElement $m */
                $sel = $m->getAttribute('selector');
                // checking for block
                $blk = $m->getElementsByTagName('block')->item(0)
                    ?? throw InterpreterException::parse("Missing <block>");

                // then we add the method
                $cd->addMethod(new MethodDef($cd, $sel, $blk));
            }
        }
    }

    /**
     * Lookup ClassDef by name or throw if not found
     *
     * @param string $n class name to fetch
     * @return ClassDef
     */
    public function getClass(string $n): ClassDef
    {
        // return existing or error
        return $this->classes[$n] ?? throw InterpreterException::classNotFound("Class $n not found");
    }
}
