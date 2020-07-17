<?php
/**
 * Tests
 *
 * This file is part of the overtrue/laravel-versionable.
 * ------------------------------------------------------
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled.
 */
namespace Tests;

use CalamandreiLorenzo\LaravelVersionable\Exceptions\NotVersionableModel;
use CalamandreiLorenzo\LaravelVersionable\VersionStrategy;
use Exception;
use Tests\Stubs\Post;
use Tests\Stubs\User;
use function config;

/**
 * Class FeatureTest
 * @package Tests
 * @author 安正超 - overtrue
 * @github https://github.com/overtrue
 */
class FeatureTest extends TestCase
{
    /**
     * @var User $user
     */
    protected $user;

    /**
     * setUp
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.providers.users.model' => User::class]);

        /** @var User user */
        $this->user = User::create(['name' => 'overtrue']);
        $this->actingAs($this->user);
    }

    /**
     * post_has_versions
     * @test
     */
    public function post_has_versions(): void
    {
        $post = Post::create(['title' => 'version1', 'content' => 'version1 content']);

        self::assertCount(1, $post->versions);
        self::assertSame($post->only('title', 'content'), $post->lastVersion->contents);

        // version2
        $post->update(['title' => 'version2']);
        $post->refresh();

        self::assertCount(2, $post->versions);
        self::assertSame($post->only('title'), $post->lastVersion->contents);
    }

    /**
     * post_create_version_with_strategy
     * @test
     * @throws Exception
     */
    public function post_create_version_with_strategy(): void
    {
        $post = Post::create(['title' => 'version1', 'content' => 'version1 content']);

        self::assertCount(1, $post->versions);
        self::assertSame($post->only('title', 'content'), $post->lastVersion->contents);

        $post->setVersionStrategy(VersionStrategy::SNAPSHOT);

        // version2
        $post->update(['title' => 'version2']);
        $post = $post->fresh();

        self::assertCount(2, $post->versions);
        self::assertSame($post->only('title', 'content'), $post->lastVersion->contents);
        self::assertSame('version1 content', $post->lastVersion->contents['content']);
    }

    /**
     * post_can_revert_to_target_version
     * @test
     */
    public function post_can_revert_to_target_version(): void
    {
        $post = Post::create(['title' => 'version1', 'content' => 'version1 content']);
        $post->update(['title' => 'version2', 'extends' => ['foo' => 'bar']]);
        $post->update(['title' => 'version3', 'content' => 'version3 content', 'extends' => ['name' => 'overtrue']]);
        $post->update(['title' => 'version4', 'content' => 'version4 content']);

        // revert version 2
        $post->revertToVersionNumber(2);
        $post = $post->fresh();

        // only title updated
        self::assertSame('version2', $post->title);
        self::assertSame('version4 content', $post->content);
        self::assertSame(['foo' => 'bar'], $post->extends);

        // revert version 3
        $post->revertToVersionNumber(3);
        $post = $post->fresh();

        // title and content are updated
        self::assertSame('version3', $post->title);
        self::assertSame('version3 content', $post->content);
    }

    /**
     * user_can_get_diff_of_version
     * @test
     * @throws NotVersionableModel
     */
    public function user_can_get_diff_of_version(): void
    {
        $post = Post::create(['title' => 'version1', 'content' => 'version1 content']);

        self::assertSame("--- Original\n+++ New\n", $post->lastVersion->diff($post));

        $post->update(['title' => 'version2']);
        $post = $post->fresh();

        self::assertSame(
            "--- Original\n+++ New\n@@ @@\n-version1\n+version2\n",
            $post->lastVersion->diff($post->getVersionByVersionNumber(1))
        );
    }

    /**
     * post_will_keep_versions
     * @test
     */
    public function post_will_keep_versions(): void
    {
        config(['versionable.keep_versions' => 3]);

        $post = Post::create(['title' => 'version1', 'content' => 'version1 content']);
        $post->update(['title' => 'version2']);
        $post->update(['title' => 'version3', 'content' => 'version3 content']);
        $post->update(['title' => 'version4', 'content' => 'version4 content']);
        $post->update(['title' => 'version5', 'content' => 'version5 content']);

        self::assertCount(3, $post->versions);

        $post->removeAllVersions();
        $post = $post->fresh();

        self::assertCount(0, $post->versions);
    }


    /**
     * user_can_disable_version_control
     * @test
     */
    public function user_can_disable_version_control(): void
    {
        $post = null;
        Post::withoutVersion(static function () use (&$post) {
            $post = Post::create(['title' => 'version1', 'content' => 'version1 content']);
        });

        self::assertCount(0, $post->versions);

        // version2
        $post = Post::create(['title' => 'version1', 'content' => 'version1 content']);
        $post = $post->fresh();
        self::assertCount(1, $post->versions);

        Post::withoutVersion(static function () use ($post) {
            $post->update(['title' => 'version2']);
        });
        $post = $post->fresh();

        self::assertCount(1, $post->versions);
        self::assertSame(['title' => 'version1', 'content' => 'version1 content'], $post->lastVersion->contents);
    }

    /**
     * post_version_soft_delete_and_restore
     * @test
     * @throws Exception
     */
    public function post_version_soft_delete_and_restore(): void
    {
        $post = Post::create(['title' => 'version1', 'content' => 'version1 content']);

        self::assertCount(1, $post->versions);
        self::assertSame($post->only('title', 'content'), $post->lastVersion->contents);
        $this->assertDatabaseCount('versions', 1);

        // version2
        $post->update(['title' => 'version2']);
        $post = $post->fresh();

        self::assertCount(2, $post->versions);
        self::assertSame($post->only('title'), $post->lastVersion->contents);
        $this->assertDatabaseCount('versions', 2);

        // version3
        $post->update(['title' => 'version3']);
        $post = $post->fresh();
        $this->assertDatabaseCount('versions', 3);


        // soft delete
        $post = $post->fresh();
        // first
        $lastVersion = $post->lastVersion;
        $post->removeVersion($lastVersion->id);
        $this->assertDatabaseCount('versions', 3);
        self::assertCount(1, $post->getThrashedVersions());

        // second delete
        $post = $post->fresh();
        $lastVersion = $post->lastVersion;
        $post->removeVersion($lastVersion->id);
        $this->assertDatabaseCount('versions', 3);
        self::assertCount(1, $post->fresh()->versions);
        self::assertCount(2, $post->getThrashedVersions());

        // restore second deleted version
        $post->restoreThrashedVersion($lastVersion->id);
        self::assertCount(2, $post->fresh()->versions);
    }

    /**
     * post_version_forced_delete
     * @test
     * @throws Exception
     */
    public function post_version_forced_delete(): void
    {
        $post = Post::create(['title' => 'version1', 'content' => 'version1 content']);

        self::assertCount(1, $post->versions);
        self::assertSame($post->only('title', 'content'), $post->lastVersion->contents);
        $this->assertDatabaseCount('versions', 1);

        // version2
        $post->update(['title' => 'version2']);
        $post = $post->fresh();

        self::assertCount(2, $post->versions);
        self::assertSame($post->only('title'), $post->lastVersion->contents);
        $this->assertDatabaseCount('versions', 2);

        // version3
        $post->update(['title' => 'version3']);
        $post = $post->fresh();
        $this->assertDatabaseCount('versions', 3);

        // first
        $post = $post->fresh();
        $post->enableForceDeleteVersion();
        $lastVersion = $post->lastVersion;
        $post->removeVersion($lastVersion->id);
        $this->assertDatabaseCount('versions', 2);

        // second delete
        $post = $post->fresh();
        $post->enableForceDeleteVersion();
        $lastVersion = $post->lastVersion;
        $post->removeVersion($lastVersion->id);
        $this->assertDatabaseCount('versions', 1);
    }
}
