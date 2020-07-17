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

use Illuminate\Support\Str;

/**
 * Class VersionColumnPrimaryKeyType
 * @package CalamandreiLorenzo\LaravelVersionable
 * @author Lorenzo Calamandrei
 * @github https://github.com/CalamandreiLorenzo
 */
class VersionColumnPrimaryKeyType
{
    /**
     * @const string UUID
     */
    public const UUID = 'uuid';

    /**
     * @const string INT
     */
    public const INT = 'integer';

    public static function generate(string $columnType)
    {
        switch ($columnType) {
            case self::UUID:
                return Str::uuid();
            case self::INT:
            default:
                return
                    ((int) config('versionable.version_model', Version::class)::max('id'))
                    + 1;
        }
    }
}
