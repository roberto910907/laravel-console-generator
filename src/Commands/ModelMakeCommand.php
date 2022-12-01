<?php

namespace ConsoleGenerator\Commands;

use Exception;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use ConsoleGenerator\Util\ModelClassDetails;
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
        $modelName = $this->askForModelName();
        $qualifyClass = $this->qualifyClass($modelName);

        $modelClassDetails = new ModelClassDetails($modelName, $this->getPath($qualifyClass), $this->getNamespace($qualifyClass));

        $this->makeDirectory($modelClassDetails->getPath());

        $modelExists = $this->filesystem->exists($modelClassDetails->getPath());

        if (! $modelExists) {
            $this->modelClassGenerator->generateModelClass($modelClassDetails);

            $this->modelClassGenerator->writeChanges();
        }

        if ($modelExists) {
            $this->info('Your model already exists! So let\'s add some new fields!');
        } else {
            $this->info('Model generated! Now let\'s add some fields!');
            $this->info('You can always add more fields later manually or by re-running this command.');
        }

        $this->newLine();

        $isFirstField = true;
        $currentFields = [];

        while (true) {
            $newField = $this->askForNextField($currentFields, $modelName, $isFirstField);
            $isFirstField = false;

            if (null === $newField) {
                break;
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param array $fields
     * @param string $modelClass
     * @param bool $isFirstField
     *
     * @return array|null
     */
    private function askForNextField(array $fields, string $modelClass, bool $isFirstField): ?array
    {
        $this->newLine();

        if ($isFirstField) {
            $questionText = 'New property name (press <return> to stop adding fields)';
        } else {
            $questionText = 'Add another property? Enter the property name (or press <return> to stop adding fields)';
        }

        $fieldName = $this->ask($questionText);

        // allow it to be empty
        if (! $fieldName) {
            return null;
        }

        if (\in_array($fieldName, $fields)) {
            throw new InvalidArgumentException(sprintf('The "%s" property already exists.', $fieldName));
        }

        // TODO: validate that the field name is not a database reserved key

        $allValidTypes = EloquentHelper::getAllValidTypes();
        $defaultFieldType = $this->guessFieldTypeByName($fieldName);

        $fieldType = null;
        while (null === $fieldType) {
            $fieldType = $this->askWithCompletion('Field type (enter <comment>?</comment> to see all types)', $allValidTypes, $defaultFieldType);

            if ('?' === $fieldType) {
                $this->printAvailableTypes();
                $this->newLine();

                $fieldType = null;
            } elseif (! \in_array($fieldType, $allValidTypes)) {
                $this->printAvailableTypes();
                $this->error(sprintf('Invalid type "%s".', $fieldType));
                $this->newLine();

                $fieldType = null;
            }
        }

        if (in_array($fieldType, EloquentHelper::getValidRelationTypes())) {
            return $this->askRelationDetails($modelClass, $fieldType, $fieldName);
        }

        // this is a normal field
        $data = ['fieldName' => $fieldName, 'type' => $fieldType];

        if ('string' === $fieldType) {
            // default to 255, avoid the question
            $data['length'] = $this->ask('Field length', 255);
        } elseif ('decimal' === $fieldType) {
            // 10 is the default value given in \Doctrine\DBAL\Schema\Column::$_precision
            $data['precision'] = $this->ask('Precision (total number of digits stored: 100.00 would be 5)', 10);

            // 0 is the default value given in \Doctrine\DBAL\Schema\Column::$_scale
            $data['scale'] = $this->ask('Scale (number of decimals to store: 100.00 would be 2)', 0);
        }

        if ($this->confirm('Can this field be null in the database (nullable)', false)) {
            $data['nullable'] = true;
        }

        return $data;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function guessFieldTypeByName(string $name): string
    {
        $defaultType = 'string';
        // try to guess the type by the field name prefix/suffix
        // convert to snake case for simplicity
        $snakeCasedField = Str::snake($name);

        if (Str::endsWith($snakeCasedField, '_at')) {
            $defaultType = 'datetime_immutable';
        } elseif (Str::endsWith($snakeCasedField, '_date')) {
            $defaultType = 'datetime_immutable';
        } elseif (Str::endsWith($snakeCasedField, '_id')) {
            $defaultType = 'integer';
        } elseif (Str::startsWith($snakeCasedField, 'is_')) {
            $defaultType = 'boolean';
        } elseif (Str::startsWith($snakeCasedField, 'has_')) {
            $defaultType = 'boolean';
        } elseif ('uuid' === $snakeCasedField) {
            $defaultType = 'uuid';
        } elseif ('guid' === $snakeCasedField) {
            $defaultType = 'guid';
        }

        return $defaultType;
    }

    /**
     * Asking for the model's name until a valid one is provided.
     *
     * @param bool $previousFailure
     *
     * @return string
     */
    public function askForModelName(bool $previousFailure = false): string
    {
        $modelName = $this->getNameInput();

        if (! $modelName || $previousFailure) {
            $modelName = $this->askWithCompletion('Class name of the model to create or update (e.g. User):', $this->eloquentHelper->getModelsForAutocomplete());
        }

        if ($this->isReservedName($modelName)) {
            $this->error('The name "' . $modelName . '" is reserved by PHP.');

            return $this->askForModelName(true);
        }

        if ((! $this->hasOption('force') || ! $this->option('force')) && $this->alreadyExists($modelName)) {
            $this->error(sprintf('%s model already exists.', $modelName));

            return $this->askForModelName(true);
        }

        return $modelName;
    }

    /**
     * @return void
     */
    private function printAvailableTypes(): void
    {
        $typesTable = EloquentHelper::getTypesTable();
        $allTypes = EloquentHelper::getValidColumnTypes();

        $printSection = function (array $sectionTypes) use (&$allTypes) {
            foreach ($sectionTypes as $mainType => $subTypes) {
                unset($allTypes[$mainType]);
                $line = sprintf('  * <comment>%s</comment>', $mainType);

                if (\is_string($subTypes) && $subTypes) {
                    $line .= sprintf(' (%s)', $subTypes);
                } elseif (\is_array($subTypes) && ! empty($subTypes)) {
                    $line .= sprintf(' (or %s)', implode(', ', array_map(function ($subType) {
                        return sprintf('<comment>%s</comment>', $subType);
                    }, $subTypes)));

                    foreach ($subTypes as $subType) {
                        unset($allTypes[$subType]);
                    }
                }

                $this->line($line);
            }

            $this->newLine();
        };

        $this->line('<info>Main types</info>');
        $printSection($typesTable['main']);

        $this->line('<info>Relationships / Associations</info>');
        $printSection($typesTable['relation']);

        $this->line('<info>Array/Object Types</info>');
        $printSection($typesTable['array_object']);

        $this->line('<info>Date/Time Types</info>');
        $printSection($typesTable['date_time']);

        $this->line('<info>Other Types</info>');
        // empty the values
        $allTypes = array_map(function () {
            return [];
        }, $allTypes);

        $printSection($allTypes);
    }

    /**
     * @param string $generatedEntityClass
     * @param string $type
     * @param string $newFieldName
     *
     * @return array
     */
    private function askRelationDetails(string $generatedEntityClass, string $type, string $newFieldName): array
    {
        $targetModelClass = null;
        while (null === $targetModelClass) {
            $relatedModelName = $this->askWithCompletion('What model should this model be related to?', $this->eloquentHelper->getModelsForAutocomplete());

            $qualifyClass = $this->qualifyClass($relatedModelName);

            $relatedModelClassDetails = new ModelClassDetails($relatedModelName, $this->getPath($qualifyClass), $this->getNamespace($qualifyClass));

            if (class_exists($relatedModelClassDetails->getNamespace())) {
                $targetModelClass = $relatedModelClassDetails->getNamespace();
            } else {
                $this->error(sprintf('Unknown class "%s"', $relatedModelName));
            }
        }

        $askFieldName = function (string $targetClass, string $defaultValue) {
            return $this->ask(
                sprintf('New field name inside %s', Str::getShortClassName($targetClass)),
                $defaultValue,
            );
        };

        $askIsNullable = function (string $propertyName, string $targetClass) {
            return $this->confirm(sprintf(
                'Is the <comment>%s</comment>.<comment>%s</comment> property allowed to be null (nullable)?',
                Str::getShortClassName($targetClass),
                $propertyName
            ));
        };

        $askOrphanRemoval = function (string $owningClass, string $inverseClass) {
            $this->text([
                'Do you want to activate <comment>orphanRemoval</comment> on your relationship?',
                sprintf(
                    'A <comment>%s</comment> is "orphaned" when it is removed from its related <comment>%s</comment>.',
                    Str::getShortClassName($owningClass),
                    Str::getShortClassName($inverseClass)
                ),
                sprintf(
                    'e.g. <comment>$%s->remove%s($%s)</comment>',
                    Str::asLowerCamelCase(Str::getShortClassName($inverseClass)),
                    Str::asCamelCase(Str::getShortClassName($owningClass)),
                    Str::asLowerCamelCase(Str::getShortClassName($owningClass))
                ),
                '',
                sprintf(
                    'NOTE: If a <comment>%s</comment> may *change* from one <comment>%s</comment> to another, answer "no".',
                    Str::getShortClassName($owningClass),
                    Str::getShortClassName($inverseClass)
                ),
            ]);

            return $this->confirm(sprintf('Do you want to automatically delete orphaned <comment>%s</comment> objects (orphanRemoval)?', $owningClass), false);
        };

        $askInverseSide = function (EntityRelation $relation) {
            if ($this->isClassInVendor($relation->getInverseClass())) {
                $relation->setMapInverseRelation(false);

                return;
            }

            // recommend an inverse side, except for OneToOne, where it's inefficient
            $recommendMappingInverse = EntityRelation::ONE_TO_ONE !== $relation->getType();

            $getterMethodName = 'get'.Str::asCamelCase(Str::getShortClassName($relation->getOwningClass()));
            if (EntityRelation::ONE_TO_ONE !== $relation->getType()) {
                // pluralize!
                $getterMethodName = Str::singularCamelCaseToPluralCamelCase($getterMethodName);
            }
            $mapInverse = $this->confirm(
                sprintf(
                    'Do you want to add a new property to <comment>%s</comment> so that you can access/update <comment>%s</comment> objects from it - e.g. <comment>$%s->%s()</comment>?',
                    Str::getShortClassName($relation->getInverseClass()),
                    Str::getShortClassName($relation->getOwningClass()),
                    Str::asLowerCamelCase(Str::getShortClassName($relation->getInverseClass())),
                    $getterMethodName
                ),
                $recommendMappingInverse
            );
            $relation->setMapInverseRelation($mapInverse);
        };

        switch ($type) {
            case EntityRelation::MANY_TO_ONE:
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_ONE,
                    $generatedEntityClass,
                    $targetModelClass
                );
                $relation->setOwningProperty($newFieldName);

                $relation->setIsNullable($askIsNullable(
                    $relation->getOwningProperty(),
                    $relation->getOwningClass()
                ));

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $this->comment(sprintf(
                        'A new property will also be added to the <comment>%s</comment> class so that you can access the related <comment>%s</comment> objects from it.',
                        Str::getShortClassName($relation->getInverseClass()),
                        Str::getShortClassName($relation->getOwningClass())
                    ));
                    $relation->setInverseProperty($askFieldName(
                        $relation->getInverseClass(),
                        Str::singularCamelCaseToPluralCamelCase(Str::getShortClassName($relation->getOwningClass()))
                    ));

                    // orphan removal only applies if the inverse relation is set
                    if (!$relation->isNullable()) {
                        $relation->setOrphanRemoval($askOrphanRemoval(
                            $relation->getOwningClass(),
                            $relation->getInverseClass()
                        ));
                    }
                }

                break;
            case EntityRelation::ONE_TO_MANY:
                // we *actually* create a ManyToOne, but populate it differently
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_ONE,
                    $targetModelClass,
                    $generatedEntityClass
                );
                $relation->setInverseProperty($newFieldName);

                $this->comment(sprintf(
                    'A new property will also be added to the <comment>%s</comment> class so that you can access and set the related <comment>%s</comment> object from it.',
                    Str::getShortClassName($relation->getOwningClass()),
                    Str::getShortClassName($relation->getInverseClass())
                ));
                $relation->setOwningProperty($askFieldName(
                    $relation->getOwningClass(),
                    Str::asLowerCamelCase(Str::getShortClassName($relation->getInverseClass()))
                ));

                $relation->setIsNullable($askIsNullable(
                    $relation->getOwningProperty(),
                    $relation->getOwningClass()
                ));

                if (!$relation->isNullable()) {
                    $relation->setOrphanRemoval($askOrphanRemoval(
                        $relation->getOwningClass(),
                        $relation->getInverseClass()
                    ));
                }

                break;
            case EntityRelation::MANY_TO_MANY:
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_MANY,
                    $generatedEntityClass,
                    $targetModelClass
                );
                $relation->setOwningProperty($newFieldName);

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $this->comment(sprintf(
                        'A new property will also be added to the <comment>%s</comment> class so that you can access the related <comment>%s</comment> objects from it.',
                        Str::getShortClassName($relation->getInverseClass()),
                        Str::getShortClassName($relation->getOwningClass())
                    ));
                    $relation->setInverseProperty($askFieldName(
                        $relation->getInverseClass(),
                        Str::singularCamelCaseToPluralCamelCase(Str::getShortClassName($relation->getOwningClass()))
                    ));
                }

                break;
            case EntityRelation::ONE_TO_ONE:
                $relation = new EntityRelation(
                    EntityRelation::ONE_TO_ONE,
                    $generatedEntityClass,
                    $targetModelClass
                );
                $relation->setOwningProperty($newFieldName);

                $relation->setIsNullable($askIsNullable(
                    $relation->getOwningProperty(),
                    $relation->getOwningClass()
                ));

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $this->comment(sprintf(
                        'A new property will also be added to the <comment>%s</comment> class so that you can access the related <comment>%s</comment> object from it.',
                        Str::getShortClassName($relation->getInverseClass()),
                        Str::getShortClassName($relation->getOwningClass())
                    ));
                    $relation->setInverseProperty($askFieldName(
                        $relation->getInverseClass(),
                        Str::asLowerCamelCase(Str::getShortClassName($relation->getOwningClass()))
                    ));
                }

                break;
            default:
                throw new InvalidArgumentException('Invalid type: '.$type);
        }

        return $relation;
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

    /**
     * @return mixed
     */
    protected function getStub(): mixed
    {
        return null;
    }
}
