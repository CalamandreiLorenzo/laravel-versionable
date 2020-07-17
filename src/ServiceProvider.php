<?php
/**
 * CalamandreiLorenzo\LaravelVersionable
 * This file is part of the overtrue/laravel-versionable.
 * ------------------------------------------------------
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled.
 */
namespace CalamandreiLorenzo\LaravelVersionable;

use function config_path;
use function database_path;

/**
 * Class ServiceProvider.
 * @author 安正超 - overtrue
 * @github https://github.com/overtrue
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * boot
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        $this->publishes([
            __DIR__ . '/../migrations' => database_path('migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/../config/versionable.php' => config_path('versionable.php'),
        ], 'config');
    }

    /**
     * register
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/versionable.php',
            'versionable'
        );
    }
}
