<?php

namespace App\Utils;

use Illuminate\Support\Facades\Request;

/**
 * Class RequestManager
 * @package App\Utils
 */

class RequestManager
{
    const LOCAL_IP_REGEX = [
        '^192.168',
    ];

    /**
     * ローカル判定
     *
     * @return bool
     */
    public static function isLocal()
    {
        if ('local' === config('app.env')) {
            return true;
        }

        $ip = Request::ip();

        foreach (self::LOCAL_IP_REGEX as $regexIP) {
            if (preg_match('/' . preg_quote($regexIP, '.') .'/', $ip)) {
                return true;
            }
        }

        return false;
    }
}
