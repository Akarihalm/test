<?php

use App\Enums\CabinetEnum;
use App\Enums\PrefectureEnum;
use App\Utils\TableConfigManager;

return [
    'table' => 'point_distances',
    'settings' => [
        'soft_delete' => true,
        'by_user' => false,
        'index' => null,
        'unique' => null,
    ],
    'columns' => [
        'departure_point_id' => [
            'label' => '出発地点ID',
            'type' => TableConfigManager::TYPE_UNSIGNED_BIGINT,
            'digit' => null,
            'decimal_places' => null,
            'min' => null,
            'max' => null,
            'unique' => false,
            'index' => true,
            'required' => true,
            'enum' => null,
            'default_value' => null,
            'sample_value' => 1,
            'comment' => '',
            'foreign_table' => 'points',
            'foreign_column' => 'id',
        ],
        'arrival_point_id' => [
            'label' => '到着地点ID',
            'type' => TableConfigManager::TYPE_UNSIGNED_BIGINT,
            'digit' => null,
            'decimal_places' => null,
            'min' => null,
            'max' => null,
            'unique' => false,
            'index' => true,
            'required' => true,
            'enum' => null,
            'default_value' => null,
            'sample_value' => 2,
            'comment' => '',
            'foreign_table' => 'points',
            'foreign_column' => 'id',
        ],
        'ticket' => [
            'label' => '必要チケット数',
            'type' => TableConfigManager::TYPE_UNSIGNED_SMALLINT,
            'digit' => null,
            'decimal_places' => null,
            'min' => null,
            'max' => null,
            'unique' => false,
            'index' => false,
            'required' => true,
            'enum' => null,
            'default_value' => null,
            'sample_value' => 5,
            'comment' => '',
            'foreign_table' => null,
            'foreign_column' => null,
        ],
    ],
];
