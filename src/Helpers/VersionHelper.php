<?php
/**
 * CalamandreiLorenzo\LaravelVersionable\Helpers
 */

namespace CalamandreiLorenzo\LaravelVersionable\Helpers;

use CalamandreiLorenzo\LaravelVersionable\CascadeVersion;
use CalamandreiLorenzo\LaravelVersionable\Version;
use CalamandreiLorenzo\LaravelVersionable\Versionable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class VersionHelper
 * @package CalamandreiLorenzo\LaravelVersionable\Helpers
 * @author Lorenzo Calamandrei
 * @github https://github.com/CalamandreiLorenzo
 */
class VersionHelper
{
    /**
     * versionAttributeToSchema
     * @param Blueprint $table
     */
    public static function versionAttributeToSchema(Blueprint $table): void
    {
        $table->unsignedBigInteger('version_number');
        $table->{config('versionable.user.key_type', 'unsignedBigInteger')}(
            config('versionable.user.foreign_key', 'user_id')
        );
        $table->string('versionable_id');
        $table->string('versionable_type');
    }

    /**
     * @param CascadeVersion|Version $version
     * @param mixed $versionClass
     * @param Versionable|Model $model
     */
    public static function generateVersionAttribute($version, $versionClass, $model): void
    {
        $version->version_number = ((int) $versionClass::where('versionable_id', $model->getKey())
                ->where('versionable_type', $model->getMorphClass())
                ->max('version_number')) + 1;
        $version->versionable_id = $model->getKey();
        $version->versionable_type = $model->getMorphClass();
        $version->{config('versionable.user.foreign_key')} = auth()->id();
    }
}
