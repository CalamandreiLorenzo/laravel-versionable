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

use CalamandreiLorenzo\LaravelVersionable\Exceptions\MissingVersionableProperty;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use function array_diff_key;
use function array_intersect_key;
use function array_keys;
use function config;
use function property_exists;
use function optional;

/**
 * Trait Versionable
 * @package CalamandreiLorenzo\LaravelVersionable
 * @author 安正超 - overtrue
 * @github https://github.com/overtrue
 * @property-read Collection|Version[] versions
 * @property-read Version lastVersion
 */
trait Versionable
{
    /**
     * @var bool $versioning
     */
    protected static bool $versioning = true;

    /**
     * @var bool $forceDeleteVersion
     */
    protected bool $forceDeleteVersion = false;

    // You can add these properties to you versionable model
    //protected $versionable = [];
    //protected $dontVersionable = ['*'];

    /**
     * bootVersionable
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public static function bootVersionable(): void
    {
        static::saved(static function (Model $model) {
            self::createVersionForModel($model);
        });

        static::deleted(static function (Model $model) {
            if (property_exists($model, 'forceDeleting') && $model->forceDeleting) {
                /**
                 * @var Versionable|Model $model
                 */
                $model->forceRemoveAllVersions();
            } else {
                self::createVersionForModel($model);
            }
        });
    }

    /**
     * createVersionForModel
     * @param Versionable|Model $model
     * @throws Exceptions\NotVersionableModel
     */
    private static function createVersionForModel(Model $model): void
    {
        if (self::$versioning && $model->shouldVersioning()) {
            Version::createForModel($model);
            $model->removeOldVersions($model->getKeepVersionsCount());
        }
    }

    /**
     * versions
     * @return MorphMany
     */
    public function versions(): MorphMany
    {
        return $this->morphMany(
            config('versionable.version_model'),
            'versionable'
        )->latest('version_number');
    }


    /**
     * lastVersion
     * @return MorphOne
     */
    public function lastVersion(): MorphOne
    {
        return $this->morphOne(
            config('versionable.version_model'),
            'versionable'
        )->latest('version_number');
    }

    /**
     * getVersion
     * @param $id
     * @return Model|null
     */
    public function getVersion($id): ?Model
    {
        return $this->versions()->find($id);
    }

    /**
     * getVersion
     * @param int $id
     * @return Model|null
     */
    public function getVersionByVersionNumber(int $id): ?Model
    {
        return $this->versions()->where('version_number', $id)->first();
    }

    /**
     * getThrashedVersions
     * @return mixed
     */
    public function getThrashedVersions()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->versions()->onlyTrashed()->get();
    }

    /**
     * restoreThrashedVersion
     * @param $id
     * @return mixed
     */
    public function restoreThrashedVersion($id)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->versions()->onlyTrashed()->whereId($id)->restore();
    }

    /**
     * revertToVersion
     * @param $id
     * @return mixed
     */
    public function revertToVersion($id)
    {
        return optional($this->versions()->findOrFail($id))->revert();
    }

    /**
     * revertToVersionNumber
     * @param int $id
     * @return mixed
     */
    public function revertToVersionNumber(int $id)
    {
        return optional(
            $this->versions()->where('version_number', $id)->firstOrFail()
        )->revert();
    }

    /**
     * removeOldVersions
     * @param int $keep
     */
    public function removeOldVersions(int $keep): void
    {
        if ($keep <= 0) {
            return;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $this->versions()->skip($keep)->take(PHP_INT_MAX)->get()->each->delete();
    }

    /**
     * removeVersions
     * @param array $ids
     * @return mixed
     */
    public function removeVersions(array $ids)
    {
        if ($this->forceDeleteVersion) {
            return $this->forceRemoveVersions($ids);
        }

        return $this->versions()->find($ids)->each->delete();
    }

    /**
     * removeVersion
     * @param $id
     * @return bool|mixed|null
     * @throws Exception
     */
    public function removeVersion($id)
    {
        if ($this->forceDeleteVersion) {
            return $this->forceRemoveVersion($id);
        }

        return optional($this->versions()->findOrFail($id))->delete();
    }

    /**
     * removeAllVersions
     */
    public function removeAllVersions(): void
    {
        if ($this->forceDeleteVersion) {
            $this->forceRemoveAllVersions();
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $this->versions->each->delete();
    }

    /**
     * forceRemoveVersion
     * @param $id
     * @return mixed
     */
    public function forceRemoveVersion($id)
    {
        return optional($this->versions()->findOrFail($id))->forceDelete();
    }

    /**
     * forceRemoveVersions
     * @param array $ids
     * @return mixed
     */
    public function forceRemoveVersions(array $ids)
    {
        return $this->versions()->find($ids)->each->forceDelete();
    }

    /**
     * forceRemoveAllVersions
     */
    public function forceRemoveAllVersions(): void
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->versions->each->forceDelete();
    }

    /**
     * shouldVersioning
     * @return bool
     */
    public function shouldVersioning(): bool
    {
        return !empty($this->getVersionableAttributes());
    }

    /**
     * getVersionableAttributes
     * @return array
     */
    public function getVersionableAttributes(): array
    {
        $changes = $this->getDirty();

        if (empty($changes)) {
            return [];
        }

        $contents = $this->attributesToArray();

        if ($this->getVersionStrategy() === VersionStrategy::DIFF) {
            $contents = $this->only(array_keys($changes));
        }

        return $this->versionableFromArray($contents);
    }

    /**
     * setVersionable
     * @param array $attributes
     * @return $this
     * @throws Exception
     */
    public function setVersionable(array $attributes): self
    {
        if (!property_exists($this, 'versionable')) {
            throw new MissingVersionableProperty('Property $versionable not exist.');
        }
        $this->versionable = $attributes;
        return $this;
    }

    /**
     * setDontVersionable
     * @param array $attributes
     * @return $this
     * @throws Exception
     */
    public function setDontVersionable(array $attributes): self
    {
        if (!property_exists($this, 'dontVersionable')) {
            throw new MissingVersionableProperty('Property $dontVersionable not exist.');
        }
        $this->dontVersionable = $attributes;
        return $this;
    }

    /**
     * getVersionable
     * @return array
     */
    public function getVersionable(): array
    {
        return property_exists($this, 'versionable') ? $this->versionable : [];
    }

    /**
     * getDontVersionable
     * @return array
     */
    public function getDontVersionable(): array
    {
        return property_exists($this, 'dontVersionable') ? $this->dontVersionable : [];
    }

    /**
     * getVersionStrategy
     * @return string
     */
    public function getVersionStrategy(): string
    {
        return property_exists($this, 'versionStrategy') ? $this->versionStrategy : VersionStrategy::DIFF;
    }

    /**
     * setVersionStrategy
     * @param string $strategy
     * @return $this
     * @throws Exception
     */
    public function setVersionStrategy(string $strategy): self
    {
        if (!property_exists($this, 'versionStrategy')) {
            throw new MissingVersionableProperty('Property $versionStrategy not exist.');
        }
        $this->versionStrategy = $strategy;
        return $this;
    }

    /**
     * getVersionModel
     * @return mixed
     */
    public function getVersionModel(): string
    {
        return config('versionable.version_model');
    }

    /**
     * getKeepVersionsCount
     * @return string
     */
    public function getKeepVersionsCount(): string
    {
        return config('versionable.keep_versions', 0);
    }

    /**
     * versionableFromArray
     * Get the versionable attributes of a given array.
     * @param array $attributes
     * @return array
     */
    public function versionableFromArray(array $attributes): array
    {
        if (count($this->getVersionable()) > 0) {
            return array_intersect_key($attributes, array_flip($this->getVersionable()));
        }

        if (count($this->getDontVersionable()) > 0) {
            return array_diff_key($attributes, array_flip($this->getDontVersionable()));
        }

        return $attributes;
    }

    /**
     * withoutVersion
     * @param callable $callback
     */
    public static function withoutVersion(callable $callback): void
    {
        self::$versioning = false;
        $callback();
        self::$versioning = true;
    }

    /**
     * enableForceDeleteVersion
     */
    public function enableForceDeleteVersion(): void
    {
        $this->forceDeleteVersion = true;
    }

    /**
     * disableForceDeleteVersion
     */
    public function disableForceDeleteVersion(): void
    {
        $this->forceDeleteVersion = false;
    }
}
