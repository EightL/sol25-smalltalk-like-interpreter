<?php

namespace IPP\Student;

use DOMDocument;
use DOMElement;

/**
 * Class table implementation
 */
class ClassTable
{
    private static ?self $instance = null;

    /** @var array<string,ClassDef> */
    private array $classes = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /** relation helper: is $a ancestor/descendant/same as $b? */
    public function isRelated(string $a, string $b): bool
    {
        return $this->isAncestor($a, $b) || $this->isAncestor($b, $a);
    }

    public function isAncestor(string $ancestor, string $desc): bool
    {
        while (true) {
            if ($ancestor === $desc) {
                return true;
            }
            $descCls = $this->classes[$desc] ?? null;
            if ($descCls === null || $descCls->getParentName() === $descCls->getName()) {
                return false;
            }
            $desc = $descCls->getParentName();
        }
    }

    // ---------------- XML loader ----------------
    public function loadFromDOM(DOMDocument $dom): void
    {
        // — register the seven built‑ins as "already taken" —
        foreach (['Object', 'Integer', 'String', 'Nil', 'True', 'False', 'Block'] as $builtin) {
            $this->classes[$builtin] = new ClassDef($builtin, 'Object');
        }

        // — now load user <class>…</class> tags, but forbid any redefinition —
        $prog = $dom->getElementsByTagName('program')->item(0)
             ?? throw InterpreterException::parse("Missing <program>");
        foreach ($prog->getElementsByTagName('class') as $c) {
            /** @var DOMElement $c */
            $name = $c->getAttribute('name');
            $par  = $c->getAttribute('parent');
            if ($name === '' || $par === '') {
                throw InterpreterException::parse("Malformed <class>");
            }
            if (in_array($name, ['Object', 'Integer', 'String', 'Nil', 'True', 'False', 'Block'], true)) {
                throw InterpreterException::other("Redefining built‑in '$name' is forbidden");
            }
            $this->classes[$name] = new ClassDef($name, $par);
        }

        // — resolve parents, error 31 if missing —
        foreach ($this->classes as $cls) {
            $p = $cls->getParentName();
            if (!isset($this->classes[$p])) {
                throw InterpreterException::classNotFound("Parent class '$p' not found");
            }
            $cls->setParent($this->classes[$p]);
        }

        // — load methods (will check arity ⇒ error 33) —
        foreach ($prog->getElementsByTagName('class') as $c) {
            $cd = $this->classes[$c->getAttribute('name')];
            foreach ($c->getElementsByTagName('method') as $m) {
                /** @var DOMElement $m */
                $sel = $m->getAttribute('selector');
                $blk = $m->getElementsByTagName('block')->item(0)
                    ?? throw InterpreterException::parse("Missing <block>");
                $cd->addMethod(new MethodDef($cd, $sel, $blk));
            }
        }
    }

    public function getClass(string $n): ClassDef
    {
        return $this->classes[$n] ?? throw InterpreterException::classNotFound("Class $n not found");
    }
}
