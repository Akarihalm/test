<?php

namespace App\Utils;

/**
 * Class TimeMemory
 * @package App\Utils
 */

class TimeMemory
{
    const COUNT_DISPLAY_BY = 1000;

    private static $startTime;
    private static $count = [];

    /**
     * @param string $key
     */
    public static function setup($key = 'default')
    {
        self::$count[$key] = 0;
        self::$startTime[$key] = microtime(true);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function getCount($key = 'default')
    {
        return self::$count[$key];
    }

    /**
     * @param string $key
     * @param int $count_by
     */
    public static function countUp($key = 'default', $count_by = self::COUNT_DISPLAY_BY)
    {
        ++self::$count[$key];
        if (0 === self::$count[$key] % $count_by) {
            self::countDisplay($key);
        }
    }

    /**
     * @param string $key
     * @param string $text
     * @param bool $displayCount
     */
    public static function countDisplay($key = 'default', $text = '', $displayCount = true)
    {
        $value = $displayCount ? number_format(self::$count[$key]) . ' : ' : '';
        $value .= $text ? $text . ' ' : '';
        dump($value . self::passedTime($key));
    }

    /**
     * @param string $key
     * @return string
     */
    private static function passedTime($key = 'default')
    {
        return number_format((microtime(true) - self::$startTime[$key]) * 1000) . ' ms' . ' - ' . number_format(self::getMemory()) . ' KB';
    }

    /**
     * @return string
     */
    private static function getMemory()
    {
        return round(memory_get_usage(true) / 1000);
    }
}
