<?php

namespace IPP\Student;

/**
 * True singleton implementation
 */
final class TrueB extends Base
{
    private static ?self $inst = null;

    private function __construct()
    {
        parent::__construct('True');
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
            'not'               => FalseB::get(),
            'and:'              => MessageDispatcher::send($args[0], 'value', []),
            'or:'               => TrueB::get(),
            'ifTrue:ifFalse:'   => MessageDispatcher::send($args[0], 'value', []),
            default             => parent::builtin($sel, $args),
        };
    }
}
