<?php

namespace ConsoleGenerator\Commands;

use ConsoleGenerator\Util\ModelClassDetails;
use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use ConsoleGenerator\Eloquent\EloquentHelper;
use Symfony\Component\Console\Input\InputOption;
use ConsoleGenerator\Eloquent\ModelClassGenerator;

class ModelMakeCommand extends GeneratorCommand
{
    public $signature = 'make:model {name?}';

    public $description = 'Generates a model class using the command line';

    /**
     * @param Filesystem $filesystem
     * @param EloquentHelper $eloquentHelper
     * @param ModelClassGenerator $modelClassGenerator
     */
    public function __construct(
        protected Filesystem          $filesystem,
        protected EloquentHelper      $eloquentHelper,
        protected ModelClassGenerator $modelClassGenerator
    ) {
        parent::__construct($filesystem);
    }

    /**
     * @throws Exception
     *
     * @return int
     */
    public function handle(): int
    {
        $modelName = $this->getModelName();
        $qualifyClass = $this->qualifyClass($modelName);

        $modelClassDetails = new ModelClassDetails($modelName, $this->getPath($qualifyClass), $this->getNamespace($qualifyClass));

        $this->makeDirectory($modelClassDetails->getPath());

        $modelExists = $this->filesystem->exists($modelClassDetails->getPath());

        if (! $modelExists) {
            $this->modelClassGenerator->generateModelClass($modelClassDetails);

            $this->modelClassGenerator->writeChanges();
        }

        $this->info("Model generated! Now let's add some fields!");
        $this->info('You can always add more fields later manually or by re-running this command.');

        $this->newLine();

        $property = $this->ask('New property name (press <return> to stop adding fields):');

        // TODO: add model properties

        return self::SUCCESS;
    }

    /**
     * Asking for the model's name until a valid one is provided.
     *
     * @param bool $previousFailure
     *
     * @return string
     */
    public function getModelName(bool $previousFailure = false): string
    {
        $modelName = $this->getNameInput();

        if (! $modelName || $previousFailure) {
            $modelName = $this->askWithCompletion('Class name of the model to create or update (e.g. User):', $this->eloquentHelper->getModelsForAutocomplete());
        }

        if ($this->isReservedName($modelName)) {
            $this->error('The name "' . $modelName . '" is reserved by PHP.');

            return $this->getModelName(true);
        }

        if ((! $this->hasOption('force') ||
                ! $this->option('force')) &&
            $this->alreadyExists($modelName)) {
            $this->error(sprintf('%s model already exists.', $modelName));

            return $this->getModelName(true);
        }

        return $modelName;
    }

    protected function getStub()
    {
        // TODO: Implement getStub() method.
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return is_dir(app_path('Models')) ? $rootNamespace . '\\Models' : $rootNamespace;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, seeder, factory, policy, resource controller, and form request classes for the model'],
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model'],
            ['seed', 's', InputOption::VALUE_NONE, 'Create a new seeder for the model'],
        ];
    }
}
