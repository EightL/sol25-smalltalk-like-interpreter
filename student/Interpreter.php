<?php

/**
 * IPP Project 2 - SOL25 interpreter
 *
 * @file Interpreter.php
 * @author xsevcim00
 * @date 2025-04-20
 */

namespace IPP\Student;

use DOMDocument;
use IPP\Core\AbstractInterpreter;

/**
 * Main interpreter class - entry point
 */
class Interpreter extends AbstractInterpreter
{
    private static ?Interpreter $instance = null;

    /**
     * Get current interpreter instance
     *
     * @return Interpreter|null
     */
    public static function getInstance(): ?Interpreter
    {
        return self::$instance;
    }

    /**
     * read next line of input as string (or null on EOF)
     *
     * @return string|null
     */
    public function readString(): ?string
    {
        // forward to input reader
        return $this->input->readString();
    }

    /**
     * Write string to output
     *
     * @param string $s text to write
     */
    public function writeString(string $s): void
    {
        $this->stdout->writeString($s);
    }

    /**
     * Execute interpreter: parse XML, load classes, run Main>>run
     *
     * @return int exit code (0 on success)
     */
    public function execute(): int
    {
        // set singleton for global access during execution
        self::$instance = $this;
        try {
            // parse and validate XML AST
            $dom = $this->source->getDOMDocument();

            // build class definitions from AST
            ClassTable::getInstance()->loadFromDOM($dom);

            // instantiate Main and invoke run method
            $main = ClassTable::getInstance()->getClass('Main');
            MessageDispatcher::send($main->instantiate(), 'run', []);

            return 0;
        } finally {
            // clear singleton after done
            self::$instance = null;
        }
    }
}
