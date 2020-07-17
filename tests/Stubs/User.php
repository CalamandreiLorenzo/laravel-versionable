<?php
/**
 * Tests\Stubs
 *
 * This file is part of the overtrue/laravel-versionable.
 * ------------------------------------------------------
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled.
 */

namespace Tests\Stubs;

/**
 * Class User
 * @package Tests\Stubs
 * @method static Post create($values)
 */
class User extends \Illuminate\Foundation\Auth\User
{
    /**
     * @var string[] $fillable
     */
    protected $fillable = ['name'];
}
