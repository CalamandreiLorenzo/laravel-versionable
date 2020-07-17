<?php
/**
 * No Namespace
 *
 * This file is part of the overtrue/laravel-versionable.
 * ------------------------------------------------------
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled.
 */

use CalamandreiLorenzo\LaravelVersionable\Helpers\VersionHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateVersionsTable
 * @author Lorenzo Calamandrei
 * @github https://github.com/CalamandreiLorenzo
 */
class CreateCascadeVersionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cascade_versions', static function (Blueprint $table) {
            $table->{config('versionable.column_type', 'unsignedBigInteger')}('id')
                ->primary();
            VersionHelper::versionAttributeToSchema($table);
            $table->json('model_relations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                [
                    'versionable_id',
                    'versionable_type',
                    'version_number'
                ],
                'unique_cascade_version_for_model'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cascade_versions');
    }
}
