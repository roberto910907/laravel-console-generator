<?php
/**
 * @author Roberto Rielo <roberto910907@gmail.com>.
 *
 * @version laravel-console-generator v0.1
 */
declare(strict_types=1);

namespace ConsoleGenerator\Eloquent;

use Doctrine\DBAL\Types\Type;
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

    /**
     * @return array
     */
    public static function getAllValidTypes(): array
    {
        return array_merge(array_keys(self::getValidColumnTypes()), self::getValidRelationTypes());
    }

    /**
     * @return array
     */
    public static function getValidColumnTypes(): array
    {
        return Type::getTypesMap();
    }

    /**
     * @return array
    */
    public static function getTypesTable(): array
    {
        return [
            'main' => [
                'string' => [],
                'text' => [],
                'boolean' => [],
                'integer' => ['smallint', 'bigint'],
                'float' => [],
            ],
            'relation' => collect(self::getValidRelationTypes())
                ->mapWithKeys(function ($relation) {
                    return [$relation => []];
                })
                ->toArray(),
            'array_object' => [
                'array' => ['simple_array'],
                'json' => [],
                'object' => [],
                'binary' => [],
                'blob' => [],
            ],
            'date_time' => [
                'datetime' => ['datetime_immutable'],
                'datetimetz' => ['datetimetz_immutable'],
                'date' => ['date_immutable'],
                'time' => ['time_immutable'],
                'dateinterval' => [],
            ],
        ];
    }

    /**
     * Listing valid Eloquent relationships
     *
     * @see https://laravel.com/docs/9.x/eloquent-relationships
     *
     * @return array
     */
    public static function getValidRelationTypes(): array
    {
        return [
            'OneToOne',
            'OneToMany',
            'ManyToMany',
            'HasOneThrough',
            'HasManyThrough',
            'OneToOne (Polymorphic)',
            'OneToMany (Polymorphic)',
            'ManyToMany (Polymorphic)',
        ];
    }
}
