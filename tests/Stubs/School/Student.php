<?php
/**
 * Tests\Stubs\School
 */

namespace Tests\Stubs\School;

use CalamandreiLorenzo\LaravelVersionable\CascadeVersionable;
use CalamandreiLorenzo\LaravelVersionable\Versionable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Son
 * @package Tests\Stubs\School
 * @author Lorenzo Calamandrei <calamandrei.lorenzo.work@gmail.com>
 * @github https://github.com/CalamandreiLorenzo
 *
 * @property int $id
 * @method static Builder|Student newModelQuery()
 * @method static Builder|Student newQuery()
 * @method static Builder|Student query()
 * @method static Builder|Student whereColumns($value)
 * @method static Builder|Student whereCopyright($value)
 * @method static Builder|Student whereCreatedAt($value)
 * @method static Builder|Student whereId($value)
 * @method static Builder|Student whereUpdatedAt($value)
 * @method static Student create($values)
 */
class Student extends Model
{
    use Versionable;
    use CascadeVersionable;

    /**
     * @var string[] $fillable
     */
    protected $fillable = ['name', 'master_id'];

    /**
     * @var string[] $cascadeVersions
     */
    protected $relationsToVersion = ['master'];

    /**
     * master
     * @return BelongsTo
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }
}
