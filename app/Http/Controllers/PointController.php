<?php

namespace App\Http\Controllers;

use App\Models\Point;
use App\Models\PointDistance;

/**
 * Class PointController
 * @package App\Http\Controllers
 */

class PointController extends Controller
{
    public function index()
    {
        $points = Point::query()
            ->with(['departure_point_distances', 'arrival_point_distances'])
            ->get()
            ->keyBy('id');

        return $points->map(function (Point $point, $id) use ($points) {
            return [
                'key' => $point->key,
                'name' => $point->name,
                'url' => $point->url,
                'pickUpRequiredTime' => $point->pick_up_required_time,
                'routeList' => array_merge(
                    $point->arrival_point_distances->map(function(PointDistance $v) use ($points) {
                        return [
                            'key' => $points[$v->departure_point_id]->key,
                            'ticket' => $v->ticket,
                        ];
                    })->toArray(),
                    $point->departure_point_distances->map(function(PointDistance $v) use ($points) {
                        return [
                            'key' => $points[$v->arrival_point_id]->key,
                            'ticket' => $v->ticket,
                        ];
                    })->toArray()
                )
            ];
        })->values();
    }
}
