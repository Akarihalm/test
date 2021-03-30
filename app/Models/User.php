<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class User
 *
 * @property int $id
 * @property string $line_id
 * @property string $name
 * @property string|null $picture
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at

 * @property Collection|Reservation[] $reservations
 *
 * @package App\Models
 */
class User extends Model
{
    use SoftDeletes;

    protected $table = 'users';

    protected $casts = [
    ];

    protected $dates = [
    ];

    protected $fillable = [
        'line_id',
        'name',
        'picture',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @param $lineId
     * @return Builder|Model|object|null
     */
    public static function getByLineId($lineId)
    {
        return self::query()->where('line_id', $lineId)->first();
    }

    /**
     * @param $params
     * @return User|Builder|Model
     */
    public static function createOrUpdate($params)
    {
        /** @var self $user */
        $user = self::getByLineId($params['line_id']);
        if ($user) {
            $user->update($params);
            return $user;
        }

        return self::query()->create($params);
    }
}
