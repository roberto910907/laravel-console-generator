<?php
/**
 * @author Roberto Rielo <roberto910907@gmail.com>.
 *
 * @version laravel-console-generator v0.1
 */
declare(strict_types=1);

namespace ConsoleGenerator\Eloquent;

use Exception;
use ConsoleGenerator\Generator\Generator;
use ConsoleGenerator\Util\ModelClassDetails;

final class ModelClassGenerator
{
    public function __construct(private Generator $generator)
    {
        //...
    }

    /**
     * @param ModelClassDetails $modelClassDetails
     *
     * @throws Exception
     * @return string
     */
    public function generateModelClass(ModelClassDetails $modelClassDetails): string
    {
        return $this->generator->generateClass($modelClassDetails, 'Model.tpl.php', []);
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public function writeChanges(): void
    {
        $this->generator->writeChanges();
    }
}
