<?php
/**
 * @author Roberto Rielo <roberto910907@gmail.com>.
 *
 * @version laravel-console-generator v0.1
 */
declare(strict_types=1);

namespace ConsoleGenerator\Eloquent;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class EloquentHelper
{
    /**
     * @return array
     */
    public function getModelsForAutocomplete(): array
    {
        return collect(File::allFiles(config('console-generator.models_path')))
            ->map(function (SplFileInfo $fileInfo) {
                return $fileInfo->getFilenameWithoutExtension();
            })
            ->toArray();
    }
}
