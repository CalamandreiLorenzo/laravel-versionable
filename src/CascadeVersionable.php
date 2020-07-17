<?php
/**
 * CalamandreiLorenzo\LaravelVersionable
 *
 * This file is part of the overtrue/laravel-versionable.
 * ------------------------------------------------------
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled.
 */

namespace CalamandreiLorenzo\LaravelVersionable;

use CalamandreiLorenzo\LaravelVersionable\Exceptions\MissingCascadeVersionableProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait CascadeVersionable
 * @package CalamandreiLorenzo\LaravelVersionable
 * @author Lorenzo Calamandrei
 * @github https://github.com/CalamandreiLorenzo
 */
trait CascadeVersionable
{
    /**
     * bootVersionable
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public static function bootCascadeVersionable(): void
    {
        static::saved(static function (Model $model) {
            self::createCascadeVersionForModel($model);
        });
    }

    /**
     * cascadeVersions
     * @return MorphMany
     */
    public function cascadeVersions(): MorphMany
    {
        return $this->morphMany(
            config('versionable.cascade.version_model'),
            'versionable'
        )->latest('version_number');
    }

    /**
     * createVersionForModel
     * @param CascadeVersionable|Model $model
     * @throws Exceptions\NotVersionableModel|MissingCascadeVersionableProperty
     */
    private static function createCascadeVersionForModel(Model $model): void
    {
        if (self::$versioning && $model->shouldVersioning()) {
            CascadeVersion::createForModel($model);
            $model->removeOldCascadeVersions($model->getKeepVersionsCount());
        }
    }

    /**
     * getKeepVersionsCount
     * @return string
     */
    public function getKeepCascadeVersionsCount(): string
    {
        return config('versionable.cascade.keep_versions', 0);
    }

    /**
     * getVersionModel
     * @return mixed
     */
    public function getCascadeVersionModel(): string
    {
        return config('versionable.cascade.version_model');
    }

    /**
     * getRelationsToVersion
     * @return array
     * @throws MissingCascadeVersionableProperty
     */
    public function getRelationsToVersion(): array
    {
        if (property_exists($this, 'relationsToVersion')) {
            return $this->relationsToVersion;
        }

        throw new MissingCascadeVersionableProperty('Missing "relationsToVersion" property');
    }

    /**
     * removeOldCascadeVersions
     * @param int $keep
     */
    public function removeOldCascadeVersions(int $keep): void
    {
        if ($keep <= 0) {
            return;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $this->cascadeVersions()->skip($keep)->take(PHP_INT_MAX)->get()->each->delete();
    }
}
