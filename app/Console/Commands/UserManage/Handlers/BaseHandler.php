<?php

namespace App\Console\Commands\UserManage\Handlers;

use Illuminate\Console\Command;

abstract class BaseHandler
{
    protected Command $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    protected function info(string $message): void
    {
        $this->command->info($message);
    }

    protected function error(string $message): void
    {
        $this->command->error($message);
    }

    protected function warn(string $message): void
    {
        $this->command->warn($message);
    }

    protected function line(string $message = ''): void
    {
        $this->command->line($message);
    }

    protected function newLine(int $count = 1): void
    {
        $this->command->newLine($count);
    }

    protected function ask(string $question, ?string $default = null): string
    {
        return $this->command->ask($question, $default) ?? '';
    }

    protected function confirm(string $question, bool $default = false): bool
    {
        return $this->command->confirm($question, $default);
    }

    protected function choice(string $question, array $choices, ?string $default = null): string
    {
        return $this->command->choice($question, $choices, $default);
    }

    protected function table(array $headers, array $rows): void
    {
        $this->command->table($headers, $rows);
    }
}

