<?php

namespace App\Http\Controllers;

use App\Enums\ReservationAnswerStatusEnum;
use App\Http\Requests\ReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Point;
use App\Models\PointDistance;
use App\Models\Reservation;
use App\Models\User;
use App\Utils\LineManager;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ReservationController
 * @package App\Http\Controllers
 */

class ReservationController extends Controller
{
    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        return ReservationResource::collection(
            Reservation::getList($request->input('unanswered'))
        );
    }

    /**
     * @param ReservationRequest $request
     * @return false|string
     * @throws GuzzleException
     */
    public function create(ReservationRequest $request)
    {
        $lineData = $request->getLineData();

        DB::beginTransaction();

        $user = User::createOrUpdate([
            'line_id' => $lineData->sub,
            'name' => $lineData->name,
            'picture' => empty($lineData->picture) ? '' : $lineData->picture,
        ]);

        $departure = Point::getByKey($request->departureKey);
        $arrival = Point::getByKey($request->arrivalKey);

        /** @var Reservation $reservation */
        $reservation = $user->reservations()->create([
            'name' => $user->name,
            'tel' => $request->tel,
            'departure_point_id' => $departure->id,
            'arrival_point_id' => $arrival->id,
            'passenger_numbers' => $request->passengerNumbers,
            'passengers' => $request->passengers,
            'pick_up_scheduled_time' => Carbon::now()->addMinutes($departure->pick_up_required_time),
        ]);

        $pushMessage = [
            '下記の内容で迎車依頼を行いました。迎車準備が整いましたらご連絡差し上げますので少々お待ち下さい。',
            '',
            '登録者名： ' . $reservation->name,
            '電話番号： ' . $reservation->tel,
            '出発地： ' . $departure->name,
            '目的地： ' . $arrival->name,
            'チケット枚数： ' . PointDistance::getRequiredTicket($departure, $arrival) . '枚',
        ];

        if ($reservation->passenger_numbers) {
            $pushMessage[] = '乗車人数： ' . $reservation->passenger_numbers . '人';
        }

        $pushMessage[] = '同乗者： ' . $reservation->passengers;
        $pushMessage[] = '迎車予定時刻： ' . Carbon::parse($reservation->pick_up_scheduled_time)->format('H:i') . '頃';

        LineManager::pushMessage($user->line_id, implode("\n", $pushMessage), '迎車依頼完了');
        resolve('slack')->notify(implode("\n", $pushMessage));

        DB::commit();

        return json_encode([]);
    }

    /**
     * @param Reservation $reservation
     * @param Request $request
     * @return false|string
     * @throws ValidationException
     * @throws GuzzleException
     */
    public function answer(Reservation $reservation, Request $request)
    {
        $value = validator($request->input(), [
            'status' => ['required', ReservationAnswerStatusEnum::rule()]
        ], [], [
            'status' => 'ステータス'
        ])->validated();

        if ($reservation->status) {
            throw new BadRequestHttpException('回答済みです');
        }

        DB::beginTransaction();

        $reservation->update([
            'status' => $value['status']
        ]);

        if ('dispatching' === $value['status']) {
            $title = '配車完了';
            $message = '配車が完了致しました。到着までしばらくお待ち下さい。';
            $type = 'success';
        } else {
            $title = '配車できませんでした';
            $message = '申し訳ございませんが、現在混み合っているため配車できませんでした。お手数おかけしますが時間をおいて再度迎車依頼お願い致します。';
            $type = 'error';
        }

        LineManager::pushMessage($reservation->user->line_id, $message, $title, $type);
        resolve('slack')->notify($title);

        DB::commit();

        return json_encode([]);
    }
}
