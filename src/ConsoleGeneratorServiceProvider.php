<?php

namespace ConsoleGenerator\ConsoleGenerator;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use ConsoleGenerator\ConsoleGenerator\Commands\ModelMakeCommand;

class ConsoleGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-console-generator')
            ->hasConfigFile()
            ->hasCommand(ModelMakeCommand::class);
    }
}
