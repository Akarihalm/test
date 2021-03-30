<?php

namespace Database\Seeders;

use App\Models\Point;
use App\Models\PointDistance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class PointAddGoogiesSeeder
 * @package Database\Seeders
 */

class PointAddGoogiesSeeder extends Seeder
{
    const points = [
        [
            'key' => 'googies',
            'name' => 'グーギーズカフェ 千曲店',
            'url' => 'https://googiescafe.owst.jp/',
            'pick_up_required_time' => 15,
        ],
    ];

    const POINT_DISTANCES = [
        'googies' => [
            'togura-station' => 1,
            'ultrasonic-wave-hot-spring' => 2,
            'hakuchoen' => 2,
            'family-mart' => 3,
            'chikuma-tourist-hall' => 3,
            'starbucks' => 1,
            'uzuraya' => 5,
            'obasute-station' => 5,
            'nakaraya' => 5,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();

        Point::query()->insert($this->addTime(self::points));

        /** @var Point[] $points */
        $points = Point::all()->keyBy('key');
        $params = collect(self::POINT_DISTANCES)->flatMap(function ($arrivals, $departure) use ($points) {
            if (empty($points[$departure])) return [];
            $distance = ['departure_point_id' => $points[$departure]->id];
            return collect($arrivals)->map(function ($ticket, $arrival) use ($points, $distance) {
                if (empty($points[$arrival])) return [];
                $distance['arrival_point_id'] = $points[$arrival]->id;
                $distance['ticket'] = $ticket;
                return $distance;
            })->values()->toArray();
        })->toArray();

        PointDistance::query()->insert($this->addTime($params));

        DB::commit();
    }

    /**
     * @param $params
     * @return mixed
     */
    private function addTime($params)
    {
        $time = Carbon::now();

        foreach ($params as $key => $param) {
            $params[$key]['created_at'] = $time;
            $params[$key]['updated_at'] = $time;
        }

        return $params;
    }
}
