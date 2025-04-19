<?php

namespace IPP\Student;

/**
 * Nil singleton implementation
 */
final class NilB extends Base
{
    private static ?self $inst = null;

    private function __construct()
    {
        parent::__construct('Nil');
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
            'asString' => new StringB('nil'),
            'isNil'    => TrueB::get(),
            default    => parent::builtin($sel, $args),
        };
    }
}
