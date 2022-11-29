<?php
/**
 * @author Roberto Rielo <roberto910907@gmail.com>.
 *
 * @version laravel-console-generator v0.1
 */
declare(strict_types=1);

namespace ConsoleGenerator\Generator;

use Exception;
use ConsoleGenerator\FileManager;
use ConsoleGenerator\Util\ModelClassDetails;

class Generator
{
    private array $pendingOperations = [];

    public function __construct(private FileManager $fileManager)
    {
    }

    /**
     * @param ModelClassDetails $modelClassDetails
     * @param string $templateName
     * @param array $variables
     *
     * @throws Exception
     * @return string
     */
    public function generateClass(ModelClassDetails $modelClassDetails, string $templateName, array $variables = []): string
    {
        $targetPath = $modelClassDetails->getPath();

        $variables = array_merge($variables, [
            'className' => $modelClassDetails->getName(),
            'namespace' => $modelClassDetails->getNamespace(),
        ]);

        $this->addOperation($targetPath, $templateName, $variables);

        return $targetPath;
    }

    /**
     * @param string $targetPath
     * @param string $templateName
     * @param array $variables
     *
     * @throws Exception
     */
    private function addOperation(string $targetPath, string $templateName, array $variables): void
    {
        if ($this->fileManager->fileExists($targetPath)) {
            throw new Exception(sprintf('The model "%s" can\'t be generated because it already exists.', $targetPath));
        }

        $templatePath = $templateName;
        if (! file_exists($templatePath)) {
            $templatePath = __DIR__ . '/../Templates/' . $templateName;

            if (! file_exists($templatePath)) {
                throw new Exception(sprintf('Cannot find template "%s"', $templateName));
            }
        }

        $this->pendingOperations[$targetPath] = [
            'template' => $templatePath,
            'variables' => $variables,
        ];
    }

    public function hasPendingOperations(): bool
    {
        return ! empty($this->pendingOperations);
    }

    /**
     * Actually writes and file changes that are pending.
     *
     * @throws Exception
     */
    public function writeChanges(): void
    {
        foreach ($this->pendingOperations as $targetPath => $templateData) {
            if (isset($templateData['contents'])) {
                $this->fileManager->dumpFile($targetPath, $templateData['contents']);

                continue;
            }

            $this->fileManager->dumpFile(
                $targetPath,
                $this->getFileContentsForPendingOperation($targetPath)
            );
        }

        $this->pendingOperations = [];
    }

    /**
     * @param string $targetPath
     *
     * @throws Exception
     *
     * @return string
     */
    public function getFileContentsForPendingOperation(string $targetPath): string
    {
        if (! isset($this->pendingOperations[$targetPath])) {
            throw new Exception(sprintf('File "%s" is not in the Generator\'s pending operations', $targetPath));
        }

        $templatePath = $this->pendingOperations[$targetPath]['template'];
        $parameters = $this->pendingOperations[$targetPath]['variables'];

        return $this->fileManager->parseTemplate($templatePath, $parameters);
    }
}
