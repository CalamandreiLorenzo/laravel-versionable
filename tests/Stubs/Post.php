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

use CalamandreiLorenzo\LaravelVersionable\Versionable;
use CalamandreiLorenzo\LaravelVersionable\VersionStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use function auth;

/**
 * Class Post
 * @package Tests\Stubs
 * @property-read int id
 * @property-read mixed user_id
 * @property string title
 * @property string content
 * @property string extends
 * @method static Builder|Post newModelQuery()
 * @method static Builder|Post newQuery()
 * @method static Builder|Post query()
 * @method static Builder|Post whereColumns($value)
 * @method static Builder|Post whereCopyright($value)
 * @method static Builder|Post whereCreatedAt($value)
 * @method static Builder|Post whereId($value)
 * @method static Builder|Post whereUpdatedAt($value)
 * @method static Post create($values)
 */
class Post extends Model
{
    use Versionable;

    /**
     * @var string[] $fillable
     */
    protected $fillable = ['title', 'content', 'user_id', 'extends'];

    /**
     * @var string[] $versionable
     */
    protected $versionable = ['title', 'content', 'extends'];

    /**
     * @var string $versionStrategy
     */
    protected $versionStrategy = VersionStrategy::DIFF;

    /**
     * @var string[] $casts
     */
    protected $casts = [
        'extends' => 'array',
    ];

    /**
     * boot
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(static function (Post $post) {
            $post->user_id = auth()->id();
        });
    }
}
