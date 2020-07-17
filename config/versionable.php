<?php
/**
 * This file is part of the overtrue/laravel-versionable.
 * ------------------------------------------------------
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled.
 */

use App\User;
use CalamandreiLorenzo\LaravelVersionable\Version;
use CalamandreiLorenzo\LaravelVersionable\VersionPrimaryKey;

return [
    /**
     * Versions key type column
     * @see CreateVersionsTable
     *
     * Change this type of settings only on the startup of the package,
     * otherwise you can get error later
     */
    'key_type' => VersionPrimaryKey::UUID,

    /**
     * Keep versions, you can redefine in target model.
     * Default: 0 - Keep all versions.
     */
    'keep_versions' => 0,

    /**
     * The model class for store versions.
     * To check the main Version class check:
     * @see CalamandreiLorenzo\LaravelVersionable\Version
     */
    'version_model' => Version::class,

    /**
     * User foreign key name of versions table.
     * @see CreateVersionsTable
     *
     * Change this type of settings only on the startup of the package,
     * otherwise you can get error later
     */
    'user_foreign_key' => 'user_id',

    /**
     * User key type used to create the columns in the versions table
     * @see CreateVersionsTable
     *
     * Change this type of settings only on the startup of the package,
     * otherwise you can get error later
     */
    'user_key_type' => 'uuid',

    /**
     * The model class for user.
     */
    'user_model' => User::class,

    /**
     * The user is mandatory for the new versions?
     * In case of missing user the versions will not be created and skipped
     *
     * Change this type of settings only on the startup of the package,
     * otherwise you can get error later
     */
    'mandatory_user' => false
];
