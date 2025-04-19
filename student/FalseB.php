<?php

namespace IPP\Student;

/**
 * FalseB singleton implementation
 */
final class FalseB extends Base
{
    private static ?self $inst = null;

    private function __construct()
    {
        parent::__construct('False');
    }

    public static function get(): self
    {
        return self::$inst ??= new self();
    }

    /**
     * @param mixed[] $args
     */
    public function builtin(string $sel, array $args): mixed
    {
        return match ($sel) {
            'not'               => TrueB::get(),
            'and:'              => FalseB::get(),
            'or:'               => MessageDispatcher::send($args[0], 'value', []),
            'ifTrue:ifFalse:'   => MessageDispatcher::send($args[1], 'value', []),
            default             => parent::builtin($sel, $args),
        };
    }
}
