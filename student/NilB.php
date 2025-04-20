<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file NilB.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

/**
 * Nil singleton implementation
 */
final class NilB extends Base
{
    private static ?self $inst = null;

    private function __construct()
    {
        parent::__construct('Nil'); // initializing with class name 'Nil'
    }

    /**
     * @return self singleton instance of Nil
     */
    public static function get(): self
    {
        // instantiating singleton on first request
        return self::$inst ??= new self();
    }

    /**
     * @param string    $sel selector name
     * @param mixed[]   $args message arguments
     * @return mixed    result of builtin dispatch
     */
    public function builtin(string $sel, array $args): mixed
    {
        // we dispatch nil‑specific methods first
        return match ($sel) {
            'asString' => new StringB('nil'),  // representing nil as string "nil"
            'isNil'    => TrueB::get(),        // nil is truthy only for isNil
            default    => parent::builtin($sel, $args), // fallback to base implementations
        };
    }
}
