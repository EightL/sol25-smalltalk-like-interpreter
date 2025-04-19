<?php

namespace IPP\Student;

use DOMElement;

/**
 * Block implementation
 */
final class Block extends Base
{
    public function __construct(
        public int $arity,
        public ?Instance $captureSelf,
        public Environment $captureEnv,
        public ?DOMElement $body,
        ?string $className = null
    ) {
        // if they passed a subclass name, use it; otherwise default to 'Block'
        parent::__construct($className ?? 'Block');
    }

    /**
     * @param mixed[] $args
     */
    public function builtin(string $sel, array $args): mixed
    {
        // isBlock
        if ($sel === 'isBlock' && count($args) === 0) {
            return TrueB::get();
        }

        // whileTrue:
        if ($sel === 'whileTrue:' && count($args) === 1) {
            while (self::isTrue(MessageDispatcher::send($this, 'value', []))) {
                MessageDispatcher::send($args[0], 'value', []);
            }
            return NilB::get();
        }

        // Regular block invocation: value / value:value:…
        $expectedSelector = $this->arity === 0 ? 'value' : str_repeat('value:', $this->arity);

        if ($sel === $expectedSelector) {
            // we must have both a real "self" and a real DOM body
            if ($this->captureSelf === null || $this->body === null) {
                throw InterpreterException::methodNotFound("Bad block selector");
            }
            $env = new Environment();
            $body = $this->body;  // now non‑null

            // build self and super
            $frameVars = [
                'self'  => $this->captureSelf,
                'super' => new SuperProxy(
                    $this->captureSelf,
                    ClassTable::getInstance()
                        ->getClass($this->captureSelf->class)
                        ->getParent()
                ),
            ];

            // collect parameter names in order
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

            // merge in param → arg bindings
            $frameVars += array_combine($paramNames, $args);

            // make them immutable
            $paramFlags = array_fill_keys($paramNames, true);

            // push the new frame
            $env->push(new Frame($frameVars, $paramFlags));

            // evaluate the block body…
            $result = MessageDispatcher::invokeBlockBody($body, $this->captureSelf, $env);
            $env->pop();
            return $result;
        }

        throw InterpreterException::methodNotFound("Bad block selector");
    }

    private static function isTrue(mixed $o): bool
    {
        return $o instanceof Instance
            && ClassTable::getInstance()->isAncestor('True', $o->class);
    }
}
