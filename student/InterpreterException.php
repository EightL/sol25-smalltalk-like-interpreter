<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file Interpreter.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use Throwable;

/**
 * Exception class for all interpreter errors with factory methods for specific types
 */
class InterpreterException extends IPPException
{
    /**
     * create parse error exception (code 22)
     *
     * @param string $msg custom message
     * @param Throwable|null $p previous exception
     * @return self
     */
    public static function parse(string $msg = "Parse error", ?Throwable $p = null): self
    {
        // wrapping parse issue as exception code 22
        return new self($msg, 22, $p);
    }

    /**
     * create class-not-found exception (code 31)
     *
     * @param string $m   custom message
     * @param Throwable|null $p   previous exception
     * @return self
     */
    public static function classNotFound(string $m = "Class not found", ?Throwable $p = null): self
    {
        // throwing when Main or parent class missing
        return new self($m, 31, $p);
    }

    /**
     * create name error exception (undefined variable, code 32)
     *
     * @param string $m custom message
     * @param Throwable|null $p previous exception
     * @return self
     */
    public static function name(string $m = "Name error", ?Throwable $p = null): self
    {
        // signaling undefined name or param
        return new self($m, 32, $p);
    }

    /**
     * create argument error exception (bad arity, code 33)
     *
     * @param string $m custom message
     * @param Throwable|null $p previous exception
     * @return self
     */
    public static function argument(string $m = "Argument error", ?Throwable $p = null): self
    {
        // indicating wrong number of args in method/block
        return new self($m, 33, $p);
    }

    /**
     * create scope error exception (assign to param, code 34)
     *
     * @param string $m custom message
     * @param Throwable|null $p previous exception
     * @return self
     */
    public static function scope(string $m = "Scope error", ?Throwable $p = null): self
    {
        // forbidding reassignment to immutable param
        return new self($m, 34, $p);
    }

    /**
     * create method-not-found exception (code 51)
     *
     * @param string $m custom message
     * @param Throwable|null $p previous exception
     * @return self
     */
    public static function methodNotFound(string $m = "Method not found", ?Throwable $p = null): self
    {
        // used when no handler for selector
        return new self($m, 51, $p);
    }

    /**
     * create generic runtime error exception (code 52)
     *
     * @param string $m custom message
     * @param Throwable|null $p previous exception
     * @return self
     */
    public static function other(string $m = "Runtime error", ?Throwable $p = null): self
    {
        // catching any other runtime issues
        return new self($m, 52, $p);
    }

    /**
     * create built-in runtime error exception (bad arg, code 53)
     *
     * @param string $m custom message
     * @param Throwable|null $p previous exception
     * @return self
     */
    public static function runtime(string $m = "Runtime error", ?Throwable $p = null): self
    {
        // thrown when built-in receives bad argument
        return new self($m, 53, $p);
    }

    /**
     * create XML error exception (malformed XML, code 41)
     *
     * @param string $m custom message
     * @param Throwable|null $p previous exception
     * @return self
     */
    public static function xml(string $m = "XML error", ?Throwable $p = null): self
    {
        // thrown when source DOM is not well formed
        return new self($m, 41, $p);
    }
}
