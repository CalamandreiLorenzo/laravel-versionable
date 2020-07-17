<?php
/**
 * No Namespace
 *
 * This file is part of the overtrue/laravel-versionable.
 * ------------------------------------------------------
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled.
 */

use CalamandreiLorenzo\LaravelVersionable\Version;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddDeletedAtToVersions
 * @author Lorenzo Calamandrei
 * @github https://github.com/CalamandreiLorenzo
 */
class AddOptionalUserToVersions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('versions', static function (Blueprint $table) {
            $table->{config('versionable.user_key_type', 'unsignedBigInteger')}(
                config('versionable.user_foreign_key', 'user_id')
            )->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function down(): void
    {
        // Delete all versions that doesn't have a user, because is mandatory before this transaction
        Version::whereDoesntHave(
            config('versionable.user_foreign_key', 'user_id')
        )->delete();

        Schema::table('versions', static function (Blueprint $table) {
            $table->{config('versionable.user_key_type', 'unsignedBigInteger')}(
                config('versionable.user_foreign_key', 'user_id')
            )->change();
        });
    }
}
