<?php
/**
 * @author Roberto Rielo <roberto910907@gmail.com>.
 * @version laravel-console-generator v0.1
 */
declare(strict_types=1);


namespace ConsoleGenerator\Util;

use Illuminate\Support\Str;

class ModelClassDetails
{
    /**
     * @param string $name
     * @param string $path
     * @param string $namespace
     */
    public function __construct(
        private string $name,
        private string $path,
        private string $namespace
    ) {
        //...
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return Str::snake(Str::pluralStudly($this->name));
    }
}
