<?php

namespace App\Enums;

use App\Service\BaseEnum;

/**
 * Class ReservationAnswerStatusEnum
 * @package App\Enums
 *
 * @method static DISPATCHING()
 * @method static CANCEL()
 */

class ReservationAnswerStatusEnum extends BaseEnum
{
    const DISPATCHING = 'dispatching';
    const CANCEL = 'cancel';

    /**
     * @return string[]
     */
    public function display()
    {
        return [
            self::DISPATCHING => '配車済み',
            self::CANCEL => '配車キャンセル',
        ];
    }
}
