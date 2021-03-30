<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Point
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $url
 * @property int $pick_up_required_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at

 * @property Collection|PointDistance[] $departure_point_distances
 * @property Collection|PointDistance[] $arrival_point_distances
 * @property Collection|Reservation[] $reservations
 *
 * @package App\Models
 */
class Point extends Model
{
    use SoftDeletes;

    protected $table = 'points';

    protected $casts = [
        'pick_up_required_time' => 'int',
    ];

    protected $dates = [
    ];

    protected $fillable = [
        'key',
        'name',
        'url',
        'pick_up_required_time',
    ];

    public function departure_point_distances()
    {
        return $this->hasMany(PointDistance::class, 'departure_point_id', 'id');
    }

    public function arrival_point_distances()
    {
        return $this->hasMany(PointDistance::class, 'arrival_point_id', 'id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @param $key
     * @return Builder|Model|object|null|self
     */
    public static function getByKey($key)
    {
        return self::query()->where('key', $key)->first();
    }
}
