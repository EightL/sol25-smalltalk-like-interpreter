<?php

namespace IPP\Student;

/**
 * Class definition
 */
class ClassDef
{
    private ?ClassDef $parent = null;

    /** @var array<string,MethodDef> */
    private array $methods = [];

    public function __construct(private string $name, private string $parentName)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentName(): string
    {
        return $this->parentName;
    }

    public function setParent(ClassDef $p): void
    {
        $this->parent = $p;
    }

    public function getParent(): ClassDef
    {
        if ($this->parent === null) {
            throw InterpreterException::other("Parent not set");
        }
        return $this->parent;
    }

    public function addMethod(MethodDef $m): void
    {
        $this->methods[$m->getSelector()] = $m;
    }

    public function getMethod(string $s): ?MethodDef
    {
        return $this->methods[$s] ?? null;
    }

    /** instantiate "new" */
    public function instantiate(): Instance
    {
        // any subclass of Block (including Block itself) must
        // become a Block whose "class" is **this** class name
        if (ClassTable::getInstance()->isAncestor('Block', $this->name)) {
            return new Block(
                0,
                /*captureSelf=*/ null,
                new Environment(),
                /*body=*/ null,
                /*className=*/ $this->name
            );
        }

        // integer subclasses…
        if (ClassTable::getInstance()->isAncestor('Integer', $this->name)) {
            return new IntegerB(0, $this->name);
        }
        // string subclasses…
        if (ClassTable::getInstance()->isAncestor('String', $this->name)) {
            return new StringB('', $this->name);
        }

        return match ($this->name) {
            'Integer' => new IntegerB(0),
            'String'  => new StringB(''),
            'Nil'     => NilB::get(),
            'True'    => TrueB::get(),
            'False'   => FalseB::get(),
            // Block itself still uses default className 'Block'
            'Block'   => new Block(0, null, new Environment(), null, 'Block'),
            default   => new Instance($this->name),
        };
    }

    /**
     * instantiateFrom: -- handles built‑in & user subclasses of Integer/StringB
     */
    public function instantiateFrom(mixed $src): Instance
    {
        // direct built‑ins
        $result = match (true) {
            $this->name === 'Integer' && $src instanceof IntegerB => new IntegerB($src->value),
            $this->name === 'String' && $src instanceof StringB => new StringB($src->value),
            $this->name === 'Nil' => NilB::get(),
            $this->name === 'True' => TrueB::get(),
            $this->name === 'False' => FalseB::get(),
            default => null
        };

        if ($result !== null) {
            return $result;
        }

        if (!($src instanceof Instance)) {
            throw InterpreterException::runtime("Not an Instance");
        }

        if (!ClassTable::getInstance()->isRelated($src->class, $this->name)) {
            throw InterpreterException::runtime("Incompatible class");
        }

        // special case from Block subclasses
        if (ClassTable::getInstance()->isAncestor('Block', $this->name)) {
            if (!$src instanceof Block) {
                throw InterpreterException::runtime("Not a block");
            }
            // copy arity, body and captured self/env, but give it our class name
            return new Block(
                $src->arity,
                $src->captureSelf,
                $src->captureEnv,
                $src->body,
                $this->name
            );
        }

        // ** NEW: subclasses of IntegerB **
        if (ClassTable::getInstance()->isAncestor('Integer', $this->name)) {
            /** @var IntegerB $src */
            $inst = new IntegerB($src->value, $this->name);
            // copy any user‑defined attributes, too
            $inst->attr = $src->attr;
            return $inst;
        }

        // ** NEW: subclasses of String **
        if (ClassTable::getInstance()->isAncestor('String', $this->name)) {
            /** @var StringB $src */
            $inst = new StringB($src->value, $this->name);
            $inst->attr = $src->attr;
            return $inst;
        }

        // ordinary user class
        $copy = new Instance($this->name);
        $copy->attr = $src->attr;
        return $copy;
    }
}
