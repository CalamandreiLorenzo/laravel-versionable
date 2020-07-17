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
use CalamandreiLorenzo\LaravelVersionable\Helpers\VersionHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use function class_uses;
use function collect;
use function config;
use function in_array;

/**
 * Class CascadeVersion.
 *
 * @property $id
 * @property Model $versionable
 * @property array $model_relations
 * @property int $version_number
 * @property $versionable_id
 * @property $versionable_type
 */
class CascadeVersion extends Model
{
    use SoftDeletes;

    /**
     * @var string[] $casts
     */
    protected $casts = [
        'model_relations' => 'array',
    ];

    /**
     * Version constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->keyType = config('versionable.key_type', 'string');
        parent::__construct($attributes);
    }

    /**
     * boot
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (CascadeVersion $version) {
            $version->id = VersionColumnPrimaryKeyType::generate(
                config('versionable.column_type')
            );
        });
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('versionable.user.model'),
            config('versionable.user.foreign_key')
        );
    }

    /**
     * versionable
     * @return MorphTo
     */
    public function versionable(): MorphTo
    {
        return $this->morphTo('versionable');
    }

    /**
     * createForModel
     * @param CascadeVersionable|Model $model
     * @return CascadeVersion
     * @throws NotVersionableModel
     * @throws Exceptions\MissingCascadeVersionableProperty
     */
    public static function createForModel(Model $model): CascadeVersion
    {
        if (!in_array(CascadeVersionable::class, class_uses($model), true)) {
            throw new NotVersionableModel(
                "{$model} not use CascadeVersionable " . config('versionable.cascade_version_model')
            );
        }
        $versionClass = $model->getCascadeVersionModel();
        /** @var CascadeVersion $version */
        $version = new $versionClass();
        VersionHelper::generateVersionAttribute($version, $versionClass, $model);

        // cycle relations and add it
        $version->model_relations = collect($model->load($model->getRelationsToVersion())->getRelations())
            ->only($model->getRelationsToVersion())
            ->map(static function (Model $model) {
                // If the sub model implement the Versionable trait, store also the version in this moment
                // otherwise only the identifier
                $additionalVersion = [];
                if (in_array(Versionable::class, class_uses($model), true)) {
                    /** @var Versionable|Model $model */
                    $additionalVersion['version_id'] = $model->lastVersion()->firstOrFail()->getKey();
                }
                return [
                    'morph_class' => $model->getMorphClass(),
                    'id' => $model->getKey(),
                ] + $additionalVersion;
            })->toArray();

        $version->save();
        return $version;
    }
}
