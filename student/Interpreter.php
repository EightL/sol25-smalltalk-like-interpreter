<?php

/**
 * Part 2 – SOL25 Interpreter (spec‑compliant implementation)
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

    public static function getInstance(): ?Interpreter
    {
        return self::$instance;
    }

    public function readString(): ?string
    {
        return $this->input->readString();
    }

    public function writeString(string $s): void
    {
        $this->stdout->writeString($s);
    }

    public function execute(): int
    {
        self::$instance = $this;
        try {
            // 1) XML well‑formed?
            $dom = $this->source->getDOMDocument();
            // 2) load AST + run Main>>run
            ClassTable::getInstance()->loadFromDOM($dom);
            $main = ClassTable::getInstance()->getClass('Main');
            MessageDispatcher::send($main->instantiate(), 'run', []);
            return 0;
        } finally {
            self::$instance = null;
        }
    }
}
