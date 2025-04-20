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
use RuntimeException;

/**
 * MessageDispatcher handles message sends in SOL25 interpreter
 */
class MessageDispatcher
{
    /**
     * Dispatch a message to a receiver, handling super, builtins, user methods, attrs
     *
     * @param mixed       $recv object, class def, or super proxy
     * @param string      $sel  selector name of the message
     * @param mixed[]     $args arguments for the message
     * @return mixed result of message send
     */
    public static function send(mixed $recv, string $sel, array $args): mixed
    {
        // we unwrap super proxy -> capture startClass then use actual target
        $startClass = null;
        if ($recv instanceof SuperProxy) {
            $startClass = $recv->startClass;
            $recv = $recv->target;
        }

        // handling class constructors - new, from:, read for String
        if ($recv instanceof ClassDef) {
            if ($sel === 'new' && $args === []) {
                // instantiating new instance without args
                return $recv->instantiate();
            }
            if ($sel === 'from:' && count($args) === 1) {
                // instantiating by copying internal attrs from arg
                return $recv->instantiateFrom($args[0]);
            }
            if ($recv->getName() === 'String' && $sel === 'read') {
                // reading from input -> StringB::read builtin
                return StringB::read();
            }
        }

        // fast path builtins if no user method found and not super dispatch
        if ($startClass === null && $recv instanceof Instance) {
            $cd = ClassTable::getInstance()->getClass($recv->class);
            $hasUser = false;
            // we scan up class chain for user-defined method -> else fall back to builtin
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
                try {
                    // no user method -> use builtin implementation
                    return $recv->builtin($sel, $args);
                } catch (InterpreterException $e) {
                    // non-method-not-found error -> rethrow
                    if ($e->getCode() !== 51) {
                        throw $e;
                    }
                }
            }
        }

        // user methods dispatch
        if ($recv instanceof Instance) {
            // we start lookup at startClass if super, else at receiver's class
            $cd = $startClass ? $startClass : ClassTable::getInstance()->getClass($recv->class);
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

        // or second chance builtins (inherited block methods, boolean, etc.)
        if ($recv instanceof Instance) {
            try {
                return $recv->builtin($sel, $args);
            } catch (InterpreterException $e) {
                if ($e->getCode() !== 51) {
                    throw $e;
                }
            }
        }

        // attribute setter - sel ends with ':' and one arg -> set in attr map
        if (str_ends_with($sel, ':') && count($args) === 1 && $recv instanceof Instance) {
            $prop = substr($sel, 0, -1);
            // guard collision - no method or setter with same name
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
            // setting attr and returning self
            $recv->attr[$prop] = $args[0];
            return $recv;
        }

        // attribute getter - no ':' in sel -> get from attr map
        if ($recv instanceof Instance && !str_contains($sel, ':')) {
            if (!array_key_exists($sel, $recv->attr)) {
                throw InterpreterException::methodNotFound();
            }
            // returning stored attribute value
            return $recv->attr[$sel];
        }

        // no match for anything -> method not found
        throw InterpreterException::methodNotFound("No method or attribute '$sel'");
    }

    /**
     * invoke a user-defined method with given block def and args
     *
     * @param Instance $self the receiver of method
     * @param MethodDef $def method definition including block node
     * @param mixed[] $args message arguments
     * @return mixed result of method block execution
     */
    private static function invoke(Instance $self, MethodDef $def, array $args): mixed
    {
        // extracting parameter names in order and then binding to args
        $blk = $def->getBlockNode();
        $params = [];
        foreach ($blk->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'parameter') {
                $order = (int)$child->getAttribute('order');
                $params[$order] = $child->getAttribute('name');
            }
        }
        ksort($params); // ksorting to preserve order
        if (count($params) !== count($args)) {
            throw InterpreterException::methodNotFound("Param count");
        }
        $env = new Environment();

        // marking params immutable so we cannot reassign (as specified in SOL25)
        $paramFlags = array_fill_keys(array_values($params), true);

        // we prepare frame vars: self, super proxy
        $frameVars = [
            'self'  => $self,
            'super' => new SuperProxy(
                $self,
                ClassTable::getInstance()->getClass($self->class)->getParent()
            )
        ];

        // adding args bound to param names
        $frameVars += array_combine(array_values($params), $args);

        // pushing new frame then execute block -> then pop
        $env->push(new Frame($frameVars, $paramFlags));
        $r = self::invokeBlockBody($blk, $self, $env);
        $env->pop();
        return $r;
    }

    /**
     * Execute block body: evaluate assigns in order then return last value
     *
     * @param DOMElement $blk  block element from AST
     * @param Instance   $self receiver for sends within block
     * @param Environment $env current eval environment
     * @return mixed last evaluated value or nil
     */
    public static function invokeBlockBody(DOMElement $blk, Instance $self, Environment $env): mixed
    {
        // we collect assigns by order and then eval each
        $assigns = [];
        foreach ($blk->childNodes as $node) {
            if ($node instanceof DOMElement && $node->tagName === 'assign') {
                $o = (int)$node->getAttribute('order');
                $assigns[$o] = $node;
            }
        }
        ksort($assigns); // ksorting to preserve order
        $last = NilB::get();

        foreach ($assigns as $as) {
            // getting var name -> then eval expr -> then set in env
            $varNode = $as->getElementsByTagName('var')->item(0);
            // Im also checking parser errors, i don't know, just in case
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

    /**
     * Evaluate expression node: literal, var, block, or send
     *
     * @param DOMElement $expr expression element
     * @param Instance   $self receiver for nested sends
     * @param Environment $env current eval environment
     * @return mixed evaluated object or value
     */
    private static function evalExpr(DOMElement $expr, Instance $self, Environment $env): mixed
    {
        // skipping text, getting first element child
        $node = self::firstElementChild($expr);

        switch ($node->nodeName) {
            // if its literal
            case 'literal':
                // we create an appropriate builtin object
                $cls = $node->getAttribute('class');
                $val = $node->getAttribute('value');
                // matches with the corresponding class and returns the object
                return match ($cls) {
                    'Integer' => new IntegerB((int)$val),
                    'String'  => new StringB(self::unescapeString($val)),
                    'Nil'     => NilB::get(),
                    'True'    => TrueB::get(),
                    'False'   => FalseB::get(),
                    'class'   => ClassTable::getInstance()->getClass($val),
                    default   => throw InterpreterException::parse("Unknown literal class '$cls'"),
                };

            // if its var
            case 'var':
                // look up variable in env
                $name = $node->getAttribute('name');
                return $env->lookup($name);

            // if its block
            case 'block':
                // wrapping AST node into Block object -> then send value when invoked
                return new Block(
                    (int)$node->getAttribute('arity'),
                    $self,
                    $env->clone(),
                    $node
                );

            // for send
            case 'send':
                // we find receiver expr child and eval first
                $recv = null;
                foreach ($node->childNodes as $child) {
                    if ($child instanceof DOMElement && $child->nodeName === 'expr' && $child->parentNode === $node) {
                        $recv = self::evalExpr($child, $self, $env);
                        break;
                    }
                }
                if ($recv === null) {
                    throw InterpreterException::parse("Malformed <send>, missing receiver");
                }

                // collecting arg values by order -> then dispatch send
                $args = [];
                foreach ($node->childNodes as $child) {
                    if ($child instanceof DOMElement && $child->nodeName === 'arg') {
                        $ord = (int)$child->getAttribute('order');
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
                ksort($args); // ksorting it to preserve order

                return self::send($recv, $node->getAttribute('selector'), array_values($args));

            default:
                throw InterpreterException::parse("Unknown expression '{$node->nodeName}'");
        }
    }

    /**
     * Helper to get first DOMElement child
     *
     * @param \DOMNode $n any DOM node
     * @return DOMElement first element child
     * @throws RuntimeException if none found
     */
    private static function firstElementChild(\DOMNode $n): DOMElement
    {
        foreach ($n->childNodes as $c) {
            if ($c instanceof DOMElement) {
                return $c;
            }
        }
        throw new RuntimeException("Expected element child");
    }

    /**
     * Unescape SOL25 string literal sequences (\n, \', \\)
     *
     * @param string $s escaped string from XML
     * @return string unescaped string value
     */
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
        // if regex error, return original
        return $result ?? $s;
    }
}
