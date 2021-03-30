<?php

namespace App\Http\Resources;

use App\Enums\ReservationAnswerStatusEnum;
use App\Models\Point;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ReservationResource
 * @package App\Http\Resources
 *
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string $name
 * @property string $tel
 * @property int $departure_point_id
 * @property int $arrival_point_id
 * @property int $passenger_numbers
 * @property string|null $picture
 * @property Carbon $pick_up_scheduled_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at

 * @property User $user
 * @property Point $departurePoint
 * @property Point $arrivalPoint
 */

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'departureKey' => $this->departurePoint->key,
            'arrivalKey' => $this->arrivalPoint->key,
            'reservationTime' => $this->created_at->format('H:i'),
            'pickUpTime' => $this->pick_up_scheduled_time->format('H:i'),
            'passengerNumbers' => $this->passenger_numbers,
            'tel' => $this->tel,
            'status' => $this->status ? ReservationAnswerStatusEnum::get($this->status)->value() : null,
        ];
    }
}
