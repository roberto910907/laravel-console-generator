<?php

namespace ConsoleGenerator;

use Spatie\LaravelPackageTools\Package;
use ConsoleGenerator\Commands\ModelMakeCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
