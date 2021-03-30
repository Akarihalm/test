<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Reservation
 *
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string $name
 * @property string $tel
 * @property int $departure_point_id
 * @property int $arrival_point_id
 * @property string|null $picture
 * @property int|null $passenger_numbers
 * @property string|null $passengers
 * @property Carbon $pick_up_scheduled_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at

 * @property User $user
 * @property Point $departurePoint
 * @property Point $arrivalPoint
 *
 * @package App\Models
 */
class Reservation extends Model
{
    use SoftDeletes;

    protected $table = 'reservations';

    protected $casts = [
        'user_id' => 'int',
        'departure_point_id' => 'int',
        'arrival_point_id' => 'int',
        'passenger_numbers' => 'int',
        'pick_up_scheduled_time' => 'datetime',
    ];

    protected $dates = [
        'pick_up_scheduled_time',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'name',
        'tel',
        'departure_point_id',
        'arrival_point_id',
        'picture',
        'passenger_numbers',
        'passengers',
        'pick_up_scheduled_time',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function departurePoint()
    {
        return $this->belongsTo(Point::class, 'departure_point_id', 'id', 'departurePoint');
    }

    public function arrivalPoint()
    {
        return $this->belongsTo(Point::class, 'arrival_point_id', 'id', 'arrivalPoint');
    }

    /**
     * @param false $unanswered
     * @return Builder[]|Collection
     */
    public static function getList($unanswered = false)
    {
        $query = self::query()->with(['departurePoint', 'arrivalPoint']);
        if ($unanswered) {
            $query->whereNull('status');
        }

        return $query->get();
    }
}
