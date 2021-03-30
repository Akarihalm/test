<?php

namespace Database\Seeders;

use App\Models\Point;
use App\Models\PointDistance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class PointSeeder
 * @package Database\Seeders
 */

class PointSeeder extends Seeder
{
    const points = [
        [
            'key' => 'togura-station',
            'name' => '戸倉駅',
            'url' => 'https://www.shinanorailway.co.jp/area/togura.php',
            'pick_up_required_time' => 15,
        ],
        [
            'key' => 'ultrasonic-wave-hot-spring',
            'name' => '万葉超音波温泉',
            'url' => 'http://www.manyoonsen.com',
            'pick_up_required_time' => 15,
        ],
        [
            'key' => 'hakuchoen',
            'name' => '白鳥園',
            'url' => 'http://www.hakuchoen.jp',
            'pick_up_required_time' => 15,
        ],
        [
            'key' => 'family-mart',
            'name' => 'ファミリーマート信州上山田温泉店',
            'url' => 'https://as.chizumaru.com/famima/detailMap?account=famima&bid=27354&adr=20',
            'pick_up_required_time' => 15,
        ],
        [
            'key' => 'chikuma-tourist-hall',
            'name' => '千曲市総合観光会館',
            'url' => 'https://chikuma-kanko.com',
            'pick_up_required_time' => 15,
        ],
        [
            'key' => 'starbucks',
            'name' => 'スターバックスコーヒー千曲店',
            'url' => 'https://store.starbucks.co.jp/detail-1586/',
            'pick_up_required_time' => 30,
        ],
        [
            'key' => 'uzuraya',
            'name' => 'ホテルうづらや（武水別神社前）',
            'url' => 'https://uzuraya.net/',
            'pick_up_required_time' => 30,
        ],
        [
            'key' => 'obasute-station',
            'name' => '姨捨駅',
            'url' => 'https://ja.wikipedia.org/wiki/%E5%A7%A8%E6%8D%A8%E9%A7%85',
            'pick_up_required_time' => 30,
        ],
        [
            'key' => 'nakaraya',
            'name' => '姨捨ゲストハウスなからや',
            'url' => 'https://www.facebook.com/obasute.nakaraya/',
            'pick_up_required_time' => 30,
        ],
    ];

    const POINT_DISTANCES = [
        'togura-station' => [
            'ultrasonic-wave-hot-spring' => 1,
            'hakuchoen' => 1,
            'family-mart' => 2,
            'chikuma-tourist-hall' => 2,
            'starbucks' => 3,
            'uzuraya' => 7,
            'obasute-station' => 7,
            'nakaraya' => 7,
        ],
        'ultrasonic-wave-hot-spring' => [
            'hakuchoen' => 1,
            'family-mart' => 1,
            'chikuma-tourist-hall' => 1,
            'starbucks' => 2,
            'uzuraya' => 6,
            'obasute-station' => 6,
            'nakaraya' => 6,
        ],
        'hakuchoen' => [
            'family-mart' => 1,
            'chikuma-tourist-hall' => 1,
            'starbucks' => 2,
            'uzuraya' => 6,
            'obasute-station' => 6,
            'nakaraya' => 6,
        ],
        'family-mart' => [
            'chikuma-tourist-hall' => 1,
            'starbucks' => 3,
            'uzuraya' => 7,
            'obasute-station' => 5,
            'nakaraya' => 5,
        ],
        'chikuma-tourist-hall' => [
            'starbucks' => 3,
            'uzuraya' => 7,
            'obasute-station' => 5,
            'nakaraya' => 5,
        ],
        'starbucks' => [
            'uzuraya' => 4,
            'obasute-station' => 4,
            'nakaraya' => 4,
        ],
        'uzuraya' => [
            'obasute-station' => 4,
            'nakaraya' => 4,
        ],
        'obasute-station' => [
            'nakaraya' => 1,
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
