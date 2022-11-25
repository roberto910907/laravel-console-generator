<?php

namespace ConsoleGenerator\ConsoleGenerator\Commands;

use Illuminate\Console\Command;

class ModelMakeCommand extends Command
{
    public $signature = 'make:model';

    public $description = 'Generates a model class using the command line';

    public function handle(): int
    {
        $this->ask('Model`s name: ');

        return self::SUCCESS;
    }
}
