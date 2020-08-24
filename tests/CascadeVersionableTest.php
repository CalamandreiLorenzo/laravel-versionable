<?php
/**
 * Tests
 */

namespace Tests;

use Tests\Stubs\School\Master;
use Tests\Stubs\School\Student;
use Tests\Stubs\User;
use function config;

/**
 * Class CascadeVersionableTest
 * @package Tests
 * @author Lorenzo Calamandrei <calamandrei.lorenzo.work@gmail.com>
 * @github https://github.com/CalamndreiLorenzo
 */
class CascadeVersionableTest extends TestCase
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
     * post_tag_should_be_versionable
     * @test
     */
    public function school_members_should_be_versionable(): void
    {
        $master = Master::create(['name' => 'Margaret']);
        self::assertCount(1, $master->versions()->get());

        /** @var Student $student */
        $student = $master->students()->create([
            'name' => 'Samantha'
        ]);
        self::assertInstanceOf(Student::class, $student);
        self::assertCount(1, $student->versions()->get());
        self::assertCount(1, $student->cascadeVersions()->get());

        // master should has 1 student
        self::assertCount(1, $master->students()->get());

        // student should has also the master record in her version
    }
}
