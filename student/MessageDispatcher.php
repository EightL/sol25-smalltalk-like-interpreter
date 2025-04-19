<?php

namespace IPP\Student;

use DOMElement;
use RuntimeException;

/**
 * Message dispatch system
 */
class MessageDispatcher
{
    /**
     * @param mixed[] $args
     */
    public static function send(mixed $recv, string $sel, array $args): mixed
    {
        // 1) super‐dispatch unwrap
        $startClass = null;
        if ($recv instanceof SuperProxy) {
            $startClass = $recv->startClass;
            $recv = $recv->target;
        }

        // 2) class constructors
        if ($recv instanceof ClassDef) {
            if ($sel === 'new' && $args === []) {
                return $recv->instantiate();
            }
            if ($sel === 'from:' && count($args) === 1) {
                return $recv->instantiateFrom($args[0]);
            }
            if ($recv->getName() === 'String' && $sel === 'read') {
                return StringB::read();
            }
        }

        // 3) early‐out builtins (only in normal dispatch, not super)
        if ($startClass === null && $recv instanceof Instance) {
            // deep scan for ANY user‐defined method anywhere in the chain
            $cd = ClassTable::getInstance()->getClass($recv->class);
            $hasUser = false;
            while ($cd) {
                if ($cd->getMethod($sel) !== null) {
                    $hasUser = true;
                    break;
                }
                $p = $cd->getParentName();
                if ($p === $cd->getName()) {
                    break;
                }
                $cd = ClassTable::getInstance()->getClass($p);
            }
            if (!$hasUser) {
                // OK to fall back to builtin
                try {
                    return $recv->builtin($sel, $args);
                } catch (InterpreterException $e) {
                    if ($e->getCode() !== 51) {
                        throw $e;
                    }
                }
            }
        }

        // 4) user methods
        if ($recv instanceof Instance) {
            // start lookup either at super::startClass or at recv's own class
            $cd = $startClass
                ? $startClass
                : ClassTable::getInstance()->getClass($recv->class);
            while ($cd) {
                if ($m = $cd->getMethod($sel)) {
                    return self::invoke($recv, $m, $args);
                }
                $p = $cd->getParentName();
                if ($p === $cd->getName()) {
                    break;
                }
                $cd = ClassTable::getInstance()->getClass($p);
            }
        }

        // 5) second chance builtins (e.g. inherited block methods, Boolean logic, etc.)
        if ($recv instanceof Instance) {
            try {
                return $recv->builtin($sel, $args);
            } catch (InterpreterException $e) {
                if ($e->getCode() !== 51) {
                    throw $e;
                }
            }
        }

        // 6) attribute setter?
        if (str_ends_with($sel, ':') && count($args) === 1 && $recv instanceof Instance) {
            $prop = substr($sel, 0, -1);
            // collision guard: walk entire chain to make sure no method named $prop or "$prop:"
            $scan = ClassTable::getInstance()->getClass($recv->class);
            while ($scan) {
                if ($scan->getMethod($prop) !== null || $scan->getMethod($prop . ':') !== null) {
                    throw InterpreterException::runtime("Attribute '$prop' collides with a method");
                }
                $p = $scan->getParentName();
                if ($p === $scan->getName()) {
                    break;
                }
                $scan = ClassTable::getInstance()->getClass($p);
            }
            $recv->attr[$prop] = $args[0];
            return $recv;
        }

        // 7) attribute getter?
        if ($recv instanceof Instance && !str_contains($sel, ':')) {
            if (!array_key_exists($sel, $recv->attr)) {
                throw InterpreterException::methodNotFound();
            }
            return $recv->attr[$sel];
        }

        // 8) nothing matched ⇒ method‑not‑found
        throw InterpreterException::methodNotFound("No method or attribute '$sel'");
    }

    /**
     * @param mixed[] $args
     */
    private static function invoke(Instance $self, MethodDef $def, array $args): mixed
    {
        $blk = $def->getBlockNode();
        $params = [];
        foreach ($blk->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'parameter') {
                $order = (int)$child->getAttribute('order');
                $params[$order] = $child->getAttribute('name');
            }
        }
        ksort($params);
        if (count($params) !== count($args)) {
            throw InterpreterException::methodNotFound("Param count");
        }
        $env = new Environment();

        // Mark parameters as immutable (just like in Block::builtin)
        $paramFlags = array_fill_keys(array_values($params), true);

        $frameVars = [
            'self' => $self,
            'super' => new SuperProxy($self, ClassTable::getInstance()->getClass($self->class)->getParent())
        ];

        // Add parameter values
        $frameVars += array_combine(array_values($params), $args);

        // Push the frame with variables and immutability flags
        $env->push(new Frame($frameVars, $paramFlags));

        $r = self::invokeBlockBody($blk, $self, $env);
        $env->pop();
        return $r;
    }

    public static function invokeBlockBody(DOMElement $blk, Instance $self, Environment $env): mixed
    {
        /** @var DOMElement[] $assigns */
        $assigns = [];
        foreach ($blk->childNodes as $node) {
            if ($node instanceof DOMElement && $node->tagName === 'assign') {
                $o = (int)$node->getAttribute('order');
                $assigns[$o] = $node;
            }
        }
        ksort($assigns);
        $last = NilB::get();

        foreach ($assigns as $as) {
            $varNode = $as->getElementsByTagName('var')->item(0);
            if (!$varNode instanceof DOMElement) {
                throw InterpreterException::parse("Missing <var>");
            }
            $vn = $varNode->getAttribute('name');
            $exprNode = $as->getElementsByTagName('expr')->item(0);
            if (!$exprNode instanceof DOMElement) {
                throw InterpreterException::parse("Missing <expr>");
            }
            $val = self::evalExpr($exprNode, $self, $env);
            $env->set($vn, $val);
            $last = $val;
        }
        return $last;
    }

    private static function evalExpr(DOMElement $expr, Instance $self, Environment $env): mixed
    {
        // skip whitespace/text and get the real child
        $node = self::firstElementChild($expr);

        switch ($node->nodeName) {
            case 'literal':
                $cls = $node->getAttribute('class');
                $val = $node->getAttribute('value');
                return match ($cls) {
                    'Integer' => new IntegerB((int)$val),
                    'String'  => new StringB(self::unescapeString($val)),
                    'Nil'     => NilB::get(),
                    'True'    => TrueB::get(),
                    'False'   => FalseB::get(),
                    'class'   => ClassTable::getInstance()->getClass($val),
                    default => throw InterpreterException::parse("Unknown literal class '$cls'"),
                };

            case 'var':
                $name = $node->getAttribute('name');
                return $env->lookup($name);

            case 'block':
                return new Block(
                    (int)$node->getAttribute('arity'),
                    $self,
                    $env->clone(),
                    $node
                );

            case 'send':
                // 1) find the receiver <expr> (wherever it sits)
                $recv = null;
                foreach ($node->childNodes as $child) {
                    if (
                        $child instanceof DOMElement && $child->nodeName === 'expr'
                        && $child->parentNode === $node
                    ) {  // Direct child of the send
                    // Get result of evaluating this expression - important for nested sends!
                        $recv = self::evalExpr($child, $self, $env);
                        break;
                    }
                }
                if ($recv === null) {
                    throw InterpreterException::parse("Malformed <send>, missing receiver");
                }

                // 2) collect direct <arg> children and their direct <expr> children
                $args = [];
                foreach ($node->childNodes as $child) {
                    if ($child instanceof DOMElement && $child->nodeName === 'arg') {
                        $ord = (int)$child->getAttribute('order');

                        // Find direct <expr> child
                        $exprChild = null;
                        foreach ($child->childNodes as $argChild) {
                            if ($argChild instanceof DOMElement && $argChild->nodeName === 'expr') {
                                $exprChild = $argChild;
                                break;
                            }
                        }

                        if ($exprChild === null) {
                            throw InterpreterException::parse("Expected exactly one expr inside arg");
                        }

                        $args[$ord] = self::evalExpr($exprChild, $self, $env);
                    }
                }
                ksort($args);

                return self::send(
                    $recv,
                    $node->getAttribute('selector'),
                    array_values($args)
                );

            default:
                throw InterpreterException::parse("Unknown expression '{$node->nodeName}'");
        }
    }

    /** Helper: get the first DOMElement child (skips text nodes). */
    private static function firstElementChild(\DOMNode $n): DOMElement
    {
        foreach ($n->childNodes as $c) {
            if ($c instanceof DOMElement) {
                return $c;
            }
        }
        throw new RuntimeException("Expected element child");
    }

    private static function unescapeString(string $s): string
    {
        $result = preg_replace_callback(
            '/\\\\(n|\'|\\\\)/',
            function (array $m): string {
                return match ($m[1]) {
                    'n'  => "\n",
                    '\'' => "'",
                    '\\' => "\\",
                };
            },
            $s
        );
        // preg_replace_callback can return null on error
        return $result ?? $s;
    }
}
