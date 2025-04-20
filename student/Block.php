<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file Block.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

use DOMElement;

/**
 * Block implementation - wraps SOL25 code blocks
 */
final class Block extends Base
{
    /**
     * Block constructor captures arity, self, env, and body
     *
     * @param int $arity number of expected args
     * @param Instance|null $captureSelf self for block execution
     * @param Environment  $captureEnv env to execute block in
     * @param DOMElement|null $body AST element of block body
     * @param string|null $className optional subclass name
     */
    public function __construct(
        public int $arity,
        public ?Instance $captureSelf,
        public Environment $captureEnv,
        public ?DOMElement $body,
        ?string $className = null
    ) {
        // default to 'Block' if no subclass name
        parent::__construct($className ?? 'Block');
    }

    /**
     * @param string $sel selector name for block builtin / invocation
     * @param mixed[] $args args passed to block
     * @return mixed result of block execution or control structure
     */
    public function builtin(string $sel, array $args): mixed
    {
        // isBlock test -> always true for Block
        if ($sel === 'isBlock' && count($args) === 0) {
            return TrueB::get();
        }

        // whileTrue: -> looping until block returns false
        if ($sel === 'whileTrue:' && count($args) === 1) {
            while (self::isTrue(MessageDispatcher::send($this, 'value', []))) {
                MessageDispatcher::send($args[0], 'value', []);
            }
            return NilB::get();
        }

        // value or value:value:... for block invocation
        $expectedSelector = $this->arity === 0 ? 'value'
            : str_repeat('value:', $this->arity); // pretty simple dynamic selector build

        if ($sel === $expectedSelector) {
            // must have captured self and body to execute
            if ($this->captureSelf === null || $this->body === null) {
                throw InterpreterException::methodNotFound("Bad block selector");
            }
            $env = new Environment();
            $body = $this->body; // now non-null

            // prepare frame vars
            $frameVars = [
                'self'  => $this->captureSelf,
                'super' => new SuperProxy(
                    $this->captureSelf,
                    ClassTable::getInstance()
                        ->getClass($this->captureSelf->class)
                        ->getParent()
                ),
            ];

            // collecting param names in order
            $paramNames = [];
            foreach ($body->childNodes as $child) {
                if (
                    $child instanceof DOMElement
                    && $child->tagName === 'parameter'
                    && $child->parentNode === $body
                ) {
                    $paramNames[] = $child->getAttribute('name');
                }
            }

            // we bind params to args in frame
            $frameVars += array_combine($paramNames, $args);

            // again, mark params immutable
            $paramFlags = array_fill_keys($paramNames, true);

            // and push frame then eval body and pop
            $env->push(new Frame($frameVars, $paramFlags));
            $result = MessageDispatcher::invokeBlockBody($body, $this->captureSelf, $env);
            $env->pop();
            return $result;
        }

        // selector not matching -> error
        throw InterpreterException::methodNotFound("Bad block selector");
    }

    /**
     * check if object is a True instance
     *
     * @param mixed $o any object to test
     * @return bool true if $o is instance of True
     */
    private static function isTrue(mixed $o): bool
    {
        // checking class ancestry for True
        return $o instanceof Instance && ClassTable::getInstance()->isAncestor('True', $o->class);
    }
}
