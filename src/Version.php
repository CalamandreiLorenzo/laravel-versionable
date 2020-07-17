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

use CalamandreiLorenzo\LaravelVersionable\Exceptions\NotVersionableModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SebastianBergmann\Diff\Differ;
use function array_keys;
use function auth;
use function class_uses;
use function config;
use function in_array;

/**
 * Class Version.
 *
 * @property $id
 * @property Model $versionable
 * @property array $contents
 * @property int $version_number
 * @property $versionable_id
 * @property $versionable_type
 */
class Version extends Model
{
    use SoftDeletes;

    /**
     * @var string[] $casts
     */
    protected $casts = [
        'contents' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        if (
            config('versionable.key_type', VersionPrimaryKey::UUID)
            === VersionPrimaryKey::UUID
        ) {
            $this->keyType = "string";
        }
        parent::__construct($attributes);
    }

    /**
     * boot
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (Version $version) {
            if (
                config('versionable.key_type', VersionPrimaryKey::UUID)
                    === VersionPrimaryKey::UUID
            ) {
                $version->id = Str::uuid();
            }
        });
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('versionable.user_model'),
            config('versionable.user_foreign_key')
        );
    }

    /**
     * @return MorphTo
     */
    public function versionable(): MorphTo
    {
        return $this->morphTo('versionable');
    }

    /**
     * createForModel
     * @param Versionable|Model $model
     * @return Version
     * @throws NotVersionableModel
     */
    public static function createForModel(Model $model): Version
    {
        if (!in_array(Versionable::class, class_uses($model), true)) {
            throw new NotVersionableModel(
                "{$model} not use Versionable " . config('versionable.version_model')
            );
        }
        $versionClass = $model->getVersionModel();
        /** @var Version $version */
        $version = new $versionClass();
        $version->version_number = ((int) $versionClass::where('versionable_id', $model->getKey())
            ->where('versionable_type', $model->getMorphClass())
            ->max('version_number')) + 1;
        $version->versionable_id = $model->getKey();
        $version->versionable_type = $model->getMorphClass();
        $version->{config('versionable.user_foreign_key')} = auth()->id();
        $version->contents = $model->getVersionableAttributes();

        $version->save();
        return $version;
    }

    /**
     * @return bool
     */
    public function revert(): bool
    {
        return $this->versionable->fill($this->contents)->save();
    }

    /**
     * diff
     * @param Versionable|Model|null $model
     *
     * @return string
     * @throws NotVersionableModel
     */
    public function diff(Model $model = null): string
    {
        $model || $model = $this->versionable;

        if ($model instanceof self) {
            $source = $model->contents;
        } else {
            if (!in_array(Versionable::class, class_uses($model), true)) {
                throw new NotVersionableModel("{$model} is not versionable");
            }

            $source = $model->versionableFromArray($this->versionable->toArray());
        }

        return (new Differ())->diff(Arr::only($source, array_keys($this->contents)), $this->contents);
    }
}
