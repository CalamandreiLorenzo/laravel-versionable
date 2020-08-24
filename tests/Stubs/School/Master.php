<?php
/**
 * Tests\Stubs\School
 */

namespace Tests\Stubs\School;

use CalamandreiLorenzo\LaravelVersionable\Versionable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Class Dad
 * @package Tests\Stubs\School
 * @author Lorenzo Calamandrei <calamandrei.lorenzo.work@gmail.com>
 * @github https://github.com/CalamandreiLorenzo
 *
 * @property int $id
 * @property-read Student[]|Collection $students
 * @method static Builder|Master newModelQuery()
 * @method static Builder|Master newQuery()
 * @method static Builder|Master query()
 * @method static Builder|Master whereColumns($value)
 * @method static Builder|Master whereCopyright($value)
 * @method static Builder|Master whereCreatedAt($value)
 * @method static Builder|Master whereId($value)
 * @method static Builder|Master whereUpdatedAt($value)
 * @method static Master create($values)
 */
class Master extends Model
{
    use Versionable;

    /**
     * @var string[] $fillable
     */
    protected $fillable = ['name'];

    /**
     * students
     * @return HasMany
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
