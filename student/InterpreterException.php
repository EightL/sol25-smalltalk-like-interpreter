<?php

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use Throwable;

/**
 * Exception class for all interpreter errors with factory methods for specific types
 */
class InterpreterException extends IPPException
{
    public static function syntax(string $msg = "Syntax error", ?Throwable $p = null): self
    {
        return new self($msg, 21, $p);
    }

    public static function parse(string $msg = "Parse error", ?Throwable $p = null): self
    {
        return new self($msg, 22, $p);
    }

    public static function classNotFound(string $m = "Class not found", ?Throwable $p = null): self
    {
        return new self($m, 31, $p);
    }

    public static function name(string $m = "Name error", ?Throwable $p = null): self
    {
        return new self($m, 32, $p);
    }

    public static function argument(string $m = "Argument error", ?Throwable $p = null): self
    {
        return new self($m, 33, $p);
    }

    public static function scope(string $m = "Scope error", ?Throwable $p = null): self
    {
        return new self($m, 34, $p);
    }

    public static function methodNotFound(string $m = "Method not found", ?Throwable $p = null): self
    {
        return new self($m, 51, $p);
    }

    /** "Any other runtime error" per spec §5 ⇒ code 52 */
    public static function other(string $m = "Runtime error", ?Throwable $p = null): self
    {
        return new self($m, 52, $p);
    }

    /** Bad argument to a built‑in: code 53 */
    public static function runtime(string $m = "Runtime error", ?Throwable $p = null): self
    {
        return new self($m, 53, $p);
    }

    // bad xml
    public static function xml(string $m = "XML error", ?Throwable $p = null): self
    {
        return new self($m, 41, $p);
    }
}
