<?php

declare(strict_types=1);

namespace App\Console;

final class Application
{
    /** @var array<string, callable(array<int, string>): int> */
    private array $commands = [];

    public function register(string $name, callable $handler): void
    {
        $this->commands[$name] = $handler;
    }

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'help';
        if (!isset($this->commands[$name])) {
            fwrite(STDERR, "Unknown command: {$name}\n");
            fwrite(STDERR, "Available: " . implode(', ', array_keys($this->commands)) . "\n");
            return 1;
        }
        return ($this->commands[$name])(array_slice($argv, 2));
    }
}
