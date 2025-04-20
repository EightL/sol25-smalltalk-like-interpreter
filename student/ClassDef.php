<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file ClassDef.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * Class definition holds methods and instantiation logic
 */
class ClassDef
{
    private ?ClassDef $parent = null;

    /** @var array<string,MethodDef> */
    private array $methods = [];

    /**
     * Constructor stores class and parent names
     *
     * @param string $name  identifier of this class
     * @param string $parentName identifier of its superclass
     */
    public function __construct(private string $name, private string $parentName)
    {
    }

    public function getName(): string
    {
        // returning stored name
        return $this->name;
    }

    public function getParentName(): string
    {
        // retrieving parentName
        return $this->parentName;
    }

    public function setParent(ClassDef $p): void
    {
        // storing parent link
        $this->parent = $p;
    }

    public function getParent(): ClassDef
    {
        if ($this->parent === null) {
            throw InterpreterException::other("Parent not set");
        }
        // returning linked parent
        return $this->parent;
    }

    /**
     * register a method definition under its selector
     *
     * @param MethodDef $m method to add
     */
    public function addMethod(MethodDef $m): void
    {
        // map selector -> MethodDef
        $this->methods[$m->getSelector()] = $m;
    }

    public function getMethod(string $s): ?MethodDef
    {
        // return method or null if absent
        return $this->methods[$s] ?? null;
    }

    /**
     * instantiate "new" for this class
     *
     * handles built-in types and user classes
     *
     * @return Instance new instance of this class
     */
    public function instantiate(): Instance
    {
        // Block subclasses -> wrap as Block (insider: special-case Block here)
        if (ClassTable::getInstance()->isAncestor('Block', $this->name)) {
            return new Block(0, null, new Environment(), null, $this->name);
        }

        // Integer subclasses -> default 0
        if (ClassTable::getInstance()->isAncestor('Integer', $this->name)) {
            return new IntegerB(0, $this->name);
        }
        // String subclasses -> default ''
        if (ClassTable::getInstance()->isAncestor('String', $this->name)) {
            return new StringB('', $this->name);
        }

        // built-in classes
        return match ($this->name) {
            'Integer' => new IntegerB(0),
            'String'  => new StringB(''),
            'Nil'     => NilB::get(),
            'True'    => TrueB::get(),
            'False'   => FalseB::get(),
            'Block'   => new Block(0, null, new Environment(), null, 'Block'),
            default   => new Instance($this->name), // user class -> generic instance
        };
    }

    /**
     * Copy existing instance into new one
     *
     * supports built-in & user subclasses of Integer/StringB
     *
     * @param mixed $src source object to copy
     * @return Instance new instance with copied state
     */
    public function instantiateFrom(mixed $src): Instance
    {
        // direct built-ins via match(true)
        $result = match (true) {
            $this->name === 'Integer' && $src instanceof IntegerB => new IntegerB($src->value),
            $this->name === 'String' && $src instanceof StringB  => new StringB($src->value),
            $this->name === 'Nil' => NilB::get(),
            $this->name === 'True' => TrueB::get(),
            $this->name === 'False' => FalseB::get(),
            default => null,
        };

        if ($result !== null) {
            // found direct mapping
            return $result;
        }

        if (!($src instanceof Instance)) {
            throw InterpreterException::runtime("Not an Instance");
        }

        // we ensure class relation for from:
        if (!ClassTable::getInstance()->isRelated($src->class, $this->name)) {
            throw InterpreterException::runtime("Incompatible class");
        }

        // Block subclasses -> copy capture and body
        if (ClassTable::getInstance()->isAncestor('Block', $this->name)) {
            if (!$src instanceof Block) {
                throw InterpreterException::runtime("Not a block");
            }
            return new Block($src->arity, $src->captureSelf, $src->captureEnv, $src->body, $this->name);
        }

        // subclasses of Integer -> copying value & attrs
        if (ClassTable::getInstance()->isAncestor('Integer', $this->name)) {
            /** @var IntegerB $src */
            $inst = new IntegerB($src->value, $this->name);
            $inst->attr = $src->attr; // we copy user attrs
            return $inst;
        }

        // subclasses of String -> copying value & attrs
        if (ClassTable::getInstance()->isAncestor('String', $this->name)) {
            /** @var StringB $src */
            $inst = new StringB($src->value, $this->name);
            $inst->attr = $src->attr; // we copy user attrs
            return $inst;
        }

        // ordinary user class -> generic Instance copy
        $copy = new Instance($this->name);
        $copy->attr = $src->attr; // copying attrs
        return $copy;
    }
}
