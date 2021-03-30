<?php

namespace App\Http\Requests;

use App\Utils\LineManager;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Foundation\Http\FormRequest;
use stdClass;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ReservationRequest
 * @package App\Http\Requests
 *
 * @property string idToken
 * @property string departureKey
 * @property string arrivalKey
 * @property string tel
 * @property string passengers
 * @property string passengerNumbers
 */

class ReservationRequest extends FormRequest
{
    private $lineUserData;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws GuzzleException
     */
    public function authorize()
    {
        if (empty($this->idToken)) {
            return false;
        }

        try {
            $this->lineUserData = json_decode(LineManager::verifyIdToken($this->idToken)->getBody()->getContents());
            return true;
        } catch (ClientException $e) {
            if (400 === $e->getCode()) {
                throw new BadRequestHttpException($e->getResponse()->getBody()->getContents());
            }
            throw $e;
        }
    }

    /**
     * @return stdClass
     */
    public function getLineData()
    {
        return $this->lineUserData;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'idToken' => ['required'],
            'departureKey' => ['required', 'exists:points,key'],
            'arrivalKey' => ['required', 'exists:points,key'],
            'tel' => ['required', 'regex:/^[0-9]{2,4}-?[0-9]{2,4}-?[0-9]{3,4}$/'],
            'passengerNumbers' => ['nullable', 'numeric'],
            'passengers' => ['nullable'],
        ];
    }

    public function attributes()
    {
        return [
            'idToken' => 'IDトークン',
            'departureKey' => '出発地点',
            'arrivalKey' => '到着地点',
            'tel' => '電話番号',
            'passengerNumbers' => '乗車人数',
            'passengers' => '相乗り者',
        ];
    }
}
