<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PointDistance
 *
 * @property int $id
 * @property int $departure_point_id
 * @property int $arrival_point_id
 * @property int $ticket
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at

 * @property Point $point
 *
 * @package App\Models
 */
class PointDistance extends Model
{
    use SoftDeletes;

    protected $table = 'point_distances';

    protected $casts = [
        'departure_point_id' => 'int',
        'arrival_point_id' => 'int',
        'ticket' => 'int',
    ];

    protected $dates = [
    ];

    protected $fillable = [
        'departure_point_id',
        'arrival_point_id',
        'ticket',
    ];

    public function point()
    {
        return $this->belongsTo(Point::class);
    }

    /**
     * @param Point $departure
     * @param Point $arrival
     * @return int|null
     */
    public static function getRequiredTicket(Point $departure, Point $arrival)
    {
        /** @var PointDistance $pointDistance */
        $pointDistance = self::query()->where(function (Builder $query) use ($departure, $arrival) {
            $query
                ->where('departure_point_id', $departure->id)
                ->where('arrival_point_id', $arrival->id);
        })->orWhere(function (Builder $query) use ($departure, $arrival) {
            $query
                ->where('departure_point_id', $arrival->id)
                ->where('arrival_point_id', $departure->id);
        })->first();

        if ($pointDistance) {
            return $pointDistance->ticket;
        }

        return null;
    }
}
