<?php

namespace App\Utils;

/**
 * Class TableConfigManager
 * @package App\Utils
 */

class TableConfigManager
{
    const TYPE_BOOL = ['function' => 'boolean', 'digit' => true, 'decimal' => false];
    const TYPE_TINYINT = ['function' => 'tinyInteger', 'digit' => true, 'decimal' => false];
    const TYPE_SMALLINT = ['function' => 'smallInteger', 'digit' => true, 'decimal' => false];
    const TYPE_MEDIUMINT = ['function' => 'mediumInteger', 'digit' => true, 'decimal' => false];
    const TYPE_INT = ['function' => 'Integer', 'digit' => true, 'decimal' => false];
    const TYPE_BIGINT = ['function' => 'bigInteger', 'digit' => true, 'decimal' => false];
    const TYPE_UNSIGNED_TINYINT = ['function' => 'unsignedTinyInteger', 'digit' => true, 'decimal' => false];
    const TYPE_UNSIGNED_SMALLINT = ['function' => 'unsignedSmallInteger', 'digit' => true, 'decimal' => false];
    const TYPE_UNSIGNED_MEDIUMINT = ['function' => 'unsignedMediumInteger', 'digit' => true, 'decimal' => false];
    const TYPE_UNSIGNED_INT = ['function' => 'unsignedInteger', 'digit' => true, 'decimal' => false];
    const TYPE_UNSIGNED_BIGINT = ['function' => 'unsignedBigInteger', 'digit' => true, 'decimal' => false];
    const TYPE_FLOAT = ['function' => 'float', 'digit' => true, 'decimal' => true];
    const TYPE_DOUBLE = ['function' => 'double', 'digit' => true, 'decimal' => true];
    const TYPE_DECIMAL = ['function' => 'decimal', 'digit' => true, 'decimal' => true];
    const TYPE_UNSIGNED_FLOAT = ['function' => 'unsignedFloat', 'digit' => true, 'decimal' => true];
    const TYPE_UNSIGNED_DOUBLE = ['function' => 'unsignedDouble', 'digit' => true, 'decimal' => true];
    const TYPE_UNSIGNED_DECIMAL = ['function' => 'unsignedDecimal', 'digit' => true, 'decimal' => true];
    const TYPE_DATE = ['function' => 'date', 'digit' => false, 'decimal' => false];
    const TYPE_DATETIME = ['function' => 'datetime', 'digit' => false, 'decimal' => false];
    const TYPE_TIMESTAMP = ['function' => 'timestamp', 'digit' => false, 'decimal' => false];
    const TYPE_TIME = ['function' => 'time', 'digit' => false, 'decimal' => false];
    const TYPE_YEAR = ['function' => 'year', 'digit' => false, 'decimal' => false];
    const TYPE_VARCHAR = ['function' => 'string', 'digit' => true, 'decimal' => false];
    const TYPE_TEXT = ['function' => 'text', 'digit' => true, 'decimal' => false];
    const TYPE_ENUM = ['function' => 'enum', 'digit' => false, 'decimal' => false];
    const TYPE_SET = ['function' => 'set', 'digit' => false, 'decimal' => false];
    const TYPE_JSON = ['function' => 'json', 'digit' => false, 'decimal' => false];

    const TYPE_GROUP_INT = [
        self::TYPE_BOOL,
        self::TYPE_TINYINT,
        self::TYPE_SMALLINT,
        self::TYPE_MEDIUMINT,
        self::TYPE_INT,
        self::TYPE_BIGINT,
        self::TYPE_UNSIGNED_TINYINT,
        self::TYPE_UNSIGNED_SMALLINT,
        self::TYPE_UNSIGNED_MEDIUMINT,
        self::TYPE_UNSIGNED_INT,
        self::TYPE_UNSIGNED_BIGINT,
    ];

    const TYPE_GROUP_FLOAT = [
        self::TYPE_FLOAT,
        self::TYPE_DOUBLE,
        self::TYPE_DECIMAL,
        self::TYPE_UNSIGNED_FLOAT,
        self::TYPE_UNSIGNED_DOUBLE,
        self::TYPE_UNSIGNED_DECIMAL,
    ];

    const TYPE_GROUP_DATE = [
        self::TYPE_DATE,
        self::TYPE_DATETIME,
        self::TYPE_TIMESTAMP,
    ];

    const TYPE_GROUP_ARRAY = [
        self::TYPE_SET,
        self::TYPE_JSON,
    ];

    /**
     * @param $type
     * @return string
     */
    public static function searchTypeGroup($type)
    {
        if ($type === self::TYPE_BOOL) {
            return 'bool';
        }

        if (in_array($type, self::TYPE_GROUP_INT)) {
            return 'int';
        }

        if (in_array($type, self::TYPE_GROUP_FLOAT)) {
            return 'float';
        }

        if (in_array($type, self::TYPE_GROUP_DATE)) {
            return 'Carbon';
        }

        if (in_array($type, self::TYPE_GROUP_ARRAY)) {
            return 'array';
        }

        return 'string';
    }
}
