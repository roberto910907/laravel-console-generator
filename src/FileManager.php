<?php
/**
 * @author Roberto Rielo <roberto910907@gmail.com>.
 * @version laravel-console-generator v0.1
 */
declare(strict_types=1);

namespace ConsoleGenerator;

use Illuminate\Filesystem\Filesystem;

class FileManager
{
    public function __construct(private Filesystem $filesystem)
    {
        //...
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function fileExists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }

    /**
     * @param string $filePath
     * @param string $content
     */
    public function dumpFile(string $filePath, string $content): void
    {
        $this->filesystem->put($filePath, $content);
    }

    /**
     * @param string $templatePath
     * @param array $parameters
     *
     * @return string
     */
    public function parseTemplate(string $templatePath, array $parameters): string
    {
        ob_start();
        extract($parameters, \EXTR_SKIP);
        include $templatePath;

        return ob_get_clean();
    }
}
